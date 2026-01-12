<?php
/**
 * Vacations Module
 */

namespace HelpDesk\Modules\Vacations;

use HelpDesk\Modules\BaseModule;
use HelpDesk\Models\Employee;
use HelpDesk\Utils\Database;
use HelpDesk\Utils\CSV;
use HelpDesk\Utils\Security;

class VacationsModule extends BaseModule {
    protected $module_name = 'Neprítomnosti';
    protected $module_slug = 'vacations';
    protected $menu_page_id = 'helpdesk-vacations';

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        add_action( 'wp_ajax_helpdesk_import_vacations', array( $this, 'handle_import_vacations' ) );
        add_action( 'wp_ajax_helpdesk_sync_vacation_ids', array( $this, 'handle_sync_vacation_ids' ) );
        add_action( 'wp_ajax_helpdesk_apply_vacations', array( $this, 'handle_apply_vacations' ) );
        add_action( 'wp_ajax_helpdesk_update_vacation', array( $this, 'handle_update_vacation' ) );
        add_action( 'wp_ajax_helpdesk_delete_vacation', array( $this, 'handle_delete_vacation' ) );
        add_action( 'wp_ajax_helpdesk_delete_all_vacations', array( $this, 'handle_delete_all_vacations' ) );
        add_action( 'wp_ajax_helpdesk_check_vacation_duplicates', array( $this, 'handle_check_vacation_duplicates' ) );
        add_action( 'wp_ajax_helpdesk_delete_old_vacations', array( $this, 'handle_delete_old_vacations' ) );
    }

    /**
     * Register menu
     */
    public function register_menu() {
        // Menu registration happens in Admin class
    }

    /**
     * Handle import vacations from CSV AJAX
     */
    public function handle_import_vacations() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        if ( ! isset( $_FILES['csv_file'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No file uploaded', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $file = $_FILES['csv_file'];

        if ( ! empty( $file['error'] ) ) {
            wp_send_json_error( array( 'message' => __( 'File upload error', HELPDESK_TEXT_DOMAIN ) ) );
        }

        if ( ! file_exists( $file['tmp_name'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Uploaded file not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $content = file_get_contents( $file['tmp_name'] );

        if ( ! $content ) {
            wp_send_json_error( array( 'message' => __( 'Failed to read file', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Remove BOM if present
        if ( substr( $content, 0, 3 ) === "\xEF\xBB\xBF" ) {
            $content = substr( $content, 3 );
        }

        // Parse CSV with ; delimiter
        $lines = preg_split( '/\r\n|\r|\n/', trim( $content ) );
        $lines = array_filter( $lines );

        if ( count( $lines ) < 2 ) {
            wp_send_json_error( array( 'message' => __( 'CSV file is empty or has no data rows', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Parse header
        $header_line = array_shift( $lines );
        $headers = str_getcsv( $header_line, ';', '"' );
        $headers = array_map( 'trim', $headers );

        // Map column indices
        $col_pracovnik = -1;
        $col_od = -1;
        $col_do = -1;

        foreach ( $headers as $idx => $header ) {
            $h_normalized = strtolower( $this->remove_accents( $header ) );
            $h_trimmed = strtolower( trim( $header ) );
            
            if ( $col_pracovnik === -1 && strpos( $h_normalized, 'pracovnik' ) !== false ) {
                $col_pracovnik = $idx;
            }
            if ( $col_od === -1 && $h_trimmed === 'od' ) {
                $col_od = $idx;
            }
            if ( $col_do === -1 && $h_trimmed === 'do' ) {
                $col_do = $idx;
            }
        }

        if ( $col_pracovnik === -1 || $col_od === -1 || $col_do === -1 ) {
            wp_send_json_error( array( 'message' => __( 'CSV must contain columns: Pracovník, Od, Do', HELPDESK_TEXT_DOMAIN ) ) );
        }

        global $wpdb;
        $vacations_table = Database::get_vacations_table();
        
        // Clear old vacations
        $wpdb->query( "DELETE FROM {$vacations_table}" );

        $imported = 0;
        $errors = [];
        $seen_records = array(); // Track duplicates within CSV

        foreach ( $lines as $line_number => $line ) {
            $row_number = $line_number + 2;
            $values = str_getcsv( $line, ';', '"' );
            $values = array_map( 'trim', $values );

            if ( count( $values ) <= max( $col_pracovnik, $col_od, $col_do ) ) {
                $errors[] = sprintf( __( 'Row %d: Not enough columns', HELPDESK_TEXT_DOMAIN ), $row_number );
                continue;
            }

            $pracovnik_meno = $values[$col_pracovnik] ?? '';
            $od = $values[$col_od] ?? '';
            $do = $values[$col_do] ?? '';

            if ( ! $pracovnik_meno || ! $od || ! $do ) {
                $errors[] = sprintf( __( 'Row %d: Missing required fields', HELPDESK_TEXT_DOMAIN ), $row_number );
                continue;
            }

            // Parse dates - support both formats
            try {
                // Try to parse Czech date format (d.m.Y) or standard format
                if ( preg_match( '/^\d{1,2}\.\d{1,2}\.\d{4}/', $od ) ) {
                    $od_date = date( 'Y-m-d', strtotime( str_replace( '.', '-', $od ) ) );
                } else {
                    $od_date = date( 'Y-m-d', strtotime( $od ) );
                }

                if ( preg_match( '/^\d{1,2}\.\d{1,2}\.\d{4}/', $do ) ) {
                    $do_date = date( 'Y-m-d', strtotime( str_replace( '.', '-', $do ) ) );
                } else {
                    $do_date = date( 'Y-m-d', strtotime( $do ) );
                }

                if ( ! $od_date || ! $do_date ) {
                    $errors[] = sprintf( __( 'Row %d: Invalid date format', HELPDESK_TEXT_DOMAIN ), $row_number );
                    continue;
                }
            } catch ( Exception $e ) {
                $errors[] = sprintf( __( 'Row %d: Date parse error', HELPDESK_TEXT_DOMAIN ), $row_number );
                continue;
            }

            // Validate that od is before do
            if ( $od_date > $do_date ) {
                $errors[] = sprintf( __( 'Row %d: Nepritomnosť od musí byť pred nepritomnosťou do', HELPDESK_TEXT_DOMAIN ), $row_number );
                continue;
            }

            // Check for duplicates within CSV (same employee and overlapping dates)
            $duplicate_key = $pracovnik_meno . '_' . $od_date . '_' . $do_date;
            if ( isset( $seen_records[ $duplicate_key ] ) ) {
                $errors[] = sprintf( __( 'Row %d: Duplikát - rovnaký pracovník a период (už pridaný v CSV)', HELPDESK_TEXT_DOMAIN ), $row_number );
                continue;
            }
            $seen_records[ $duplicate_key ] = true;

            // Check for overlapping dates with existing records in database
            $existing_overlap = $wpdb->get_row( $wpdb->prepare(
                "SELECT id FROM {$vacations_table} 
                 WHERE meno_pracovnika = %s 
                 AND nepritomnost_od <= %s 
                 AND nepritomnost_do >= %s
                 LIMIT 1",
                $pracovnik_meno,
                $do_date,
                $od_date
            ) );

            if ( $existing_overlap ) {
                $errors[] = sprintf( __( 'Row %d: Konflikt - overlappinguje s existujúcim záznamom pre %s', HELPDESK_TEXT_DOMAIN ), $row_number, $pracovnik_meno );
                continue;
            }

            // Insert into absences table
            $result = $wpdb->insert(
                $vacations_table,
                array(
                    'meno_pracovnika' => $pracovnik_meno,
                    'nepritomnost_od' => $od_date,
                    'nepritomnost_do' => $do_date,
                    'created_at' => current_time( 'mysql' ),
                ),
                array( '%s', '%s', '%s', '%s' )
            );

            if ( $result ) {
                $imported++;
            } else {
                $errors[] = sprintf( __( 'Row %d: Failed to insert', HELPDESK_TEXT_DOMAIN ), $row_number );
            }
        }

        wp_send_json_success( array(
            'message' => sprintf( __( 'Naimportovaných dovoleniek: %d', HELPDESK_TEXT_DOMAIN ), $imported ),
            'imported' => $imported,
            'errors' => $errors,
        ) );
    }

    /**
     * Handle sync vacation IDs AJAX
     */
    public function handle_sync_vacation_ids() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        global $wpdb;
        $vacations_table = Database::get_vacations_table();
        $employees_table = Database::get_employees_table();

        // Get all vacations without pracovnik_id
        $vacations = $wpdb->get_results(
            "SELECT id, meno_pracovnika FROM {$vacations_table} WHERE pracovnik_id IS NULL OR pracovnik_id = 0",
            ARRAY_A
        );

        $synced = 0;
        $not_found = 0;

        foreach ( $vacations as $vacation ) {
            // Find employee by name (ignoring accents)
            $employee = $this->find_employee_by_name( $vacation['meno_pracovnika'] );

            if ( $employee ) {
                $wpdb->update(
                    $vacations_table,
                    array( 'pracovnik_id' => $employee->get( 'id' ) ),
                    array( 'id' => $vacation['id'] ),
                    array( '%d' ),
                    array( '%d' )
                );
                $synced++;
            } else {
                $not_found++;
            }
        }

        wp_send_json_success( array(
            'message' => sprintf( __( 'Synchronizovaných: %d, Nenájdených: %d', HELPDESK_TEXT_DOMAIN ), $synced, $not_found ),
            'synced' => $synced,
            'not_found' => $not_found,
        ) );
    }

    /**
     * Handle apply vacations AJAX
     */
    public function handle_apply_vacations() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        global $wpdb;
        $vacations_table = Database::get_vacations_table();
        $employees_table = Database::get_employees_table();

        // Get all vacations with pracovnik_id
        $vacations = $wpdb->get_results(
            "SELECT id, pracovnik_id, nepritomnost_od, nepritomnost_do FROM {$vacations_table} WHERE pracovnik_id IS NOT NULL AND pracovnik_id > 0",
            ARRAY_A
        );

        $applied = 0;

        foreach ( $vacations as $vacation ) {
            $result = $wpdb->update(
                $employees_table,
                array(
                    'dovolenka_od' => $vacation['nepritomnost_od'],
                    'dovolenka_do' => $vacation['nepritomnost_do'],
                ),
                array( 'id' => $vacation['pracovnik_id'] ),
                array( '%s', '%s' ),
                array( '%d' )
            );

            if ( $result !== false ) {
                $applied++;
            }
        }

        wp_send_json_success( array(
            'message' => sprintf( __( 'Aplikovaných dovoleniek: %d', HELPDESK_TEXT_DOMAIN ), $applied ),
            'applied' => $applied,
        ) );
    }

    /**
     * Handle delete vacation AJAX
     */
    public function handle_delete_vacation() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $vacation_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $vacation_id ) {
            wp_send_json_error( array( 'message' => __( 'Vacation ID required', HELPDESK_TEXT_DOMAIN ) ) );
        }

        global $wpdb;
        $vacations_table = Database::get_vacations_table();

        $result = $wpdb->delete(
            $vacations_table,
            array( 'id' => $vacation_id ),
            array( '%d' )
        );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Nepritomnosť bola vymazaná', HELPDESK_TEXT_DOMAIN ) ) );
        }

        wp_send_json_error( array( 'message' => __( 'Failed to delete vacation', HELPDESK_TEXT_DOMAIN ) ) );
    }

    /**
     * Handle delete all vacations AJAX
     */
    public function handle_delete_all_vacations() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        global $wpdb;
        $vacations_table = Database::get_vacations_table();

        $result = $wpdb->query( "DELETE FROM {$vacations_table}" );

        if ( $result !== false ) {
            wp_send_json_success( array( 'message' => __( 'Všetky nepritomnosti boli vymazané', HELPDESK_TEXT_DOMAIN ) ) );
        }

        wp_send_json_error( array( 'message' => __( 'Failed to delete vacations', HELPDESK_TEXT_DOMAIN ) ) );
    }

    /**
     * Handle update vacation AJAX
     */
    public function handle_update_vacation() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $vacation_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $meno = isset( $_POST['meno_pracovnika'] ) ? sanitize_text_field( $_POST['meno_pracovnika'] ) : '';
        $od = isset( $_POST['nepritomnost_od'] ) ? sanitize_text_field( $_POST['nepritomnost_od'] ) : '';
        $do = isset( $_POST['nepritomnost_do'] ) ? sanitize_text_field( $_POST['nepritomnost_do'] ) : '';

        if ( ! $vacation_id || ! $meno || ! $od || ! $do ) {
            wp_send_json_error( array( 'message' => __( 'Missing required fields', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Validate date format (YYYY-MM-DD)
        $date_pattern = '/^\d{4}-\d{2}-\d{2}$/';
        if ( ! preg_match( $date_pattern, $od ) || ! preg_match( $date_pattern, $do ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid date format', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Validate that od is before do
        if ( $od > $do ) {
            wp_send_json_error( array( 'message' => __( 'Nepritomnosť od musí byť pred nepritomnosťou do', HELPDESK_TEXT_DOMAIN ) ) );
        }

        global $wpdb;
        $vacations_table = Database::get_vacations_table();

        // Check for duplicate records - overlapping dates for same employee
        $check_sql = $wpdb->prepare(
            "SELECT id FROM " . $vacations_table . " 
             WHERE id != %d AND meno_pracovnika = %s 
             AND nepritomnost_od <= %s AND nepritomnost_do >= %s",
            $vacation_id,
            $meno,
            $do,       // Check if existing period ends after our start
            $od        // Check if existing period starts before our end
        );
        $existing = $wpdb->get_var( $check_sql );

        if ( $existing ) {
            wp_send_json_error( array( 'message' => __( 'Nepritomnosť sa prekrýva s existujúcou dovolenkovou (duplikát)', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $result = $wpdb->update(
            $vacations_table,
            array(
                'meno_pracovnika' => $meno,
                'nepritomnost_od' => $od,
                'nepritomnost_do' => $do,
            ),
            array( 'id' => $vacation_id ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( $result !== false ) {
            wp_send_json_success( array(
                'message' => __( 'Nepritomnosť bola aktualizovaná', HELPDESK_TEXT_DOMAIN ),
            ) );
        }

        wp_send_json_error( array( 'message' => __( 'Failed to update vacation', HELPDESK_TEXT_DOMAIN ) ) );
    }

    /**
     * Find employee by name (meno_priezvisko) - ignoring accents
     */
    private function find_employee_by_name( $name ) {
        $employees = \HelpDesk\Models\Employee::get_all();
        $search_name = $this->remove_accents( $name );
        
        foreach ( $employees as $emp ) {
            $emp_name = $this->remove_accents( $emp['meno_priezvisko'] );
            if ( strtolower( $emp_name ) === strtolower( $search_name ) ) {
                return new \HelpDesk\Models\Employee( $emp['id'] );
            }
        }
        return null;
    }

    /**
     * Remove accents from string
     */
    private function remove_accents( $str ) {
        $str = htmlspecialchars_decode( $str, ENT_QUOTES );
        $chars = array(
            'š' => 's', 'č' => 'c', 'ž' => 'z', 'Š' => 'S', 'Č' => 'C', 'Ž' => 'Z',
            'á' => 'a', 'ä' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'ú' => 'u', 'ý' => 'y',
            'Á' => 'A', 'Ä' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ô' => 'O', 'Ú' => 'U', 'Ý' => 'Y',
        );
        return strtr( $str, $chars );
    }

    /**
     * Handle check vacation duplicates AJAX
     */
    public function handle_check_vacation_duplicates() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        global $wpdb;
        $vacations_table = Database::get_vacations_table();

        // Simple approach: find any overlapping periods using direct SQL with limit
        // This avoids O(n²) PHP loops with large datasets
        $sql = "SELECT v1.id as id1, v1.meno_pracovnika, v1.nepritomnost_od as od1, v1.nepritomnost_do as do1,
                       v2.id as id2, v2.nepritomnost_od as od2, v2.nepritomnost_do as do2
                FROM {$vacations_table} v1
                INNER JOIN {$vacations_table} v2 
                  ON v1.meno_pracovnika = v2.meno_pracovnika 
                  AND v1.id < v2.id
                  AND v1.nepritomnost_od <= v2.nepritomnost_do 
                  AND v1.nepritomnost_do >= v2.nepritomnost_od
                ORDER BY v1.meno_pracovnika, v1.nepritomnost_od
                LIMIT 100";

        $dups = $wpdb->get_results( $sql, ARRAY_A );

        if ( ! $dups ) {
            wp_send_json_success( array(
                'message' => 'Žiadne duplikáty',
                'count' => 0,
                'duplicates' => array(),
            ) );
            return;
        }

        $formatted = array();
        foreach ( $dups as $d ) {
            $formatted[] = array(
                'employee' => $d['meno_pracovnika'],
                'record1' => array(
                    'id' => $d['id1'],
                    'od' => $d['od1'],
                    'do' => $d['do1'],
                ),
                'record2' => array(
                    'id' => $d['id2'],
                    'od' => $d['od2'],
                    'do' => $d['do2'],
                ),
            );
        }

        wp_send_json_success( array(
            'message' => 'Nájdené duplikáty: ' . count( $formatted ),
            'count' => count( $formatted ),
            'duplicates' => $formatted,
        ) );
    }

    /**
     * Handle delete old vacations AJAX
     */
    public function handle_delete_old_vacations() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        global $wpdb;
        $vacations_table = Database::get_vacations_table();
        $today = date( 'Y-m-d' );

        $before = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$vacations_table}" ) );

        // Direct DELETE without pre-SELECT for speed
        $deleted = $wpdb->query( "DELETE FROM {$vacations_table} WHERE nepritomnost_do < '{$today}'" );

        $after = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$vacations_table}" ) );

        if ( $deleted === false ) {
            wp_send_json_error( array( 'message' => 'Chyba: ' . $wpdb->last_error ) );
            return;
        }

        wp_send_json_success( array(
            'message' => "Vymazané: $deleted (bolo $before, zostalo $after)",
            'deleted' => $deleted,
            'before' => $before,
            'after' => $after,
        ) );
    }
}
