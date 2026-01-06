<?php
/**
 * Signatures Module (Application Support)
 */

namespace HelpDesk\Modules\Signatures;

use HelpDesk\Modules\BaseModule;
use HelpDesk\Models\Signature;
use HelpDesk\Utils\Database;
use HelpDesk\Utils\Security;

class SignaturesModule extends BaseModule {
    /**
     * Module name
     */
    protected $module_name = 'signatures';

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
        add_action( 'wp_ajax_helpdesk_save_signature', array( $this, 'handle_save_signature' ) );
        add_action( 'wp_ajax_helpdesk_get_signature', array( $this, 'handle_get_signature' ) );
        add_action( 'wp_ajax_helpdesk_get_signature_by_employee_product', array( $this, 'handle_get_signature_by_employee_product' ) );
        add_action( 'wp_ajax_helpdesk_delete_signature', array( $this, 'handle_delete_signature' ) );
        add_action( 'wp_ajax_helpdesk_search_signatures', array( $this, 'handle_search_signatures' ) );
    }

    /**
     * Handle save signature
     */
    public function handle_save_signature() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $podpis = isset( $_POST['podpis'] ) ? sanitize_text_field( $_POST['podpis'] ) : '';
        $text_podpisu = isset( $_POST['text_podpisu'] ) ? wp_kses_post( $_POST['text_podpisu'] ) : '';
        $produkt_id = isset( $_POST['produkt_id'] ) ? absint( $_POST['produkt_id'] ) : 0;
        $pracovnik_id = isset( $_POST['pracovnik_id'] ) ? absint( $_POST['pracovnik_id'] ) : 0;

        if ( ! $podpis ) {
            wp_send_json_error( array( 'message' => __( 'Podpis je povinný', HELPDESK_TEXT_DOMAIN ) ) );
        }

        if ( ! $produkt_id ) {
            wp_send_json_error( array( 'message' => __( 'Produkt je povinný', HELPDESK_TEXT_DOMAIN ) ) );
        }

        if ( ! $pracovnik_id ) {
            wp_send_json_error( array( 'message' => __( 'Pracovník je povinný', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $data = array(
            'podpis' => $podpis,
            'text_podpisu' => $text_podpisu,
            'produkt_id' => $produkt_id,
            'pracovnik_id' => $pracovnik_id,
        );

        $signature = new Signature( $id );

        if ( $id ) {
            // Update
            $signature->update( $data );
        } else {
            // Create
            $id = $signature->create( $data );
        }

        if ( $id ) {
            wp_send_json_success( array( 'id' => $id, 'message' => __( 'Uložené', HELPDESK_TEXT_DOMAIN ) ) );
        }

        wp_send_json_error( array( 'message' => __( 'Chyba pri ukladaní', HELPDESK_TEXT_DOMAIN ) ) );
    }

    /**
     * Handle get signature
     */
    public function handle_get_signature() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'ID je povinný', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $signature = Signature::get_by_id( $id );

        if ( ! $signature ) {
            wp_send_json_error( array( 'message' => __( 'Podpis nebol nájdený', HELPDESK_TEXT_DOMAIN ) ) );
        }

        wp_send_json_success( $signature->get_data() );
    }

    /**
     * Handle get signature by employee and product (for auto-preset)
     */
    public function handle_get_signature_by_employee_product() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        $pracovnik_id = isset( $_POST['pracovnik_id'] ) ? absint( $_POST['pracovnik_id'] ) : 0;
        $produkt_id = isset( $_POST['produkt_id'] ) ? absint( $_POST['produkt_id'] ) : 0;

        if ( ! $pracovnik_id || ! $produkt_id ) {
            wp_send_json_error( array( 'message' => __( 'Pracovník a produkt sú povinní', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $signature = Signature::get_by_employee_and_product( $pracovnik_id, $produkt_id );

        if ( ! $signature ) {
            // No signature found, return empty success
            wp_send_json_success( null );
        }

        wp_send_json_success( $signature->get_data() );
    }

    /**
     * Handle delete signature
     */
    public function handle_delete_signature() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'ID je povinný', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $signature = new Signature( $id );

        if ( ! $signature->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Podpis nebol nájdený', HELPDESK_TEXT_DOMAIN ) ) );
        }

        if ( $signature->delete() ) {
            wp_send_json_success( array( 'message' => __( 'Vymazané', HELPDESK_TEXT_DOMAIN ) ) );
        }

        wp_send_json_error( array( 'message' => __( 'Chyba pri mazaní', HELPDESK_TEXT_DOMAIN ) ) );
    }

    /**
     * Handle search signatures
     */
    public function handle_search_signatures() {
        if ( ! Security::verify_ajax_request( '_ajax_nonce' ) ) {
            return;
        }

        $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';

        global $wpdb;
        $table = Database::get_signatures_table();
        $products_table = Database::get_products_table();
        $employees_table = Database::get_employees_table();

        $query = "SELECT s.id, s.podpis, s.produkt_id, p.nazov as produkt_nazov, s.pracovnik_id, e.meno_priezvisko
                  FROM {$table} s
                  LEFT JOIN {$products_table} p ON s.produkt_id = p.id
                  LEFT JOIN {$employees_table} e ON s.pracovnik_id = e.id";

        if ( $search ) {
            $query .= $wpdb->prepare(
                " WHERE s.podpis LIKE %s OR p.nazov LIKE %s OR e.meno_priezvisko LIKE %s",
                '%' . $wpdb->esc_like( $search ) . '%',
                '%' . $wpdb->esc_like( $search ) . '%',
                '%' . $wpdb->esc_like( $search ) . '%'
            );
        }

        $query .= " ORDER BY s.podpis ASC";

        $results = $wpdb->get_results( $query, ARRAY_A );

        wp_send_json_success( $results );
    }
}
