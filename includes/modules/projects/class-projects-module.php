<?php
/**
 * Projects Module
 */

namespace HelpDesk\Modules\Projects;

use HelpDesk\Modules\BaseModule;
use HelpDesk\Models\Project;
use HelpDesk\Models\Employee;
use HelpDesk\Utils\Validator;
use HelpDesk\Utils\Database;
use HelpDesk\Utils\Security;

class ProjectsModule extends BaseModule {
    protected $module_name = 'Projekty';
    protected $module_slug = 'projects';
    protected $menu_page_id = 'helpdesk-projects';

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        add_action( 'wp_ajax_helpdesk_save_project', array( $this, 'handle_save_project' ) );
        add_action( 'wp_ajax_helpdesk_delete_project', array( $this, 'handle_delete_project' ) );
        add_action( 'wp_ajax_helpdesk_get_project', array( $this, 'handle_get_project' ) );
        add_action( 'wp_ajax_helpdesk_get_projects', array( $this, 'handle_get_projects' ) );
        add_action( 'wp_ajax_helpdesk_get_all_projects_with_employees', array( $this, 'handle_get_all_projects_with_employees' ) );
        add_action( 'wp_ajax_helpdesk_add_employee_to_project', array( $this, 'handle_add_employee' ) );
        add_action( 'wp_ajax_helpdesk_remove_employee_from_project', array( $this, 'handle_remove_employee' ) );
        add_action( 'wp_ajax_helpdesk_set_project_employee_status', array( $this, 'handle_set_employee_status' ) );
        add_action( 'wp_ajax_helpdesk_export_projects', array( $this, 'handle_export_projects' ) );
        add_action( 'wp_ajax_helpdesk_import_projects', array( $this, 'handle_import_projects' ) );
        add_action( 'wp_ajax_helpdesk_search_projects', array( $this, 'handle_search_projects' ) );
        add_action( 'wp_ajax_helpdesk_sync_employee_names', array( $this, 'handle_sync_employee_names' ) );
    }

    /**
     * Register menu
     */
    public function register_menu() {
        // Menu registration happens in Admin class
    }

    /**
     * Handle save project AJAX
     */
    public function handle_save_project() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $project_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        
        // Parse project data
        $data = array();
        if ( isset( $_POST['project_data'] ) ) {
            $data = json_decode( stripslashes( sanitize_text_field( $_POST['project_data'] ) ), true );
        }

        if ( empty( $data ) || ! is_array( $data ) ) {
            wp_send_json_error( array( 'message' => 'Neboli prijaté údaje projektu' ) );
        }

        // Sanitize - zakaznicke_cislo, hd_kontakt, pm_manazer_id, sla_manazer_id, poznamka a nemenit
        $sanitized = array(
            'zakaznicke_cislo' => isset( $data['zakaznicke_cislo'] ) ? sanitize_text_field( $data['zakaznicke_cislo'] ) : '',
            'hd_kontakt' => isset( $data['hd_kontakt'] ) ? sanitize_text_field( $data['hd_kontakt'] ) : '',
            'pm_manazer_id' => isset( $data['pm_manazer_id'] ) && ! empty( $data['pm_manazer_id'] ) ? absint( $data['pm_manazer_id'] ) : null,
            'sla_manazer_id' => isset( $data['sla_manazer_id'] ) && ! empty( $data['sla_manazer_id'] ) ? absint( $data['sla_manazer_id'] ) : null,
            'poznamka' => isset( $data['poznamka'] ) ? sanitize_textarea_field( $data['poznamka'] ) : '',
            'nemenit' => isset( $data['nemenit'] ) ? (int) $data['nemenit'] : 0
        );

        // Validate
        if ( empty( $sanitized['zakaznicke_cislo'] ) ) {
            wp_send_json_error( array( 'errors' => array( 'zakaznicke_cislo' => __( 'Zákaznícke číslo je povinné', HELPDESK_TEXT_DOMAIN ) ) ) );
        }

        if ( $project_id ) {
            // Update
            $project = new Project( $project_id );

            if ( ! $project->exists() ) {
                wp_send_json_error( array( 'message' => __( 'Project not found', HELPDESK_TEXT_DOMAIN ) ) );
            }

            $project->update( $sanitized );
            
            // Remove all existing employees and add new ones
            $old_employees = $project->get_employees();
            foreach ( $old_employees as $emp ) {
                $project->remove_employee( $emp['id'] );
            }
            
            // Parse employees - can be array of IDs or array of objects with {id, is_hlavny}
            $employees_raw = isset( $_POST['employees'] ) ? $_POST['employees'] : '[]';
            
            error_log( 'EMPLOYEES_RAW (raw POST): ' . print_r( $employees_raw, true ) );
            
            // Ak je to JSON string, parsuj ho
            if ( is_string( $employees_raw ) ) {
                $employees_raw = json_decode( stripslashes( $employees_raw ), true );
                if ( ! is_array( $employees_raw ) ) {
                    $employees_raw = array();
                }
            } else {
                $employees_raw = (array) $employees_raw;
            }
            
            error_log( 'EMPLOYEES_PARSED: ' . print_r( $employees_raw, true ) );
            
            if ( is_array( $employees_raw ) && ! empty( $employees_raw ) ) {
                foreach ( $employees_raw as $emp_item ) {
                    $emp_id = is_array( $emp_item ) ? absint( $emp_item['id'] ?? 0 ) : absint( $emp_item );
                    $is_hlavny = 0;
                    
                    if ( is_array( $emp_item ) && isset( $emp_item['is_hlavny'] ) ) {
                        $is_hlavny = absint( $emp_item['is_hlavny'] );
                    }
                    
                    error_log( 'Adding employee: ID=' . $emp_id . ', is_hlavny=' . $is_hlavny );
                    
                    if ( $emp_id > 0 ) {
                        $project->add_employee( $emp_id, $is_hlavny );
                    }
                }
            }
            
            wp_send_json_success( array(
                'message' => __( 'Project updated successfully', HELPDESK_TEXT_DOMAIN )
            ) );
        } else {
            // Create new project
            $project = new Project();
            $new_id = $project->create( $sanitized );
            
            if ( ! $new_id ) {
                wp_send_json_error( array( 'message' => __( 'Failed to create project', HELPDESK_TEXT_DOMAIN ) ) );
            }
            
            // Add employees
            $employees_raw = isset( $_POST['employees'] ) ? $_POST['employees'] : '[]';
            
            error_log( 'CREATE: EMPLOYEES_RAW (raw POST): ' . print_r( $employees_raw, true ) );
            
            // Ak je to JSON string, parsuj ho
            if ( is_string( $employees_raw ) ) {
                $employees_raw = json_decode( stripslashes( $employees_raw ), true );
                if ( ! is_array( $employees_raw ) ) {
                    $employees_raw = array();
                }
            } else {
                $employees_raw = (array) $employees_raw;
            }
            
            error_log( 'CREATE: EMPLOYEES_PARSED: ' . print_r( $employees_raw, true ) );
            
            if ( is_array( $employees_raw ) && ! empty( $employees_raw ) ) {
                foreach ( $employees_raw as $emp_item ) {
                    $emp_id = is_array( $emp_item ) ? absint( $emp_item['id'] ?? 0 ) : absint( $emp_item );
                    $is_hlavny = 0;
                    
                    if ( is_array( $emp_item ) && isset( $emp_item['is_hlavny'] ) ) {
                        $is_hlavny = absint( $emp_item['is_hlavny'] );
                    }
                    
                    error_log( 'CREATE: Adding employee: ID=' . $emp_id . ', is_hlavny=' . $is_hlavny );
                    
                    if ( $emp_id > 0 ) {
                        $project->add_employee( $emp_id, $is_hlavny );
                    }
                }
            }
            
            wp_send_json_success( array(
                'message' => __( 'Project created successfully', HELPDESK_TEXT_DOMAIN )
            ) );
        }
    }

    /**
     * Handle delete project AJAX
     */
    public function handle_delete_project() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $project_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $project_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid project ID', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $project = new Project( $project_id );

        if ( ! $project->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Project not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        if ( $project->delete() ) {
            wp_send_json_success( array( 'message' => __( 'Project deleted successfully', HELPDESK_TEXT_DOMAIN ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete project', HELPDESK_TEXT_DOMAIN ) ) );
        }
    }

    /**
     * Handle get project AJAX
     */
    public function handle_get_project() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $project_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $project_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid project ID', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $project = new Project( $project_id );

        if ( ! $project->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Project not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $project_employees = $project->get_employees_with_standby();
        $all_employees = Employee::get_all();
        
        // Debug: Log what we're returning
        error_log( 'PROJECT EMPLOYEES WITH STANDBY: ' . print_r( $project_employees, true ) );
        
        wp_send_json_success( array(
            'project' => $project->get_all_data(),
            'employees' => $project_employees,
            'all_employees' => $all_employees,
        ) );
    }

    /**
     * Handle get projects AJAX
     */
    public function handle_get_projects() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        // Get filter settings
        $dashboard_filters = get_option( 'helpdesk_dashboard_filters', array(
            'show_nw_projects' => false,
        ) );
        $show_nw_projects = (bool) $dashboard_filters['show_nw_projects'];

        global $wpdb;
        $table = Database::get_projects_table();

        // Build query with filter
        $sql = "SELECT * FROM {$table}";

        // Apply -nw filter if needed
        if ( ! $show_nw_projects ) {
            $sql .= " WHERE zakaznicke_cislo NOT LIKE %s";
            $projects = $wpdb->get_results(
                $wpdb->prepare( $sql, '%-nw%' ),
                ARRAY_A
            );
        } else {
            $projects = $wpdb->get_results( $sql, ARRAY_A );
        }

        wp_send_json_success( array( 'projects' => $projects ) );
    }

    /**
     * Handle get all projects with employees AJAX
     */
    public function handle_get_all_projects_with_employees() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        // Get filter settings
        $dashboard_filters = get_option( 'helpdesk_dashboard_filters', array(
            'show_nw_projects' => false,
        ) );
        $show_nw_projects = (bool) $dashboard_filters['show_nw_projects'];

        global $wpdb;
        $table = Database::get_projects_table();
        $employees_table = Database::get_employees_table();

        // Build query with PM and SLA info
        $sql = "SELECT p.*, e_pm.meno_priezvisko as pm_name, e_sla.meno_priezvisko as sla_name 
                FROM {$table} p 
                LEFT JOIN {$employees_table} e_pm ON p.pm_manazer_id = e_pm.id 
                LEFT JOIN {$employees_table} e_sla ON p.sla_manazer_id = e_sla.id";

        // Apply -nw filter if needed
        if ( ! $show_nw_projects ) {
            $sql .= " WHERE p.zakaznicke_cislo NOT LIKE %s";
            $projects = $wpdb->get_results(
                $wpdb->prepare( $sql, '%-nw%' ),
                ARRAY_A
            );
        } else {
            $projects = $wpdb->get_results( $sql, ARRAY_A );
        }
        
        // Load employees for each project
        foreach ( $projects as &$project ) {
            $proj_obj = new Project( $project['id'] );
            $project['employees'] = $proj_obj->get_employees_with_standby();
        }
        
        wp_send_json_success( array( 'projects' => $projects ) );
    }

    /**
     * Handle add employee to project AJAX
     */
    public function handle_add_employee() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $project_id = isset( $_POST['project_id'] ) ? absint( $_POST['project_id'] ) : 0;
        $employee_id = isset( $_POST['employee_id'] ) ? absint( $_POST['employee_id'] ) : 0;
        $is_main = isset( $_POST['is_main'] ) ? (bool) $_POST['is_main'] : false;

        if ( ! $project_id || ! $employee_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid project or employee ID', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $project = new Project( $project_id );

        if ( ! $project->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Project not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $project->add_employee( $employee_id, $is_main );

        wp_send_json_success( array(
            'message' => __( 'Employee added to project', HELPDESK_TEXT_DOMAIN ),
            'employees' => $project->get_employees(),
        ) );
    }

    /**
     * Handle remove employee from project AJAX
     */
    public function handle_remove_employee() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $project_id = isset( $_POST['project_id'] ) ? absint( $_POST['project_id'] ) : 0;
        $employee_id = isset( $_POST['employee_id'] ) ? absint( $_POST['employee_id'] ) : 0;

        if ( ! $project_id || ! $employee_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid project or employee ID', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $project = new Project( $project_id );

        if ( ! $project->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Project not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $project->remove_employee( $employee_id );

        wp_send_json_success( array(
            'message' => __( 'Employee removed from project', HELPDESK_TEXT_DOMAIN ),
            'employees' => $project->get_employees(),
        ) );
    }

    /**
     * Handle set project employee status (Hlavný/Člen)
     */
    public function handle_set_employee_status() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $project_id = isset( $_POST['project_id'] ) ? absint( $_POST['project_id'] ) : 0;
        $employee_id = isset( $_POST['employee_id'] ) ? absint( $_POST['employee_id'] ) : 0;
        $is_hlavny = isset( $_POST['is_hlavny'] ) ? absint( $_POST['is_hlavny'] ) : 0;

        if ( ! $project_id || ! $employee_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid project or employee ID', HELPDESK_TEXT_DOMAIN ) ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'hd_projekt_pracovnik';

        $result = $wpdb->update(
            $table,
            array( 'is_hlavny' => $is_hlavny ),
            array( 'projekt_id' => $project_id, 'pracovnik_id' => $employee_id ),
            array( '%d' ),
            array( '%d', '%d' )
        );

        if ( $result === false ) {
            wp_send_json_error( array( 'message' => __( 'Database error', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // If setting as main, handle standby and multiple mains
        if ( $is_hlavny ) {
            // Check if this employee has standby period for this project
            $standby_table = $wpdb->prefix . 'hd_pracovnik_pohotovost';
            $has_standby = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $standby_table 
                    WHERE pracovnik_id = %d AND projekt_id = %d AND koniec IS NULL",
                    $employee_id,
                    $project_id
                )
            );

            // If employee is on standby, just set as main
            if ( $has_standby ) {
                wp_send_json_success( array(
                    'message' => __( 'Employee set as main (has active standby)', HELPDESK_TEXT_DOMAIN )
                ) );
                return;
            }

            // Check if there are other mains for this project
            $other_mains = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table 
                    WHERE projekt_id = %d AND is_hlavny = 1 AND pracovnik_id != %d",
                    $project_id,
                    $employee_id
                )
            );

            if ( $other_mains > 0 ) {
                wp_send_json_success( array(
                    'message' => __( 'Employee set as main (other mains exist)', HELPDESK_TEXT_DOMAIN )
                ) );
                return;
            }
        } else {
            // If unsetting as main, check if there are other employees to become main
            $remaining = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table 
                    WHERE projekt_id = %d AND pracovnik_id != %d",
                    $project_id,
                    $employee_id
                )
            );

            if ( $remaining > 0 ) {
                // Set first remaining as main
                $first_remaining = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT pracovnik_id FROM $table 
                        WHERE projekt_id = %d AND pracovnik_id != %d LIMIT 1",
                        $project_id,
                        $employee_id
                    )
                );

                if ( $first_remaining ) {
                    $wpdb->update(
                        $table,
                        array( 'is_hlavny' => 1 ),
                        array( 'projekt_id' => $project_id, 'pracovnik_id' => $first_remaining ),
                        array( '%d' ),
                        array( '%d', '%d' )
                    );
                }
            }
        }

        wp_send_json_success( array(
            'message' => __( 'Status updated successfully', HELPDESK_TEXT_DOMAIN )
        ) );
    }

    /**
     * Handle export projects to CSV
     */
    public function handle_export_projects() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $projects = Project::get_all();

        if ( empty( $projects ) ) {
            wp_send_json_error( array( 'message' => __( 'No projects to export', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Prepare CSV data - iba zakaznicke_cislo (bez ID)
        $csv_data = [];
        foreach ( $projects as $proj ) {
            $csv_data[] = array(
                $proj['zakaznicke_cislo'],
            );
        }

        // Generate CSV content
        require_once HELPDESK_PLUGIN_DIR . 'includes/utils/class-csv.php';
        $csv_content = \HelpDesk\Utils\CSV::generate(
            $csv_data,
            array( 'Zákaznícke Číslo' )
        );

        $filename = 'projekty_' . date( 'Y-m-d_H-i-s' ) . '.csv';

        wp_send_json_success( array(
            'content' => $csv_content,
            'filename' => $filename,
        ) );
    }

    /**
     * Handle import projects from CSV
     */
    public function handle_import_projects() {
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

        // Parse CSV manually with ; delimiter
        $lines = preg_split( '/\r\n|\r|\n/', trim( $content ) );
        $lines = array_filter( $lines );

        if ( count( $lines ) < 2 ) {
            wp_send_json_error( array( 'message' => __( 'CSV file is empty or has no data rows', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Parse header
        $header_line = array_shift( $lines );
        $headers = str_getcsv( $header_line, ';', '"' );
        $headers = array_map( 'trim', $headers );

        $imported = 0;
        $errors = [];

        foreach ( $lines as $index => $line ) {
            $line_number = $index + 2;
            $values = str_getcsv( $line, ';', '"' );
            $values = array_map( 'trim', $values );

            if ( empty( $values[0] ) ) {
                continue; // Skip empty rows
            }

            // Get zakaznicke_cislo from first column
            $zakaznicke_cislo = $values[0];

            if ( empty( $zakaznicke_cislo ) ) {
                $errors[] = sprintf(
                    __( 'Line %d: Empty value', HELPDESK_TEXT_DOMAIN ),
                    $line_number
                );
                continue;
            }

            // Check if project exists
            global $wpdb;
            $table = Database::get_projects_table();
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE zakaznicke_cislo = %s",
                    $zakaznicke_cislo
                )
            );

            if ( $exists ) {
                $errors[] = sprintf(
                    __( 'Line %d: Project %s already exists', HELPDESK_TEXT_DOMAIN ),
                    $line_number,
                    $zakaznicke_cislo
                );
                continue;
            }

            // Create new project
            $data = array( 'zakaznicke_cislo' => $zakaznicke_cislo );
            $project = new Project();
            $project_id = $project->create( $data );

            if ( $project_id ) {
                $imported++;
            } else {
                $errors[] = sprintf(
                    __( 'Line %d: Failed to create', HELPDESK_TEXT_DOMAIN ),
                    $line_number
                );
            }
        }

        $message = sprintf(
            __( 'Imported %d projects', HELPDESK_TEXT_DOMAIN ),
            $imported
        );

        if ( ! $errors || $imported > 0 ) {
            wp_send_json_success( array(
                'message' => $message,
                'warnings' => $errors,
            ) );
        } else {
            wp_send_json_error( array(
                'message' => $message,
                'warnings' => $errors,
            ) );
        }
    }

    /**
     * Handle search projects AJAX
     */
    public function handle_search_projects() {
        // Security: nonce, capability, rate limit
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        // Get search term safely
        $search_term = \HelpDesk\Utils\Security::get_post_param( 'search', '', 'text' );

        if ( empty( $search_term ) ) {
            wp_send_json_success( array( 'projects' => array() ) );
            wp_die();
        }

        // Get filter settings
        $dashboard_filters = get_option( 'helpdesk_dashboard_filters', array(
            'show_nw_projects' => false,
        ) );
        $show_nw_projects = (bool) $dashboard_filters['show_nw_projects'];

        global $wpdb;
        $table = Database::get_projects_table();
        $employees_table = Database::get_employees_table();
        $project_employee_table = Database::get_project_employee_table();
        $positions_table = Database::get_positions_table();
        $standby_table = Database::get_standby_table();
        $vacations_table = Database::get_vacations_table();
        $like = '%' . $wpdb->esc_like( $search_term ) . '%';
        $today = current_time( 'Y-m-d' );

        // Build query with filter
        $sql = "SELECT * FROM {$table} WHERE zakaznicke_cislo LIKE %s";
        $sql_params = array( $like );

        // Apply -nw filter if needed
        if ( ! $show_nw_projects ) {
            $sql .= " AND zakaznicke_cislo NOT LIKE %s";
            $sql_params[] = '%-nw%';
        }

        $sql .= " ORDER BY zakaznicke_cislo ASC LIMIT 20";

        // Search v zakaznicke_cislo
        $projects = $wpdb->get_results(
            $wpdb->prepare( $sql, $sql_params ),
            ARRAY_A
        );

        // Add employees for each project
        if ( $projects ) {
            foreach ( $projects as &$project ) {
                // Get project employees (from project_employee table)
                $project_employees_sql = "SELECT e.id, e.meno_priezvisko, e.klapka, e.mobil, e.poznamka, e.pozicia_id, 0 as pozicia_nazov, MAX(CASE WHEN v.nepritomnost_od <= %s AND v.nepritomnost_do >= %s THEN v.nepritomnost_od END) as nepritomnost_od, MAX(CASE WHEN v.nepritomnost_od <= %s AND v.nepritomnost_do >= %s THEN v.nepritomnost_do END) as nepritomnost_do, pp.is_hlavny, 'project' as emp_type
                         FROM {$employees_table} e
                         INNER JOIN `{$project_employee_table}` pp ON e.id = pp.pracovnik_id
                         LEFT JOIN {$vacations_table} v ON e.id = v.pracovnik_id
                         WHERE pp.projekt_id = %d
                         GROUP BY e.id
                         ORDER BY pp.is_hlavny DESC, e.meno_priezvisko ASC";
                
                $project_employees = $wpdb->get_results(
                    $wpdb->prepare(
                        $project_employees_sql,
                        $today,
                        $today,
                        $today,
                        $today,
                        $project['id']
                    ),
                    ARRAY_A
                );

                // Get standby employees (from standby table) - only those with active standby
                // Use subquery to get only the LATEST standby record for each employee
                $standby_employees_sql = "SELECT e.id, e.meno_priezvisko, e.klapka, e.mobil, e.poznamka, e.pozicia_id, 0 as pozicia_nazov, MAX(CASE WHEN v.nepritomnost_od <= %s AND v.nepritomnost_do >= %s THEN v.nepritomnost_od END) as nepritomnost_od, MAX(CASE WHEN v.nepritomnost_od <= %s AND v.nepritomnost_do >= %s THEN v.nepritomnost_do END) as nepritomnost_do, 0 as is_hlavny, 'standby' as emp_type, MAX(s.pohotovost_od) as pohotovost_od, MAX(s.pohotovost_do) as pohotovost_do
                         FROM {$employees_table} e
                         INNER JOIN (
                            SELECT pracovnik_id, projekt_id, pohotovost_od, pohotovost_do, je_aktivna
                            FROM {$standby_table}
                            WHERE id IN (
                               SELECT MAX(id) 
                               FROM {$standby_table}
                               WHERE projekt_id = %d AND je_aktivna = 1
                               GROUP BY pracovnik_id
                            )
                         ) s ON e.id = s.pracovnik_id AND s.projekt_id = %d
                         LEFT JOIN {$vacations_table} v ON e.id = v.pracovnik_id
                         GROUP BY e.id
                         ORDER BY e.meno_priezvisko ASC";
                
                $standby_employees = $wpdb->get_results(
                    $wpdb->prepare(
                        $standby_employees_sql,
                        $today,
                        $today,
                        $today,
                        $today,
                        $project['id'],
                        $project['id']
                    ),
                    ARRAY_A
                );

                // Merge employees - project employees first, then add standby employees that are not already in project
                $all_employees = $project_employees ? $project_employees : array();
                if ( $standby_employees ) {
                    $project_emp_ids = wp_list_pluck( $project_employees ? $project_employees : array(), 'id' );
                    foreach ( $standby_employees as $standby_emp ) {
                        if ( ! in_array( $standby_emp['id'], $project_emp_ids, true ) ) {
                            $all_employees[] = $standby_emp;
                        }
                    }
                }

                // Detektuj rozdiely v diacritike
                $diacritics_warnings = array();
                if ( ! empty( $all_employees ) ) {
                    require_once HELPDESK_PLUGIN_DIR . 'includes/utils/class-name-matcher.php';
                    
                    // Zber všetkých variantov mien bez diacritiky
                    foreach ( $all_employees as $emp ) {
                        if ( ! empty( $emp['meno_priezvisko'] ) ) {
                            $normalized = \NameMatcher::normalize( $emp['meno_priezvisko'] );
                            
                            // Hľadaj iné varianty v rovnakom projekte
                            $other_variants = array();
                            foreach ( $all_employees as $other_emp ) {
                                if ( $other_emp['id'] !== $emp['id'] && ! empty( $other_emp['meno_priezvisko'] ) ) {
                                    $other_norm = \NameMatcher::normalize( $other_emp['meno_priezvisko'] );
                                    if ( $other_norm === $normalized && $other_emp['meno_priezvisko'] !== $emp['meno_priezvisko'] ) {
                                        $other_variants[] = $other_emp['meno_priezvisko'];
                                    }
                                }
                            }
                            
                            // Ak existujú iné varianty s rozlišným pravopisom
                            if ( ! empty( $other_variants ) ) {
                                $all_variants = array_merge( array( $emp['meno_priezvisko'] ), $other_variants );
                                $all_variants = array_unique( $all_variants );
                                
                                $warning_key = $normalized;
                                if ( ! isset( $diacritics_warnings[ $warning_key ] ) ) {
                                    $diacritics_warnings[ $warning_key ] = array(
                                        'normalized_name' => $normalized,
                                        'variants' => $all_variants,
                                    );
                                }
                            }
                        }
                    }
                }
                
                $project['diacritics_warnings'] = array_values( $diacritics_warnings );
                $project['employees'] = $all_employees;
            }
        }

        // Return escaped response
        wp_send_json_success( array( 'projects' => \HelpDesk\Utils\Security::escape_response( $projects ? $projects : array() ) ) );
    }

    /**
     * Synchronizuj mená zamestnancov v projekte
     * Nájde všetkých zamestnancov s rovnakým menom bez diacritiky a aktualizuje ich na správny variant
     */
    public function handle_sync_employee_names() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $project_id = isset( $_POST['project_id'] ) ? absint( $_POST['project_id'] ) : 0;
        $correct_name = isset( $_POST['correct_name'] ) ? sanitize_text_field( $_POST['correct_name'] ) : '';

        if ( ! $project_id || empty( $correct_name ) ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid project ID or employee name', HELPDESK_TEXT_DOMAIN )
            ) );
        }

        require_once HELPDESK_PLUGIN_DIR . 'includes/utils/class-name-matcher.php';

        global $wpdb;
        $employees_table = Database::get_employees_table();
        
        // Nájdi všetkých zamestnancov s rovnakým menom bez diacritiky
        $all_employees = $wpdb->get_results(
            "SELECT id, meno_priezvisko FROM {$employees_table} ORDER BY meno_priezvisko",
            ARRAY_A
        );

        $normalized_correct = \NameMatcher::normalize( $correct_name );
        $matching_employees = array();
        
        foreach ( $all_employees as $emp ) {
            $normalized_emp = \NameMatcher::normalize( $emp['meno_priezvisko'] );
            if ( $normalized_emp === $normalized_correct ) {
                $matching_employees[] = $emp;
            }
        }

        if ( empty( $matching_employees ) ) {
            wp_send_json_error( array(
                'message' => __( 'No matching employees found', HELPDESK_TEXT_DOMAIN )
            ) );
        }

        // Aktualizuj všetkých zamestnancov na správny variant
        $updated_count = 0;
        foreach ( $matching_employees as $emp ) {
            if ( $emp['meno_priezvisko'] !== $correct_name ) {
                $result = $wpdb->update(
                    $employees_table,
                    array( 'meno_priezvisko' => $correct_name ),
                    array( 'id' => $emp['id'] ),
                    array( '%s' ),
                    array( '%d' )
                );
                
                if ( $result !== false ) {
                    $updated_count++;
                    error_log( sprintf(
                        'Synchronizácia mien: ID %d - "%s" → "%s"',
                        $emp['id'],
                        $emp['meno_priezvisko'],
                        $correct_name
                    ) );
                }
            }
        }

        wp_send_json_success( array(
            'message' => sprintf( __( 'Updated %d employees', HELPDESK_TEXT_DOMAIN ), $updated_count ),
            'updated_count' => $updated_count,
            'total_found' => count( $matching_employees ),
        ) );
    }
}

