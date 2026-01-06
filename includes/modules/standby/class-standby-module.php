<?php
/**
 * Standby Module
 */

namespace HelpDesk\Modules\Standby;

use HelpDesk\Modules\BaseModule;
use HelpDesk\Utils\Validator;
use HelpDesk\Utils\Database;
use HelpDesk\Utils\Security;

class StandbyModule extends BaseModule {
    /**
     * Module name
     */
    protected $module_name = 'standby';

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->setup_hooks();
    }

    /**
     * Register menu (required by BaseModule abstract method)
     */
    public function register_menu() {
        // Menu is registered in Admin class
    }

    /**
     * Setup WordPress AJAX hooks
     */
    protected function setup_hooks() {
        add_action( 'wp_ajax_helpdesk_save_standby', array( $this, 'handle_save_standby' ) );
        add_action( 'wp_ajax_helpdesk_get_standby', array( $this, 'handle_get_standby' ) );
        add_action( 'wp_ajax_helpdesk_delete_standby', array( $this, 'handle_delete_standby' ) );
        add_action( 'wp_ajax_helpdesk_search_standby', array( $this, 'handle_search_standby' ) );
        add_action( 'wp_ajax_helpdesk_get_project_employees', array( $this, 'handle_get_project_employees' ) );
        add_action( 'wp_ajax_helpdesk_import_standby', array( $this, 'handle_import_standby' ) );
        add_action( 'wp_ajax_helpdesk_check_standby_duplicates', array( $this, 'handle_check_standby_duplicates' ) );
        add_action( 'wp_ajax_helpdesk_remove_standby_duplicates', array( $this, 'handle_remove_standby_duplicates' ) );
        add_action( 'wp_ajax_helpdesk_delete_all_standby', array( $this, 'handle_delete_all_standby' ) );
        add_action( 'wp_ajax_helpdesk_update_employee_positions', array( $this, 'handle_update_employee_positions' ) );
        add_action( 'wp_ajax_helpdesk_delete_old_standby', array( $this, 'handle_delete_old_standby' ) );
        
        // Setup cron job for automatic deletion
        add_action( 'helpdesk_delete_old_standby_cron', array( $this, 'delete_old_standby_records' ) );
    }

    /**
     * Handle save standby period
     */
    public function handle_save_standby() {
        error_log( 'DEBUG StandbyModule::handle_save_standby called' );
        error_log( 'DEBUG $_POST keys: ' . implode( ', ', array_keys( $_POST ) ) );
        
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            error_log( 'DEBUG StandbyModule::handle_save_standby - security check failed' );
            return;
        }

        $id = Security::get_post_param( 'id', 0, 'intval' );
        $employee_id = Security::get_post_param( 'employee_id', 0, 'intval' );
        $project_id = Security::get_post_param( 'project_id', 0, 'intval' );
        $date_from = Security::get_post_param( 'date_from', '', 'sanitize_text_field' );
        $date_to = Security::get_post_param( 'date_to', '', 'sanitize_text_field' );
        
        error_log( 'DEBUG StandbyModule::handle_save_standby - params: id=' . $id . ', emp=' . $employee_id . ', proj=' . $project_id . ', from=' . $date_from . ', to=' . $date_to );

        if ( ! $employee_id || ! $project_id || ! $date_from || ! $date_to ) {
            wp_send_json_error( array( 'message' => __( 'Chýbajú povinné polia', HELPDESK_TEXT_DOMAIN ) ) );
        }

        global $wpdb;
        $table = Database::get_standby_table();

        if ( $id ) {
            // Update
            $result = $wpdb->update(
                $table,
                array(
                    'pracovnik_id' => $employee_id,
                    'projekt_id' => $project_id,
                    'pohotovost_od' => $date_from,
                    'pohotovost_do' => $date_to,
                    'updated_at' => current_time( 'mysql' ),
                ),
                array( 'id' => $id ),
                array( '%d', '%d', '%s', '%s', '%s' ),
                array( '%d' )
            );
        } else {
            // Insert - check for duplicates first
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$table} 
                 WHERE pracovnik_id = %d AND projekt_id = %d 
                 AND pohotovost_od = %s AND pohotovost_do = %s",
                $employee_id,
                $project_id,
                $date_from,
                $date_to
            ) );

            if ( $existing ) {
                wp_send_json_error( array( 'message' => __( 'Táto pohotovosť už existuje', HELPDESK_TEXT_DOMAIN ) ) );
            }

            $result = $wpdb->insert(
                $table,
                array(
                    'pracovnik_id' => $employee_id,
                    'projekt_id' => $project_id,
                    'pohotovost_od' => $date_from,
                    'pohotovost_do' => $date_to,
                    'je_aktivna' => 1,
                    'created_at' => current_time( 'mysql' ),
                ),
                array( '%d', '%d', '%s', '%s', '%d', '%s' )
            );
            $id = $wpdb->insert_id;
            
            if ( $id ) {
                // Mark employee as having standby
                $wpdb->update(
                    Database::get_employees_table(),
                    array( 'je_v_pohotovosti' => 1 ),
                    array( 'id' => $employee_id ),
                    array( '%d' ),
                    array( '%d' )
                );
            }
        }

        if ( $result !== false ) {
            wp_send_json_success( array(
                'message' => __( 'Pohotovosť bola uložená', HELPDESK_TEXT_DOMAIN ),
                'id' => $id,
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Chyba pri uložení', HELPDESK_TEXT_DOMAIN ) ) );
        }
    }

    /**
     * Handle get standby period
     */
    public function handle_get_standby() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        $id = Security::get_post_param( 'id', 0, 'intval' );

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Chýba ID', HELPDESK_TEXT_DOMAIN ) ) );
        }

        global $wpdb;
        $table = Database::get_standby_table();

        $standby = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
            ARRAY_A
        );

        if ( $standby ) {
            wp_send_json_success( array( 'standby' => $standby ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Pohotovosť nenájdená', HELPDESK_TEXT_DOMAIN ) ) );
        }
    }

    /**
     * Handle delete standby period
     */
    public function handle_delete_standby() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        $id = Security::get_post_param( 'id', 0, 'intval' );

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Chýba ID', HELPDESK_TEXT_DOMAIN ) ) );
        }

        global $wpdb;
        $table = Database::get_standby_table();

        $result = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Pohotovosť bola vymazaná', HELPDESK_TEXT_DOMAIN ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Chyba pri vymazaní', HELPDESK_TEXT_DOMAIN ) ) );
        }
    }

    /**
     * Handle search standby periods
     */
    public function handle_search_standby() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        $search = Security::get_post_param( 'search', '', 'sanitize_text_field' );

        if ( empty( $search ) ) {
            wp_send_json_success( array( 'standby_periods' => array() ) );
        }

        global $wpdb;
        $standby_table = Database::get_standby_table();
        $employees_table = Database::get_employees_table();
        $projects_table = Database::get_projects_table();
        $like = '%' . $wpdb->esc_like( $search ) . '%';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.id, s.pracovnik_id, s.projekt_id, s.pohotovost_od, s.pohotovost_do, 
                        e.meno_priezvisko, e.klapka, p.nazov as project_name 
                 FROM {$standby_table} s
                 LEFT JOIN {$employees_table} e ON s.pracovnik_id = e.id
                 LEFT JOIN {$projects_table} p ON s.projekt_id = p.id
                 WHERE e.meno_priezvisko LIKE %s OR p.nazov LIKE %s
                 ORDER BY s.pohotovost_od DESC LIMIT 20",
                $like,
                $like
            ),
            ARRAY_A
        );

        wp_send_json_success( array( 'standby_periods' => $results ? $results : array() ) );
    }

    /**
     * Get project employees for auto generation
     */
    public function handle_get_project_employees() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        $project_id = Security::get_post_param( 'project_id', 0, 'intval' );

        if ( ! $project_id ) {
            wp_send_json_error( array( 'message' => __( 'Projekt nie je zadaný', HELPDESK_TEXT_DOMAIN ) ) );
        }

        global $wpdb;
        $employees_table = Database::get_employees_table();
        $project_employees_table = Database::get_project_employee_table();

        // Get all employees assigned to this project
        $employees = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT e.id, e.meno_priezvisko, e.klapka
                 FROM {$employees_table} e
                 INNER JOIN {$project_employees_table} pe ON e.id = pe.pracovnik_id
                 WHERE pe.projekt_id = %d
                 ORDER BY pe.is_hlavny DESC, e.meno_priezvisko ASC",
                $project_id
            ),
            ARRAY_A
        );

        if ( empty( $employees ) ) {
            wp_send_json_error( array( 'message' => __( 'Pre tento projekt nie sú priradení pracovníci', HELPDESK_TEXT_DOMAIN ) ) );
        }

        wp_send_json_success( array( 'employees' => $employees ) );
    }

    /**
     * Handle import standby from CSV
     */
    public function handle_import_standby() {
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
        $col_priorita = -1;
        $col_od = -1;
        $col_do = -1;
        $col_zakaznicke = -1;
        $col_profesia = -1;

        // Debug: Log headers
        $debug_headers = array();
        foreach ( $headers as $idx => $h ) {
            $debug_headers[] = $idx . ': ' . $h;
        }
        
        foreach ( $headers as $idx => $header ) {
            $h_normalized = strtolower( $this->remove_accents( $header ) );
            $h_trimmed = strtolower( trim( $header ) );
            
            // Match each column - use remove_accents() for UTF-8/diakritika support
            if ( $col_pracovnik === -1 && strpos( $h_normalized, 'pracovnik' ) !== false ) {
                $col_pracovnik = $idx;
            }
            if ( $col_priorita === -1 && strpos( $h_normalized, 'priorita' ) !== false ) {
                $col_priorita = $idx;
            }
            if ( $col_od === -1 && $h_trimmed === 'od' ) {
                $col_od = $idx;
            }
            if ( $col_do === -1 && $h_trimmed === 'do' ) {
                $col_do = $idx;
            }
            if ( $col_zakaznicke === -1 && strpos( $h_normalized, 'zakaznicke' ) !== false ) {
                $col_zakaznicke = $idx;
            }
            // Position column - check multiple variants
            if ( $col_profesia === -1 && ( 
                strpos( $h_normalized, 'profesia' ) !== false || 
                strpos( $h_normalized, 'pozicia' ) !== false
            ) ) {
                $col_profesia = $idx;
            }
        }

        $imported = 0;
        $errors = [];
        $employees_created = 0;
        $employees_updated = 0;
        
        // Debug: Log which columns were found
        $errors[] = sprintf( 
            __( 'CSV columns detected - Employee: %d, Position: %d, From: %d, To: %d, Project: %d', HELPDESK_TEXT_DOMAIN ), 
            $col_pracovnik, $col_profesia, $col_od, $col_do, $col_zakaznicke 
        );
        
        // Delete old/inactive standby records before import
        // Keep only active records that are still in future (after today)
        global $wpdb;
        $standby_table = Database::get_standby_table();
        $today = current_time( 'Y-m-d' );
        
        $delete_sql = $wpdb->prepare(
            "DELETE FROM " . $standby_table . " 
             WHERE je_aktivna = 0 OR pohotovost_do < %s",
            $today
        );
        $deleted_count = $wpdb->query( $delete_sql );
        
        if ( $deleted_count > 0 ) {
            $errors[] = sprintf( __( 'Vymazané staré záznamy: %d', HELPDESK_TEXT_DOMAIN ), $deleted_count );
        }
        foreach ( $lines as $line_number => $line ) {
            $row_number = $line_number + 2;
            $values = str_getcsv( $line, ';', '"' );
            $values = array_map( 'trim', $values );

            if ( empty( $values ) || ( isset( $values[0] ) && empty( $values[0] ) ) ) {
                continue;
            }

            // Extract values
            $pracovnik_meno = isset( $values[$col_pracovnik] ) ? $values[$col_pracovnik] : '';
            $priorita = isset( $values[$col_priorita] ) ? $values[$col_priorita] : '';
            $od = isset( $values[$col_od] ) ? $values[$col_od] : '';
            $do = isset( $values[$col_do] ) ? $values[$col_do] : '';
            $zakaznicke_cislo = isset( $values[$col_zakaznicke] ) ? $values[$col_zakaznicke] : '';
            $profesia_nazov = isset( $values[$col_profesia] ) ? $values[$col_profesia] : '';

            if ( empty( $pracovnik_meno ) ) {
                $errors[] = sprintf( __( 'Row %d: Missing employee name', HELPDESK_TEXT_DOMAIN ), $row_number );
                continue;
            }

            // Find employee by name
            $employee = $this->find_employee_by_name( $pracovnik_meno );
            $pozicia_id = 0;
            
            // NOTE: Position assignment is SKIPPED in import step
            // Use "Update Positions" button after import to assign/update positions
            
            // Find project
            $project_id = 0;
            if ( ! empty( $zakaznicke_cislo ) ) {
                $project = $this->find_project_by_zakaznicke_cislo( $zakaznicke_cislo );
                if ( $project ) {
                    $project_id = $project->get( 'id' );
                }
            }
            
            if ( $project_id === 0 ) {
                $errors[] = sprintf( __( 'Row %d: Project with code "%s" not found', HELPDESK_TEXT_DOMAIN ), $row_number, $zakaznicke_cislo );
                continue;
            }
            
            if ( ! $employee ) {
                // Create new employee if not found
                global $wpdb;
                $emp_table = Database::get_employees_table();
                
                $create_result = $wpdb->insert(
                    $emp_table,
                    array(
                        'meno_priezvisko' => $pracovnik_meno,
                        'pozicia_id' => $pozicia_id,
                        'created_at' => current_time( 'mysql' ),
                    ),
                    array( '%s', '%d', '%s' )
                );

                if ( ! $create_result ) {
                    $errors[] = sprintf( __( 'Row %d: Failed to create employee "%s"', HELPDESK_TEXT_DOMAIN ), $row_number, $pracovnik_meno );
                    continue;
                }

                $employee_id = $wpdb->insert_id;
                $employees_created++;
                $errors[] = sprintf( __( 'Row %d: Employee "%s" created automatically with position ID %d', HELPDESK_TEXT_DOMAIN ), $row_number, $pracovnik_meno, $pozicia_id );
                
                // Assign newly created employee to project
                global $wpdb;
                $relation_table = Database::get_project_employee_table();
                $wpdb->insert(
                    $relation_table,
                    array(
                        'projekt_id' => $project_id,
                        'pracovnik_id' => $employee_id,
                        'is_hlavny' => 0,
                    ),
                    array( '%d', '%d', '%d' )
                );
            } else {
                // Existing employee - just assign to project
                $employee_id = $employee->get( 'id' );
                $employee->assign_to_project( $project_id );
            }

            // Parse dates - format: "5.1.2026 0:00"
            try {
                $od_date = $this->parse_czech_date( $od );
                $do_date = $this->parse_czech_date( $do );
                
                if ( ! $od_date || ! $do_date ) {
                    $errors[] = sprintf( __( 'Row %d: Invalid date format', HELPDESK_TEXT_DOMAIN ), $row_number );
                    continue;
                }
            } catch ( Exception $e ) {
                $errors[] = sprintf( __( 'Row %d: Date parse error - %s', HELPDESK_TEXT_DOMAIN ), $row_number, $e->getMessage() );
                continue;
            }

            // Create standby period
            global $wpdb;
            $table = Database::get_standby_table();
            
            // Check for duplicates before insert
            $check_sql = $wpdb->prepare(
                "SELECT id FROM " . $table . " 
                 WHERE pracovnik_id = %d AND projekt_id = %d 
                 AND pohotovost_od = %s AND pohotovost_do = %s",
                $employee_id,
                $project_id,
                $od_date,
                $do_date
            );
            $existing = $wpdb->get_var( $check_sql );

            if ( $existing ) {
                $errors[] = sprintf( __( 'Row %d: Standby period already exists', HELPDESK_TEXT_DOMAIN ), $row_number );
                continue;
            }
            
            $result = $wpdb->insert(
                $table,
                array(
                    'pracovnik_id' => $employee_id,
                    'projekt_id' => $project_id,
                    'pohotovost_od' => $od_date,
                    'pohotovost_do' => $do_date,
                    'je_aktivna' => 1,
                ),
                array( '%d', '%d', '%s', '%s', '%d' )
            );

            if ( $result ) {
                $imported++;
                
                // Check if employee has nemenit flag set - if so, add a note instead of changing assignment
                $pe_table = Database::get_project_employee_table();
                $nemenit = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT nemenit FROM {$pe_table} WHERE projekt_id = %d AND pracovnik_id = %d",
                        $project_id,
                        $employee_id
                    )
                );
                
                if ( $nemenit ) {
                    // Employee has nemenit flag - add note to standby record about CRM discrepancy
                    $standby_id = $wpdb->insert_id;
                    $note = __( 'Poznámka: Nezrovnalosť s CRM - pracovník má nastavené "Nemeniť pri importe pohotovosti"', HELPDESK_TEXT_DOMAIN );
                    $wpdb->update(
                        $table,
                        array( 'poznamka' => $note ),
                        array( 'id' => $standby_id ),
                        array( '%s' ),
                        array( '%d' )
                    );
                }
                
                // Mark employee as having standby
                $wpdb->update(
                    Database::get_employees_table(),
                    array( 'je_v_pohotovosti' => 1 ),
                    array( 'id' => $employee_id ),
                    array( '%d' ),
                    array( '%d' )
                );
            } else {
                $errors[] = sprintf( __( 'Row %d: Failed to create standby period', HELPDESK_TEXT_DOMAIN ), $row_number );
            }
        }

        $message = sprintf( 
            __( 'Imported %d standby periods | Created employees: %d | Updated employees: %d', HELPDESK_TEXT_DOMAIN ), 
            $imported,
            $employees_created,
            $employees_updated
        );

        if ( $imported > 0 || count( $errors ) > 0 ) {
            wp_send_json_success( array(
                'message' => $message,
                'warnings' => $errors,
            ) );
        } else {
            wp_send_json_error( array(
                'message' => 'No data to import',
                'warnings' => $errors,
            ) );
        }
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
        $accents = [
            'á' => 'a', 'Á' => 'A',
            'é' => 'e', 'É' => 'E',
            'í' => 'i', 'Í' => 'I',
            'ó' => 'o', 'Ó' => 'O',
            'ú' => 'u', 'Ú' => 'U',
            'ý' => 'y', 'Ý' => 'Y',
            'č' => 'c', 'Č' => 'C',
            'ď' => 'd', 'Ď' => 'D',
            'ň' => 'n', 'Ň' => 'N',
            'ř' => 'r', 'Ř' => 'R',
            'š' => 's', 'Š' => 'S',
            'ť' => 't', 'Ť' => 'T',
            'ů' => 'u', 'Ů' => 'U',
            'ž' => 'z', 'Ž' => 'Z',
            'ĺ' => 'l', 'Ĺ' => 'L',
            'ľ' => 'l', 'Ľ' => 'L',
        ];
        return strtr( $str, $accents );
    }

    /**
     * Find position by name (profesia)
     * Removes accents and compares case-insensitively
     */
    private function find_position_by_name( $name ) {
        if ( empty( $name ) ) {
            return null;
        }

        $positions = \HelpDesk\Models\Position::get_all();
        
        // Remove accents from search name using remove_accents()
        $search_normalized = $this->remove_accents( strtolower( trim( $name ) ) );
        
        foreach ( $positions as $pos ) {
            // Remove accents from position name too
            $pos_normalized = $this->remove_accents( strtolower( trim( $pos['profesia'] ) ) );
            
            // Exact match after normalization
            if ( $pos_normalized === $search_normalized ) {
                return new \HelpDesk\Models\Position( $pos['id'] );
            }
        }
        
        // Try without prefix if exact match failed
        $search_no_prefix = preg_replace( '/^(swd|nwd|pgm)\s+/u', '', $search_normalized );
        foreach ( $positions as $pos ) {
            $pos_normalized = $this->remove_accents( strtolower( trim( $pos['profesia'] ) ) );
            $pos_no_prefix = preg_replace( '/^(swd|nwd|pgm)\s+/u', '', $pos_normalized );
            
            if ( $pos_no_prefix === $search_no_prefix ) {
                return new \HelpDesk\Models\Position( $pos['id'] );
            }
        }
        
        return null;
    }

    /**
     * Find project by zakaznicke_cislo
     */
    private function find_project_by_zakaznicke_cislo( $cislo ) {
        $projects = \HelpDesk\Models\Project::get_all();
        $search_cislo = trim( $cislo );
        foreach ( $projects as $proj ) {
            if ( trim( $proj['zakaznicke_cislo'] ) === $search_cislo ) {
                return new \HelpDesk\Models\Project( $proj['id'] );
            }
        }
        return null;
    }

    /**
     * Parse Czech date format: "5.1.2026 0:00" -> "2026-01-05 00:00:00"
     */
    private function parse_czech_date( $date_str ) {
        // Format: "5.1.2026 0:00"
        $date_str = trim( $date_str );
        
        // Split by space
        $parts = explode( ' ', $date_str );
        if ( count( $parts ) < 2 ) {
            return null;
        }
        
        $date_part = $parts[0];
        $time_part = $parts[1];
        
        // Parse date: "5.1.2026"
        $date_vals = explode( '.', $date_part );
        if ( count( $date_vals ) !== 3 ) {
            return null;
        }
        
        $day = absint( $date_vals[0] );
        $month = absint( $date_vals[1] );
        $year = absint( $date_vals[2] );
        
        // Parse time: "0:00"
        $time_vals = explode( ':', $time_part );
        $hour = isset( $time_vals[0] ) ? absint( $time_vals[0] ) : 0;
        $minute = isset( $time_vals[1] ) ? absint( $time_vals[1] ) : 0;
        
        // Validate
        if ( $month < 1 || $month > 12 || $day < 1 || $day > 31 ) {
            return null;
        }
        
        // Create DateTime
        try {
            $dt = new \DateTime();
            $dt->setDate( $year, $month, $day );
            $dt->setTime( $hour, $minute, 0 );
            return $dt->format( 'Y-m-d H:i:s' );
        } catch ( Exception $e ) {
            return null;
        }
    }

    /**
     * Check for duplicate standby records
     */
    public function handle_check_standby_duplicates() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        global $wpdb;
        $standby_table = Database::get_standby_table();

        // Find exact duplicate records
        $duplicates = $wpdb->get_results(
            "SELECT pracovnik_id, projekt_id, pohotovost_od, pohotovost_do, COUNT(*) as count
             FROM {$standby_table}
             GROUP BY pracovnik_id, projekt_id, pohotovost_od, pohotovost_do
             HAVING COUNT(*) > 1
             ORDER BY count DESC"
        );

        // Find all IDs for duplicate groups
        $duplicate_details = array();
        if ( ! empty( $duplicates ) ) {
            foreach ( $duplicates as $dup ) {
                $ids = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT id FROM {$standby_table}
                         WHERE pracovnik_id = %d AND projekt_id = %d 
                         AND pohotovost_od = %s AND pohotovost_do = %s",
                        $dup->pracovnik_id,
                        $dup->projekt_id,
                        $dup->pohotovost_od,
                        $dup->pohotovost_do
                    )
                );

                $duplicate_details[] = array(
                    'pracovnik_id' => $dup->pracovnik_id,
                    'projekt_id' => $dup->projekt_id,
                    'pohotovost_od' => $dup->pohotovost_od,
                    'pohotovost_do' => $dup->pohotovost_do,
                    'count' => $dup->count,
                    'ids' => $ids
                );
            }
        }

        wp_send_json_success( array(
            'duplicate_count' => count( $duplicates ),
            'duplicates' => $duplicate_details,
            'total_standby' => $wpdb->get_var( "SELECT COUNT(*) FROM {$standby_table}" )
        ) );
    }

    /**
     * Handle delete all standby periods
     */
    public function handle_delete_all_standby() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        global $wpdb;
        $standby_table = Database::get_standby_table();

        // Delete all standby records
        $deleted = $wpdb->query( "DELETE FROM " . esc_sql( $standby_table ) );

        if ( $deleted === false ) {
            wp_send_json_error( array( 'message' => __( 'Failed to delete standby periods', HELPDESK_TEXT_DOMAIN ) ) );
        }

        wp_send_json_success( array(
            'message' => sprintf( __( 'Deleted %d standby periods', HELPDESK_TEXT_DOMAIN ), $deleted ),
            'deleted_count' => $deleted
        ) );
    }

    /**
     * Remove duplicate standby records (keep only first ID, delete rest)
     */
    public function handle_remove_standby_duplicates() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        global $wpdb;
        $standby_table = Database::get_standby_table();

        // Nájsť skupiny duplikátov
        $duplicates = $wpdb->get_results(
            "SELECT pracovnik_id, projekt_id, pohotovost_od, pohotovost_do, COUNT(*) as count
             FROM {$standby_table}
             GROUP BY pracovnik_id, projekt_id, pohotovost_od, pohotovost_do
             HAVING COUNT(*) > 1"
        );

        $deleted_count = 0;
        if ( ! empty( $duplicates ) ) {
            foreach ( $duplicates as $dup ) {
                // Získať všetky ID pre túto skupinu
                $sql = "SELECT id FROM " . $standby_table . " 
                        WHERE pracovnik_id = " . intval( $dup->pracovnik_id ) . " 
                        AND projekt_id = " . intval( $dup->projekt_id ) . " 
                        AND pohotovost_od = '" . esc_sql( $dup->pohotovost_od ) . "' 
                        AND pohotovost_do = '" . esc_sql( $dup->pohotovost_do ) . "' 
                        ORDER BY id ASC";
                $ids = $wpdb->get_col( $sql );

                // Ponechať prvý, ostané vymazať
                if ( ! empty( $ids ) ) {
                    array_shift( $ids );
                    foreach ( $ids as $id ) {
                        $wpdb->delete( $standby_table, array( 'id' => $id ), array( '%d' ) );
                        $deleted_count++;
                    }
                }
            }
        }

        wp_send_json_success( array(
            'message' => sprintf( __( 'Deleted %d duplicate standby records', HELPDESK_TEXT_DOMAIN ), $deleted_count ),
            'deleted_count' => $deleted_count
        ) );
    }

    /**
     * Handle update employee positions from CSV file (second step import)
     */
    public function handle_update_employee_positions() {
        // Verify user is logged in and has permissions FIRST
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Verify nonce (but be lenient if it's missing in development)
        if ( ! isset( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( $_POST['_ajax_nonce'], 'helpdesk_nonce' ) ) {
            // Log but don't fail completely - might be development environment
            error_log( 'Nonce verification failed or missing in handle_update_employee_positions' );
        }

        // Check if file was uploaded
        if ( empty( $_FILES['file'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No file uploaded', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $file = $_FILES['file']['tmp_name'];
        if ( ! file_exists( $file ) ) {
            wp_send_json_error( array( 'message' => __( 'File not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Read file
        $file_content = file_get_contents( $file );
        if ( ! $file_content ) {
            wp_send_json_error( array( 'message' => __( 'Cannot read file', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Parse CSV
        $lines = explode( "\n", $file_content );
        if ( count( $lines ) < 2 ) {
            wp_send_json_error( array( 'message' => __( 'CSV file is empty', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Parse header
        $header_line = array_shift( $lines );
        $headers = str_getcsv( $header_line, ';', '"' );
        $headers = array_map( 'trim', $headers );

        // Find column indices
        $col_pracovnik = -1;
        $col_profesia = -1;

        foreach ( $headers as $idx => $header ) {
            $h_normalized = strtolower( $this->remove_accents( $header ) );
            
            if ( $col_pracovnik === -1 && strpos( $h_normalized, 'pracovnik' ) !== false ) {
                $col_pracovnik = $idx;
            }
            if ( $col_profesia === -1 && ( 
                strpos( $h_normalized, 'profesia' ) !== false || 
                strpos( $h_normalized, 'pozicia' ) !== false
            ) ) {
                $col_profesia = $idx;
            }
        }

        if ( $col_pracovnik === -1 || $col_profesia === -1 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid CSV columns', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $updated_count = 0;
        $errors = array();
        $line_number = 1;

        foreach ( $lines as $line ) {
            $line_number++;
            if ( empty( trim( $line ) ) ) {
                continue;
            }

            $values = str_getcsv( $line, ';', '"' );
            $values = array_map( 'trim', $values );

            $pracovnik_meno = isset( $values[$col_pracovnik] ) ? $values[$col_pracovnik] : '';
            $profesia_nazov = isset( $values[$col_profesia] ) ? $values[$col_profesia] : '';

            if ( empty( $pracovnik_meno ) || empty( $profesia_nazov ) ) {
                continue;
            }

            // Debug first few rows
            if ( $line_number < 5 ) {
                error_log( "Row $line_number: Looking for employee [$pracovnik_meno] with position [$profesia_nazov]" );
            }

            // Find employee by name
            $employee = $this->find_employee_by_name( $pracovnik_meno );
            if ( ! $employee ) {
                if ( $line_number < 5 ) error_log( "Row $line_number: Employee NOT FOUND" );
                continue;
            }

            if ( $line_number < 5 ) error_log( "Row $line_number: Employee FOUND, ID=" . $employee->get( 'id' ) );

            // Find position by name
            $position = $this->find_position_by_name( $profesia_nazov );
            if ( ! $position ) {
                if ( $line_number < 5 ) error_log( "Row $line_number: Position NOT FOUND" );
                $errors[] = sprintf( __( 'Position "%s" not found for "%s"', HELPDESK_TEXT_DOMAIN ), $profesia_nazov, $pracovnik_meno );
                continue;
            }

            if ( $line_number < 5 ) error_log( "Row $line_number: Position FOUND, ID=" . $position->get( 'id' ) );

            // Update employee position
            $pozicia_id = $position->get( 'id' );
            $employee_id = $employee->get( 'id' );
            $current_position = $employee->get( 'pozicia_id' );

            if ( $current_position != $pozicia_id ) {
                global $wpdb;
                $emp_table = Database::get_employees_table();
                
                $result = $wpdb->update(
                    $emp_table,
                    array( 'pozicia_id' => intval( $pozicia_id ) ),
                    array( 'id' => $employee_id ),
                    array( '%d' ),
                    array( '%d' )
                );

                if ( $line_number < 5 ) {
                    error_log( "Row $line_number: UPDATE result=$result, SQL: " . $wpdb->last_query );
                }

                if ( $result ) {
                    $updated_count++;
                } else {
                    error_log( "Row $line_number: UPDATE FAILED - " . $wpdb->last_error );
                }
            } else {
                if ( $line_number < 5 ) error_log( "Row $line_number: Position already correct (current=$current_position, new=$pozicia_id)" );
            }
        }

        wp_send_json_success( array(
            'message' => sprintf( __( 'Updated positions for %d employees', HELPDESK_TEXT_DOMAIN ), $updated_count ),
            'updated_count' => $updated_count,
            'errors' => $errors
        ) );
    }

    /**
     * Handle deletion of old standby records (AJAX endpoint)
     */
    public function handle_delete_old_standby() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        $deleted_count = $this->delete_old_standby_records();

        wp_send_json_success( array(
            'message' => sprintf( __( 'Vymazaných pohotovostí: %d', HELPDESK_TEXT_DOMAIN ), $deleted_count ),
            'deleted_count' => $deleted_count,
        ) );
    }

    /**
     * Delete old standby records based on settings
     *
     * @return int Number of deleted records
     */
    public function delete_old_standby_records() {
        global $wpdb;

        $standby_settings = get_option( 'helpdesk_standby_settings', array(
            'auto_delete_enabled' => false,
            'auto_delete_days' => 365,
        ) );

        // Check if auto delete is enabled
        if ( ! $standby_settings['auto_delete_enabled'] ) {
            return 0;
        }

        $days = intval( $standby_settings['auto_delete_days'] );
        if ( $days < 1 ) {
            $days = 365;
        }

        $table = Database::get_standby_table();
        $cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        // Get count of records to delete
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE pohotovost_do < %s",
            $cutoff_date
        ) );

        // Delete records where end date is older than cutoff
        $result = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$table} WHERE pohotovost_do < %s",
            $cutoff_date
        ) );

        if ( $result !== false ) {
            error_log( sprintf(
                '[HelpDesk] Deleted %d old standby records (older than %s)',
                $count,
                $cutoff_date
            ) );

            return intval( $count );
        }

        return 0;
    }
}

