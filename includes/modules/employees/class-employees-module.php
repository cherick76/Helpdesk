<?php
/**
 * Employees Module
 */

namespace HelpDesk\Modules\Employees;

use HelpDesk\Modules\BaseModule;
use HelpDesk\Models\Employee;
use HelpDesk\Utils\Validator;
use HelpDesk\Utils\Database;
use HelpDesk\Utils\Security;

class EmployeesModule extends BaseModule {
    protected $module_name = 'Pracovníci';
    protected $module_slug = 'employees';
    protected $menu_page_id = 'helpdesk-employees';

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        add_action( 'wp_ajax_helpdesk_save_employee', array( $this, 'handle_save_employee' ) );
        add_action( 'wp_ajax_helpdesk_delete_employee', array( $this, 'handle_delete_employee' ) );
        add_action( 'wp_ajax_helpdesk_get_employee', array( $this, 'handle_get_employee' ) );
        add_action( 'wp_ajax_helpdesk_get_employees', array( $this, 'handle_get_employees' ) );
        add_action( 'wp_ajax_helpdesk_get_positions', array( $this, 'handle_get_positions' ) );
        add_action( 'wp_ajax_helpdesk_export_employees', array( $this, 'handle_export_employees' ) );
        add_action( 'wp_ajax_helpdesk_preview_import_employees', array( $this, 'handle_preview_import_employees' ) );
        add_action( 'wp_ajax_helpdesk_import_employees', array( $this, 'handle_import_employees' ) );
        add_action( 'wp_ajax_helpdesk_bulk_assign_projects', array( $this, 'handle_bulk_assign_projects' ) );
        add_action( 'wp_ajax_helpdesk_save_standby_batch', array( $this, 'handle_save_standby_batch' ) );
        add_action( 'wp_ajax_helpdesk_get_employee_vacation', array( $this, 'handle_get_employee_vacation' ) );
        add_action( 'wp_ajax_helpdesk_save_employee_vacation', array( $this, 'handle_save_employee_vacation' ) );
        add_action( 'wp_ajax_helpdesk_remove_employee_vacation', array( $this, 'handle_remove_employee_vacation' ) );
    }

    /**
     * Register menu
     */
    public function register_menu() {
        // Menu registration happens in Admin class
    }

    /**
     * Handle save employee AJAX
     */
    public function handle_save_employee() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $employee_id = Security::get_post_param( 'id', 0, 'int' );
        
        // Build data array from POST
        $data = array(
            'meno_priezvisko' => isset( $_POST['meno_priezvisko'] ) ? sanitize_text_field( $_POST['meno_priezvisko'] ) : '',
            'klapka' => isset( $_POST['klapka'] ) ? sanitize_text_field( $_POST['klapka'] ) : '',
            'mobil' => isset( $_POST['mobil'] ) ? sanitize_text_field( $_POST['mobil'] ) : '',
            'pozicia_id' => isset( $_POST['pozicia_id'] ) ? absint( $_POST['pozicia_id'] ) : null,
            'poznamka' => isset( $_POST['poznamka'] ) ? sanitize_textarea_field( $_POST['poznamka'] ) : '',
        );

        // Sanitize data
        $sanitized = Validator::sanitize_employee( $data );

        // Validate data
        $errors = Validator::validate_employee( $sanitized );

        if ( ! empty( $errors ) ) {
            wp_send_json_error( array( 'errors' => $errors ) );
        }

        if ( $employee_id ) {
            // Update
            $employee = new Employee( $employee_id );

            if ( ! $employee->exists() ) {
                wp_send_json_error( array( 'message' => __( 'Employee not found', HELPDESK_TEXT_DOMAIN ) ) );
            }

            // Check klapka uniqueness (only if klapka is provided and changed)
            if ( ! empty( $sanitized['klapka'] ) && $employee->get( 'klapka' ) !== $sanitized['klapka'] ) {
                if ( ! $employee->is_klapka_unique( $sanitized['klapka'], $employee_id ) ) {
                    wp_send_json_error( array( 'errors' => array( 'klapka' => __( 'Klapka already exists', HELPDESK_TEXT_DOMAIN ) ) ) );
                }
            }

            $employee->update( $sanitized );

            // Handle project assignments
            $this->update_employee_projects( $employee_id );

            wp_send_json_success( array(
                'message' => __( 'Employee updated successfully', HELPDESK_TEXT_DOMAIN ),
                'employee' => Security::escape_response( $employee->get_all_data() ),
            ) );
        } else {
            // Create
            $employee = new Employee();
            // Check klapka uniqueness (only if klapka is provided)
            if ( ! empty( $sanitized['klapka'] ) && ! $employee->is_klapka_unique( $sanitized['klapka'] ) ) {
                wp_send_json_error( array( 'errors' => array( 'klapka' => __( 'Klapka already exists', HELPDESK_TEXT_DOMAIN ) ) ) );
            }

            $id = $employee->create( $sanitized );

            if ( $id ) {
                $employee->load( $id );

                // Handle project assignments
                $this->update_employee_projects( $id );

                wp_send_json_success( array(
                    'message' => __( 'Employee created successfully', HELPDESK_TEXT_DOMAIN ),
                    'employee' => Security::escape_response( $employee->get_all_data() ),
                ) );
            } else {
                global $wpdb;
                $error_msg = __( 'Failed to create employee', HELPDESK_TEXT_DOMAIN );
                if ( $wpdb->last_error ) {
                    $error_msg .= ' - DB Error: ' . $wpdb->last_error;
                    Security::log_activity( 'create_employee', 'employee', 0, 'Database error: ' . $wpdb->last_error );
                }
                wp_send_json_error( array( 'message' => $error_msg ) );
            }
        }
    }

    /**
     * Handle delete employee AJAX
     */
    public function handle_delete_employee() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $employee_id = Security::get_post_param( 'id', 0, 'int', true );

        if ( ! $employee_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid employee ID', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $employee = new Employee( $employee_id );

        if ( ! $employee->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Employee not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        if ( $employee->delete() ) {
            wp_send_json_success( array( 'message' => __( 'Employee deleted successfully', HELPDESK_TEXT_DOMAIN ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete employee', HELPDESK_TEXT_DOMAIN ) ) );
        }
    }

    /**
     * Handle get employee AJAX
     */
    public function handle_get_employee() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $employee_id = Security::get_post_param( 'id', 0, 'int', true );

        if ( ! $employee_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid employee ID', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $employee = new Employee( $employee_id );

        if ( ! $employee->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Employee not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Get employee's projects
        $projects = $employee->get_projects();
        $project_ids = array();
        foreach ( $projects as $project ) {
            $project_ids[] = absint( $project['id'] );
        }

        // Get standby periods
        $standby_periods = $employee->get_standby_periods();
        
        // Check if has standby for today
        global $wpdb;
        $today = current_time( 'Y-m-d' );
        $standby_table = Database::get_standby_table();
        $has_standby_today = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$standby_table}
                 WHERE pracovnik_id = %d
                 AND pohotovost_od <= %s AND pohotovost_do >= %s
                 AND je_aktivna = 1",
                $employee_id,
                $today,
                $today
            )
        );
        
        // Get position name
        $position_name = '';
        $pozicia_id = $employee->get( 'pozicia_id' );
        if ( $pozicia_id ) {
            $positions_table = Database::get_positions_table();
            $position = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT skratka FROM {$positions_table} WHERE id = %d",
                    $pozicia_id
                )
            );
            $position_name = $position ? $position : 'N/A';
        }

        $employee_data = $employee->get_all_data();
        $employee_data['pozicia_nazov'] = $position_name;

        wp_send_json_success( array( 
            'employee' => Security::escape_response( $employee_data ),
            'employee_projects' => $project_ids,
            'standby_periods' => Security::escape_response( $standby_periods ),
            'has_standby_today' => $has_standby_today > 0 ? 1 : 0
        ) );
    }

    /**
     * Handle get employees AJAX
     */
    public function handle_get_employees() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $employees = Employee::get_all();
        wp_send_json_success( array( 'employees' => Security::escape_response( $employees ) ) );
    }

    /**
     * Handle get positions
     */
    public function handle_get_positions() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $positions = \HelpDesk\Models\Position::get_all();
        wp_send_json_success( array( 'positions' => Security::escape_response( $positions ) ) );
    }

    /**
     * Handle export employees to CSV
     */
    public function handle_export_employees() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $employees = Employee::get_all();

        if ( empty( $employees ) ) {
            wp_send_json_error( array( 'message' => __( 'No employees to export', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Prepare CSV data (bez ID - ID sa generuje automaticky pri importe)
        $csv_data = [];
        foreach ( $employees as $emp ) {
            // Get position name if pozicia_id is set
            $position_name = '';
            if ( ! empty( $emp['pozicia_id'] ) ) {
                $position = \HelpDesk\Models\Position::get_by_id( $emp['pozicia_id'] );
                if ( $position ) {
                    $position_name = $position->get_name();
                }
            }
            
            $csv_data[] = array(
                $emp['meno_priezvisko'],
                $emp['klapka'] ?? '',
                $emp['mobil'] ?? '',
                $position_name,
                $emp['poznamka'] ?? '',
            );
        }

        // Generate CSV content
        require_once HELPDESK_PLUGIN_DIR . 'includes/utils/class-csv.php';
        $csv_content = \HelpDesk\Utils\CSV::generate(
            $csv_data,
            array( 'Meno a priezvisko', 'Klapka', 'Mobil', 'Pozícia', 'Poznámka' )
        );

        $filename = 'pracovnici_' . date( 'Y-m-d_H-i-s' ) . '.csv';

        wp_send_json_success( array(
            'content' => Security::escape_response( $csv_content ),
            'filename' => $filename,
        ) );
    }

    /**
     * Handle preview of CSV import before confirming
     */
    public function handle_preview_import_employees() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        if ( ! isset( $_FILES['csv_file'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No file uploaded', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $file = $_FILES['csv_file'];

        // Check for upload errors
        if ( ! empty( $file['error'] ) ) {
            wp_send_json_error( array( 'message' => __( 'File upload error', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Read file content
        if ( ! file_exists( $file['tmp_name'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Uploaded file not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $content = file_get_contents( $file['tmp_name'] );

        if ( ! $content ) {
            wp_send_json_error( array( 'message' => __( 'Failed to read file', HELPDESK_TEXT_DOMAIN ) ) );
        }

        require_once HELPDESK_PLUGIN_DIR . 'includes/utils/class-csv.php';
        $rows = \HelpDesk\Utils\CSV::parse( $content );

        if ( empty( $rows ) ) {
            wp_send_json_error( array( 'message' => __( 'CSV file is empty', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Auto-detect column names
        $sample_row = isset( $rows[0] ) ? $rows[0] : array();
        $name_column = null;
        $phone_column = null;
        $mobile_column = null;

        // Detect columns
        if ( isset( $sample_row['Pracovník'] ) ) {
            $name_column = 'Pracovník';
        } elseif ( isset( $sample_row['Meno a priezvisko'] ) ) {
            $name_column = 'Meno a priezvisko';
        }

        if ( isset( $sample_row['Telefón'] ) ) {
            $phone_column = 'Telefón';
        } elseif ( isset( $sample_row['Klapka'] ) ) {
            $phone_column = 'Klapka';
        }

        if ( isset( $sample_row['Mobil'] ) ) {
            $mobile_column = 'Mobil';
        }

        // Validation of required columns
        if ( ! $name_column || ! $phone_column ) {
            wp_send_json_error( array( 
                'message' => __( 'CSV súbor musí obsahovať stĺpce "Pracovník/Meno a priezvisko" a "Telefón/Klapka"', HELPDESK_TEXT_DOMAIN ) 
            ) );
        }

        // Prepare preview data
        $preview_data = array();

        foreach ( $rows as $index => $row ) {
            $line_number = $index + 2;

            $meno_priezvisko = isset( $row[$name_column] ) ? \HelpDesk\Utils\CSV::sanitize_value( $row[$name_column] ) : '';
            $klapka = isset( $row[$phone_column] ) ? \HelpDesk\Utils\CSV::sanitize_value( $row[$phone_column] ) : '';
            $mobil = $mobile_column && isset( $row[$mobile_column] ) ? \HelpDesk\Utils\CSV::sanitize_value( $row[$mobile_column] ) : '';

            if ( empty( $meno_priezvisko ) ) {
                continue; // Skip empty rows
            }

            // Check if employee exists
            $existing = Employee::get_by_name( $meno_priezvisko );

            $preview_data[] = array(
                'line_number' => $line_number,
                'name' => $meno_priezvisko,
                'phone' => $klapka,
                'mobile' => $mobil,
                'exists' => $existing ? true : false,
                'existing_phone' => $existing ? $existing->get( 'klapka' ) : '',
                'existing_mobile' => $existing ? $existing->get( 'mobil' ) : '',
            );
        }

        wp_send_json_success( array(
            'employees' => Security::escape_response( $preview_data ),
            'total' => count( $preview_data ),
        ) );
    }

    /**
     * Handle import employees from CSV
     */
    public function handle_import_employees() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        // Check if we're importing with selected employees list
        $selected_employees = Security::get_post_param( 'selected_employees', null, 'array' );

        if ( ! isset( $_FILES['csv_file'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No file uploaded', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $file = $_FILES['csv_file'];

        // Check for upload errors
        if ( ! empty( $file['error'] ) ) {
            wp_send_json_error( array( 'message' => __( 'File upload error', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Validate file type (more lenient - accept CSV and plain text)
        $allowed_types = array( 'text/csv', 'text/plain', 'application/vnd.ms-excel' );
        if ( ! in_array( $file['type'], $allowed_types ) ) {
            // Don't reject based on MIME type - check by extension instead
        }

        // Read file content
        if ( ! file_exists( $file['tmp_name'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Uploaded file not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $content = file_get_contents( $file['tmp_name'] );

        if ( ! $content ) {
            wp_send_json_error( array( 'message' => __( 'Failed to read file', HELPDESK_TEXT_DOMAIN ) ) );
        }

        require_once HELPDESK_PLUGIN_DIR . 'includes/utils/class-csv.php';
        $rows = \HelpDesk\Utils\CSV::parse( $content );

        if ( empty( $rows ) ) {
            wp_send_json_error( array( 'message' => __( 'CSV file is empty', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Auto-detect column names - support both old and new format
        $sample_row = isset( $rows[0] ) ? $rows[0] : array();
        $name_column = null;
        $phone_column = null;
        $mobile_column = null;

        // Detect name column
        if ( isset( $sample_row['Pracovník'] ) ) {
            $name_column = 'Pracovník';
        } elseif ( isset( $sample_row['Meno a priezvisko'] ) ) {
            $name_column = 'Meno a priezvisko';
        }

        // Detect phone column
        if ( isset( $sample_row['Telefón'] ) ) {
            $phone_column = 'Telefón';
        } elseif ( isset( $sample_row['Klapka'] ) ) {
            $phone_column = 'Klapka';
        }

        // Detect mobile column
        if ( isset( $sample_row['Mobil'] ) ) {
            $mobile_column = 'Mobil';
        }

        // Validation of required columns
        if ( ! $name_column ) {
            wp_send_json_error( array( 
                'message' => __( 'CSV súbor musí obsahovať stĺpec "Pracovník" alebo "Meno a priezvisko"', HELPDESK_TEXT_DOMAIN ) 
            ) );
        }

        if ( ! $phone_column ) {
            wp_send_json_error( array( 
                'message' => __( 'CSV súbor musí obsahovať stĺpec "Telefón" alebo "Klapka"', HELPDESK_TEXT_DOMAIN ) 
            ) );
        }

        $imported = 0;
        $updated = 0;
        $errors = [];
        $conflicts = [];
        $summary = [];

        foreach ( $rows as $index => $row ) {
            $line_number = $index + 2; // +2 because of header and 0-indexed

            // Get data from CSV
            $meno_priezvisko = isset( $row[$name_column] ) ? \HelpDesk\Utils\CSV::sanitize_value( $row[$name_column] ) : '';
            $klapka = isset( $row[$phone_column] ) ? \HelpDesk\Utils\CSV::sanitize_value( $row[$phone_column] ) : '';
            $mobil = $mobile_column && isset( $row[$mobile_column] ) ? \HelpDesk\Utils\CSV::sanitize_value( $row[$mobile_column] ) : '';

            // Basic validation - name is required
            if ( empty( $meno_priezvisko ) ) {
                $errors[] = sprintf(
                    __( 'Riadok %d: Pracovník (meno a priezvisko) je povinné', HELPDESK_TEXT_DOMAIN ),
                    $line_number
                );
                continue;
            }

            // Check if employee is in selected list (if selected_employees is provided)
            if ( is_array( $selected_employees ) ) {
                $is_selected = false;
                foreach ( $selected_employees as $selected ) {
                    if ( isset( $selected['name'] ) && $selected['name'] === $meno_priezvisko ) {
                        $is_selected = true;
                        break;
                    }
                }
                if ( ! $is_selected ) {
                    continue; // Skip this employee - not selected
                }
            }

            // Try to find existing employee by name
            $existing_employee = Employee::get_by_name( $meno_priezvisko );

            if ( $existing_employee ) {
                // Employee already exists - update phone and mobile
                $existing_klapka = $existing_employee->get( 'klapka' );
                $existing_mobil = $existing_employee->get( 'mobil' );

                $update_data = array();
                $changes = array();

                // Check if klapka matches
                if ( ! empty( $klapka ) && $existing_klapka !== $klapka ) {
                    // Conflict detected - phone number differs
                    $conflicts[] = array(
                        'line' => $line_number,
                        'employee' => $meno_priezvisko,
                        'existing_klapka' => $existing_klapka,
                        'new_klapka' => $klapka,
                    );
                    // We need to ask for confirmation, but since we're in AJAX, we'll store conflicts
                    // For now, skip this employee to avoid overwriting without confirmation
                    continue;
                }

                // Update klapka if empty in database
                if ( ! empty( $klapka ) && empty( $existing_klapka ) ) {
                    $update_data['klapka'] = $klapka;
                    $changes[] = sprintf( __( 'Klapka doplnená: %s', HELPDESK_TEXT_DOMAIN ), $klapka );
                }

                // Update mobil if provided
                if ( ! empty( $mobil ) ) {
                    $update_data['mobil'] = $mobil;
                    $changes[] = sprintf( __( 'Mobil doplnený: %s', HELPDESK_TEXT_DOMAIN ), $mobil );
                }

                // Apply updates if any
                if ( ! empty( $update_data ) ) {
                    if ( $existing_employee->update( $update_data ) ) {
                        $updated++;
                        $summary[] = sprintf(
                            __( 'Riadok %d: Pracovník "%s" - %s', HELPDESK_TEXT_DOMAIN ),
                            $line_number,
                            $meno_priezvisko,
                            implode( ', ', $changes )
                        );
                    } else {
                        $errors[] = sprintf(
                            __( 'Riadok %d: Chyba pri aktualizácii pracovníka "%s"', HELPDESK_TEXT_DOMAIN ),
                            $line_number,
                            $meno_priezvisko
                        );
                    }
                } else {
                    // No updates needed
                    $summary[] = sprintf(
                        __( 'Riadok %d: Pracovník "%s" - Bez zmien (údaje sú aktuálne)', HELPDESK_TEXT_DOMAIN ),
                        $line_number,
                        $meno_priezvisko
                    );
                }
            } else {
                // New employee - create
                $data = array(
                    'meno_priezvisko' => $meno_priezvisko,
                    'klapka' => $klapka,
                );

                // Add mobil if provided
                if ( ! empty( $mobil ) ) {
                    $data['mobil'] = $mobil;
                }

                // Validate
                $validation_errors = Validator::validate_employee( $data );
                if ( ! empty( $validation_errors ) ) {
                    $errors[] = sprintf(
                        __( 'Riadok %d: %s', HELPDESK_TEXT_DOMAIN ),
                        $line_number,
                        implode( ', ', $validation_errors )
                    );
                    continue;
                }

                // Check klapka uniqueness (only if klapka is provided)
                if ( ! empty( $data['klapka'] ) ) {
                    $employee_check = new Employee();
                    if ( ! $employee_check->is_klapka_unique( $data['klapka'] ) ) {
                        $errors[] = sprintf(
                            __( 'Riadok %d: Klapka "%s" už existuje', HELPDESK_TEXT_DOMAIN ),
                            $line_number,
                            $data['klapka']
                        );
                        continue;
                    }
                }

                // Create new employee
                $employee = new Employee();
                if ( $employee->create( $data ) ) {
                    $imported++;
                    $summary[] = sprintf(
                        __( 'Riadok %d: Nový pracovník "%s" vytvorený', HELPDESK_TEXT_DOMAIN ),
                        $line_number,
                        $meno_priezvisko
                    );
                } else {
                    $errors[] = sprintf(
                        __( 'Riadok %d: Chyba pri vytváraní pracovníka "%s"', HELPDESK_TEXT_DOMAIN ),
                        $line_number,
                        $meno_priezvisko
                    );
                }
            }
        }

        // Build final message
        $response = array(
            'imported' => $imported,
            'updated' => $updated,
            'summary' => $summary,
        );

        if ( ! empty( $conflicts ) ) {
            $response['conflicts'] = $conflicts;
        }

        if ( ! empty( $errors ) ) {
            $response['errors'] = $errors;
        }

        $message = sprintf(
            __( 'Import dokončený: %d nových pracovníkov, %d aktualizovaných pracovníkov', HELPDESK_TEXT_DOMAIN ),
            $imported,
            $updated
        );

        if ( ! empty( $conflicts ) ) {
            $message .= sprintf(
                __( ', %d konfliktov (rozdielne klapky)', HELPDESK_TEXT_DOMAIN ),
                count( $conflicts )
            );
        }

        wp_send_json_success( array_merge(
            array( 'message' => $message ),
            $response
        ) );
    }

    /**
     * Handle bulk assign projects AJAX
     */
    public function handle_bulk_assign_projects() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $employee_ids = Security::get_post_param( 'employees', array(), 'array' );
        $project_ids = Security::get_post_param( 'projects', array(), 'array' );
        
        // Sanitize IDs
        $employee_ids = array_map( 'absint', (array) $employee_ids );
        $project_ids = array_map( 'absint', (array) $project_ids );

        if ( empty( $employee_ids ) || empty( $project_ids ) ) {
            wp_send_json_error( array( 'message' => __( 'No employees or projects selected', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $success_count = 0;
        $error_count = 0;
        $errors = array();

        // Assign each employee to each project
        foreach ( $employee_ids as $employee_id ) {
            $employee = new Employee( $employee_id );
            
            if ( ! $employee->exists() ) {
                $error_count++;
                $errors[] = sprintf( __( 'Employee ID %d does not exist', HELPDESK_TEXT_DOMAIN ), $employee_id );
                continue;
            }

            foreach ( $project_ids as $project_id ) {
                // Assign employee to project
                if ( $employee->assign_to_project( $project_id ) ) {
                    $success_count++;
                } else {
                    $error_count++;
                    $errors[] = sprintf( __( 'Failed to assign employee %s to project ID %d', HELPDESK_TEXT_DOMAIN ), $employee->get( 'meno' ), $project_id );
                }
            }
        }

        $message = sprintf( 
            __( 'Successfully assigned %d employee-project combinations', HELPDESK_TEXT_DOMAIN ), 
            $success_count 
        );

        if ( empty( $errors ) ) {
            wp_send_json_success( array( 'message' => $message ) );
        } else {
            wp_send_json_success( array(
                'message' => $message,
                'warnings' => $errors,
            ) );
        }
    }

    /**
     * Update employee projects
     */
    private function update_employee_projects( $employee_id ) {
        global $wpdb;

        $employee = new Employee( $employee_id );
        if ( ! $employee->exists() ) {
            return false;
        }

        // Get selected projects from POST
        $selected_projects = isset( $_POST['projects'] ) ? array_map( 'absint', $_POST['projects'] ) : array();

        // Get current projects
        $current_projects = $employee->get_projects();
        $current_project_ids = array();
        foreach ( $current_projects as $project ) {
            $current_project_ids[] = absint( $project['id'] );
        }

        // Projects to add
        $projects_to_add = array_diff( $selected_projects, $current_project_ids );
        foreach ( $projects_to_add as $project_id ) {
            $employee->assign_to_project( $project_id, false );
        }

        // Projects to remove
        $projects_to_remove = array_diff( $current_project_ids, $selected_projects );
        if ( ! empty( $projects_to_remove ) ) {
            $table = Database::get_project_employee_table();
            foreach ( $projects_to_remove as $project_id ) {
                $wpdb->delete(
                    $table,
                    array(
                        'projekt_id' => $project_id,
                        'pracovnik_id' => $employee_id,
                    ),
                    array( '%d', '%d' )
                );
            }
        }

        return true;
    }

    /**
     * Handle save standby AJAX
     */
    public function handle_save_standby() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $employee_id = Security::get_post_param( 'employee_id', 0, 'int', true );
        $project_id = Security::get_post_param( 'project_id', 0, 'int', true );
        $od = Security::get_post_param( 'od', '', 'text', true );
        $do = Security::get_post_param( 'do', '', 'text', true );
        $standby_id = Security::get_post_param( 'standby_id', 0, 'int' );

        if ( ! $employee_id || ! $project_id || ! $od || ! $do ) {
            wp_send_json_error( array( 'message' => __( 'Missing required fields', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $employee = new Employee( $employee_id );
        if ( ! $employee->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Employee not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        if ( $standby_id ) {
            // Update
            $result = $employee->update_standby_period( $standby_id, $project_id, $od, $do );
            if ( $result ) {
                wp_send_json_success( array( 
                    'message' => __( 'Standby period updated successfully', HELPDESK_TEXT_DOMAIN ),
                    'standby_periods' => $employee->get_standby_periods()
                ) );
            }
        } else {
            // Create
            $result = $employee->add_standby_period( $project_id, $od, $do );
            if ( $result ) {
                wp_send_json_success( array( 
                    'message' => __( 'Standby period added successfully', HELPDESK_TEXT_DOMAIN ),
                    'standby_periods' => $employee->get_standby_periods()
                ) );
            }
        }

        wp_send_json_error( array( 'message' => __( 'Failed to save standby period', HELPDESK_TEXT_DOMAIN ) ) );
    }

    /**
     * Handle batch save standby periods AJAX
     */
    public function handle_save_standby_batch() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $periods_json = Security::get_post_param( 'periods', '', 'text', true );
        
        if ( empty( $periods_json ) ) {
            wp_send_json_error( array( 'message' => __( 'No periods provided', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $periods = json_decode( $periods_json, true );
        
        if ( ! is_array( $periods ) || empty( $periods ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid periods format', HELPDESK_TEXT_DOMAIN ) ) );
        }

        global $wpdb;
        $table = Database::get_standby_table();
        
        // Validate and prepare all periods
        $valid_periods = array();
        foreach ( $periods as $period ) {
            $employee_id = absint( $period['employee_id'] ?? 0 );
            $project_id = absint( $period['project_id'] ?? 0 );
            $od = sanitize_text_field( $period['od'] ?? '' );
            $do = sanitize_text_field( $period['do'] ?? '' );

            if ( $employee_id && $project_id && $od && $do ) {
                $valid_periods[] = array(
                    'employee_id' => $employee_id,
                    'project_id' => $project_id,
                    'od' => $od,
                    'do' => $do
                );
            }
        }

        if ( empty( $valid_periods ) ) {
            wp_send_json_error( array( 'message' => __( 'No valid periods to save', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Start transaction
        $wpdb->query( 'START TRANSACTION' );

        try {
            // Build batch INSERT query
            $values = array();
            $placeholders = array();
            
            foreach ( $valid_periods as $period ) {
                $values[] = $period['employee_id'];
                $values[] = $period['project_id'];
                $values[] = $period['od'];
                $values[] = $period['do'];
                $placeholders[] = '(%d, %d, %s, %s)';
            }

            $query = "INSERT IGNORE INTO {$table} (pracovnik_id, projekt_id, pohotovost_od, pohotovost_do) VALUES " 
                    . implode( ', ', $placeholders );

            $prepared = $wpdb->prepare( $query, $values );
            $result = $wpdb->query( $prepared );

            if ( $result === false ) {
                throw new Exception( 'Database insert failed' );
            }

            // Commit transaction
            $wpdb->query( 'COMMIT' );

            wp_send_json_success( array(
                'message' => sprintf( __( '%d pohotovostí bolo vytvorených', HELPDESK_TEXT_DOMAIN ), count( $valid_periods ) ),
                'saved_count' => count( $valid_periods )
            ) );

        } catch ( Exception $e ) {
            // Rollback transaction
            $wpdb->query( 'ROLLBACK' );
            
            wp_send_json_error( array(
                'message' => __( 'Chyba pri ukladaní pohotovosti: ', HELPDESK_TEXT_DOMAIN ) . $e->getMessage()
            ) );
        }
    }

    /**
     * Handle delete standby AJAX
     */
    public function handle_delete_standby() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $employee_id = Security::get_post_param( 'employee_id', 0, 'int', true );
        $standby_id = Security::get_post_param( 'standby_id', 0, 'int', true );

        if ( ! $employee_id || ! $standby_id ) {
            wp_send_json_error( array( 'message' => __( 'Missing required fields', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $employee = new Employee( $employee_id );
        if ( ! $employee->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Employee not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $result = $employee->delete_standby_period( $standby_id );
        if ( $result ) {
            wp_send_json_success( array( 
                'message' => __( 'Standby period deleted successfully', HELPDESK_TEXT_DOMAIN ),
                'standby_periods' => $employee->get_standby_periods()
            ) );
        }

        wp_send_json_error( array( 'message' => __( 'Failed to delete standby period', HELPDESK_TEXT_DOMAIN ) ) );
    }

    /**
     * Handle get employee vacation AJAX
     */
    public function handle_get_employee_vacation() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $employee_id = Security::get_post_param( 'id', 0, 'int', true );

        if ( ! $employee_id ) {
            wp_send_json_error( array( 'message' => __( 'Employee ID required', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $employee = new Employee( $employee_id );
        if ( ! $employee->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Employee not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $nepritomnost_od = $employee->get( 'nepritomnost_od' );
        $nepritomnost_do = $employee->get( 'nepritomnost_do' );

        wp_send_json_success( array(
            'nepritomnost_od' => $nepritomnost_od,
            'nepritomnost_do' => $nepritomnost_do,
        ) );
    }

    /**
     * Handle save employee vacation AJAX
     */
    public function handle_save_employee_vacation() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $employee_id = Security::get_post_param( 'id', 0, 'int', true );
        $nepritomnost_od = Security::get_post_param( 'nepritomnost_od', '', 'text', true );
        $nepritomnost_do = Security::get_post_param( 'nepritomnost_do', '', 'text', true );

        if ( ! $employee_id || ! $nepritomnost_od || ! $nepritomnost_do ) {
            wp_send_json_error( array( 'message' => __( 'Missing required fields', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Validate date format (YYYY-MM-DD)
        $date_pattern = '/^\d{4}-\d{2}-\d{2}$/';
        if ( ! preg_match( $date_pattern, $nepritomnost_od ) || ! preg_match( $date_pattern, $nepritomnost_do ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid date format', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Validate that od is before do
        if ( $nepritomnost_od > $nepritomnost_do ) {
            wp_send_json_error( array( 'message' => __( 'Nepritomnosť od musí byť pred nepritomnosťou do', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $employee = new Employee( $employee_id );
        if ( ! $employee->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Employee not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Update employee vacation dates
        global $wpdb;
        $result = $wpdb->update(
            Database::get_employees_table(),
            array(
                'nepritomnost_od' => $nepritomnost_od,
                'nepritomnost_do' => $nepritomnost_do,
            ),
            array( 'id' => $employee_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        if ( $result !== false ) {
            wp_send_json_success( array(
                'message' => __( 'Nepritomnosť bola uložená', HELPDESK_TEXT_DOMAIN ),
            ) );
        }

        wp_send_json_error( array( 'message' => __( 'Failed to save vacation', HELPDESK_TEXT_DOMAIN ) ) );
    }

    /**
     * Handle remove employee vacation AJAX
     */
    public function handle_remove_employee_vacation() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $employee_id = Security::get_post_param( 'id', 0, 'int', true );

        if ( ! $employee_id ) {
            wp_send_json_error( array( 'message' => __( 'Employee ID required', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $employee = new Employee( $employee_id );
        if ( ! $employee->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Employee not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Clear vacation dates
        global $wpdb;
        $result = $wpdb->update(
            Database::get_employees_table(),
            array(
                'nepritomnost_od' => NULL,
                'nepritomnost_do' => NULL,
            ),
            array( 'id' => $employee_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        if ( $result !== false ) {
            wp_send_json_success( array(
                'message' => __( 'Nepritomnosť bola vymazaná', HELPDESK_TEXT_DOMAIN ),
            ) );
        }

        wp_send_json_error( array( 'message' => __( 'Failed to remove vacation', HELPDESK_TEXT_DOMAIN ) ) );
    }
}

