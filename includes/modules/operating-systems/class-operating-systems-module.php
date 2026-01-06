<?php
/**
 * Operating Systems Module
 */

namespace HelpDesk\Modules\OperatingSystems;

use HelpDesk\Modules\BaseModule;
use HelpDesk\Models\OperatingSystem;
use HelpDesk\Utils\Security;

class OperatingSystemsModule extends BaseModule {
    protected $module_name = 'Operačné Systémy';
    protected $module_slug = 'operating-systems';
    protected $menu_page_id = 'helpdesk-operating-systems';

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        add_action( 'wp_ajax_helpdesk_save_operating_system', array( $this, 'handle_save_operating_system' ) );
        add_action( 'wp_ajax_helpdesk_delete_operating_system', array( $this, 'handle_delete_operating_system' ) );
        add_action( 'wp_ajax_helpdesk_get_operating_system', array( $this, 'handle_get_operating_system' ) );
    }

    /**
     * Register menu
     */
    public function register_menu() {
        // Menu registration happens in Admin class
    }

    /**
     * Handle save operating system AJAX
     */
    public function handle_save_operating_system() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $nazov = isset( $_POST['nazov'] ) ? sanitize_text_field( $_POST['nazov'] ) : '';
        $zkratka = isset( $_POST['zkratka'] ) ? sanitize_text_field( $_POST['zkratka'] ) : '';
        $popis = isset( $_POST['popis'] ) ? sanitize_textarea_field( $_POST['popis'] ) : '';
        $aktivny = isset( $_POST['aktivny'] ) ? (int) $_POST['aktivny'] : 1;

        if ( empty( $nazov ) ) {
            wp_send_json_error( array( 'message' => __( 'Názov je povinný', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        $data = array(
            'nazov' => $nazov,
            'zkratka' => $zkratka,
            'popis' => $popis,
            'aktivny' => $aktivny,
        );

        try {
            if ( $id > 0 ) {
                // Update
                $result = OperatingSystem::update_os( $id, $data );
                if ( $result === false ) {
                    global $wpdb;
                    wp_send_json_error( array( 'message' => 'Database error: ' . $wpdb->last_error ) );
                    return;
                }
                $message = __( 'OS bol aktualizovaný', HELPDESK_TEXT_DOMAIN );
            } else {
                // Create
                $id = OperatingSystem::create( $data );
                if ( ! $id ) {
                    global $wpdb;
                    wp_send_json_error( array( 'message' => 'Database error: ' . $wpdb->last_error ) );
                    return;
                }
                $message = __( 'OS bol vytvorený', HELPDESK_TEXT_DOMAIN );
            }
            wp_send_json_success( array( 'message' => $message, 'id' => $id ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    /**
     * Handle delete operating system AJAX
     */
    public function handle_delete_operating_system() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( $id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Neplatné ID', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        OperatingSystem::delete_os( $id );
        wp_send_json_success( array( 'message' => __( 'OS bol zmazaný', HELPDESK_TEXT_DOMAIN ) ) );
    }

    /**
     * Handle get operating system AJAX
     */
    public function handle_get_operating_system() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Neplatné ID', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        $os = OperatingSystem::get_by_id( $id );

        if ( ! $os ) {
            wp_send_json_error( array( 'message' => __( 'OS nenájdený', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        wp_send_json_success( array( 'os' => $os ) );
    }
}
