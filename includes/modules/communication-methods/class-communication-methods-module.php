<?php
/**
 * Communication Methods Module
 */

namespace HelpDesk\Modules\CommunicationMethods;

use HelpDesk\Modules\BaseModule;
use HelpDesk\Models\CommunicationMethod;
use HelpDesk\Utils\Database;
use HelpDesk\Utils\Security;

class CommunicationMethodsModule extends BaseModule {
    /**
     * Module name
     */
    protected $module_name = 'communication-methods';

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
        add_action( 'wp_ajax_helpdesk_save_communication_method', array( $this, 'handle_save_communication_method' ) );
        add_action( 'wp_ajax_helpdesk_get_communication_method', array( $this, 'handle_get_communication_method' ) );
        add_action( 'wp_ajax_helpdesk_delete_communication_method', array( $this, 'handle_delete_communication_method' ) );
        add_action( 'wp_ajax_helpdesk_search_communication_methods', array( $this, 'handle_search_communication_methods' ) );
    }

    /**
     * Handle save communication method
     */
    public function handle_save_communication_method() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $nazov = isset( $_POST['nazov'] ) ? sanitize_text_field( $_POST['nazov'] ) : '';
        $popis = isset( $_POST['popis'] ) ? sanitize_text_field( $_POST['popis'] ) : '';
        $priorita = isset( $_POST['priorita'] ) ? absint( $_POST['priorita'] ) : 0;
        $aktivny = isset( $_POST['aktivny'] ) ? 1 : 0;

        if ( ! $nazov ) {
            wp_send_json_error( array( 'message' => __( 'Názov je povinný', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Check uniqueness
        $method = new CommunicationMethod( $id );
        if ( ! $method->is_nazov_unique( $nazov, $id ) ) {
            wp_send_json_error( array( 'message' => __( 'Tento názov už existuje', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $data = array(
            'nazov' => $nazov,
            'popis' => $popis,
            'priorita' => $priorita,
            'aktivny' => $aktivny,
        );

        if ( $id ) {
            // Update
            $method->update( $data );
        } else {
            // Create
            $id = $method->create( $data );
        }

        if ( $id ) {
            wp_send_json_success( array( 'id' => $id, 'message' => __( 'Uložené', HELPDESK_TEXT_DOMAIN ) ) );
        }

        wp_send_json_error( array( 'message' => __( 'Chyba pri ukladaní', HELPDESK_TEXT_DOMAIN ) ) );
    }

    /**
     * Handle get communication method
     */
    public function handle_get_communication_method() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'ID je povinný', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $method = new CommunicationMethod( $id );

        if ( ! $method->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Spôsob komunikácie nebol nájdený', HELPDESK_TEXT_DOMAIN ) ) );
        }

        wp_send_json_success( $method->get_data() );
    }

    /**
     * Handle delete communication method
     */
    public function handle_delete_communication_method() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'ID je povinný', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $method = new CommunicationMethod( $id );

        if ( ! $method->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Spôsob komunikácie nebol nájdený', HELPDESK_TEXT_DOMAIN ) ) );
        }

        if ( $method->delete() ) {
            wp_send_json_success( array( 'message' => __( 'Vymazané', HELPDESK_TEXT_DOMAIN ) ) );
        }

        wp_send_json_error( array( 'message' => __( 'Chyba pri mazaní', HELPDESK_TEXT_DOMAIN ) ) );
    }

    /**
     * Handle search communication methods
     */
    public function handle_search_communication_methods() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';

        global $wpdb;
        $table = Database::get_communication_methods_table();

        $query = "SELECT * FROM {$table}";

        if ( $search ) {
            $query .= $wpdb->prepare( " WHERE nazov LIKE %s OR popis LIKE %s", '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' );
        }

        $query .= " ORDER BY priorita ASC, nazov ASC";

        $results = $wpdb->get_results( $query, ARRAY_A );

        wp_send_json_success( $results );
    }
}
