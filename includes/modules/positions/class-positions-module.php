<?php
/**
 * Positions Module
 */

namespace HelpDesk\Modules\Positions;

use HelpDesk\Models\Position;
use HelpDesk\Modules\BaseModule;
use HelpDesk\Utils\Security;

class PositionsModule extends BaseModule {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        add_action( 'wp_ajax_helpdesk_save_position', array( $this, 'handle_save_position' ) );
        add_action( 'wp_ajax_helpdesk_delete_position', array( $this, 'handle_delete_position' ) );
        add_action( 'wp_ajax_helpdesk_export_positions', array( $this, 'handle_export_positions' ) );
        add_action( 'wp_ajax_helpdesk_import_positions', array( $this, 'handle_import_positions' ) );
    }

    /**
     * Register menu - handled by Admin class
     */
    public function register_menu() {
        // Menu registration happens in Admin class
    }

    /**
     * Handle save position AJAX
     */
    public function handle_save_position() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $profesia = isset( $_POST['profesia'] ) ? sanitize_text_field( $_POST['profesia'] ) : '';
        $skratka = isset( $_POST['skratka'] ) ? sanitize_text_field( $_POST['skratka'] ) : '';
        $priorita = isset( $_POST['priorita'] ) ? sanitize_text_field( $_POST['priorita'] ) : '';

        if ( empty( $profesia ) ) {
            wp_send_json_error( array( 'message' => __( 'Profesia je povinná', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        // Check for duplicates (excluding current record)
        if ( Position::name_exists( $profesia, $id > 0 ? $id : null ) ) {
            wp_send_json_error( array( 'message' => __( 'Táto profesia už existuje', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        $data = array(
            'profesia' => $profesia,
            'skratka' => $skratka,
            'priorita' => $priorita,
        );

        try {
            if ( $id > 0 ) {
                // Update
                $result = Position::update_position( $id, $data );
                if ( $result === false ) {
                    global $wpdb;
                    wp_send_json_error( array( 'message' => 'Database error: ' . $wpdb->last_error ) );
                    return;
                }
                $message = __( 'Pozícia bola aktualizovaná', HELPDESK_TEXT_DOMAIN );
            } else {
                // Create
                $id = Position::create( $data );
                if ( ! $id ) {
                    global $wpdb;
                    wp_send_json_error( array( 'message' => 'Database error: ' . $wpdb->last_error ) );
                    return;
                }
                $message = __( 'Pozícia bola vytvorená', HELPDESK_TEXT_DOMAIN );
            }
            wp_send_json_success( array( 'message' => $message, 'id' => $id ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    /**
     * Handle delete position AJAX
     */
    public function handle_delete_position() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( $id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid position ID', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        try {
            $result = Position::delete_position( $id );
            if ( $result === false ) {
                global $wpdb;
                wp_send_json_error( array( 'message' => 'Database error: ' . $wpdb->last_error ) );
                return;
            }
            wp_send_json_success( array( 'message' => __( 'Pozícia bola zmazaná', HELPDESK_TEXT_DOMAIN ) ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    /**
     * Handle export positions to CSV
     */
    public function handle_export_positions() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $positions = Position::get_all();

        if ( empty( $positions ) ) {
            wp_send_json_error( array( 'message' => __( 'No positions to export', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Prepare CSV data
        $csv_data = [];
        foreach ( $positions as $pos ) {
            $csv_data[] = array(
                $pos['profesia'],
                $pos['priorita'] ?? 0,
            );
        }

        // Generate CSV content
        require_once HELPDESK_PLUGIN_DIR . 'includes/utils/class-csv.php';
        $csv_content = \HelpDesk\Utils\CSV::generate(
            $csv_data,
            array( 'Profesia', 'Priorita' )
        );

        $filename = 'pozicie_' . date( 'Y-m-d_H-i-s' ) . '.csv';

        wp_send_json_success( array(
            'content' => $csv_content,
            'filename' => $filename,
        ) );
    }

    /**
     * Handle import positions from CSV
     */
    public function handle_import_positions() {
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

        $imported = 0;
        $warnings = [];

        foreach ( $rows as $index => $row ) {
            $line_number = $index + 2; // +2 because of header and 0-indexed

            $profesia = isset( $row['Profesia'] ) ? \HelpDesk\Utils\CSV::sanitize_value( $row['Profesia'] ) : '';
            $priorita = isset( $row['Priorita'] ) ? (int) \HelpDesk\Utils\CSV::sanitize_value( $row['Priorita'] ) : 0;

            if ( empty( $profesia ) ) {
                $warnings[] = sprintf(
                    __( 'Line %d: Profesia is required', HELPDESK_TEXT_DOMAIN ),
                    $line_number
                );
                continue;
            }

            // Check if already exists
            if ( Position::name_exists( $profesia ) ) {
                $warnings[] = sprintf(
                    __( 'Line %d: Profesia "%s" already exists', HELPDESK_TEXT_DOMAIN ),
                    $line_number,
                    $profesia
                );
                continue;
            }

            // Create position
            $data = array(
                'profesia' => $profesia,
                'priorita' => $priorita,
            );

            $result = Position::create( $data );
            if ( $result ) {
                $imported++;
            } else {
                $warnings[] = sprintf(
                    __( 'Line %d: Failed to create position', HELPDESK_TEXT_DOMAIN ),
                    $line_number
                );
            }
        }

        if ( $imported > 0 ) {
            wp_send_json_success( array(
                'message' => sprintf( __( '%d positions imported', HELPDESK_TEXT_DOMAIN ), $imported ),
                'warnings' => $warnings,
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'No positions imported', HELPDESK_TEXT_DOMAIN ) ) );
        }
    }
}
