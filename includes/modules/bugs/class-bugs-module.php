<?php
/**
 * Bugs Module
 */

namespace HelpDesk\Modules\Bugs;

use HelpDesk\Modules\BaseModule;
use HelpDesk\Models\Bug;
use HelpDesk\Models\Product;
use HelpDesk\Models\Signature;
use HelpDesk\Utils\Validator;
use HelpDesk\Utils\Security;

class BugsModule extends BaseModule {
    protected $module_name = 'Riešenia';
    protected $module_slug = 'bugs';
    protected $menu_page_id = 'helpdesk-bugs';

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        add_action( 'wp_ajax_helpdesk_save_bug', array( $this, 'handle_save_bug' ) );
        add_action( 'wp_ajax_helpdesk_delete_bug', array( $this, 'handle_delete_bug' ) );
        add_action( 'wp_ajax_helpdesk_get_bug', array( $this, 'handle_get_bug' ) );
        add_action( 'wp_ajax_helpdesk_get_bugs', array( $this, 'handle_get_bugs' ) );
        add_action( 'wp_ajax_helpdesk_get_ap_hd_worker', array( $this, 'handle_get_ap_hd_worker' ) );
        add_action( 'wp_ajax_helpdesk_export_bugs', array( $this, 'handle_export_bugs' ) );
        add_action( 'wp_ajax_helpdesk_import_bugs', array( $this, 'handle_import_bugs' ) );
        add_action( 'wp_ajax_helpdesk_get_bug_codes', array( $this, 'handle_get_bug_codes' ) );
        add_action( 'wp_ajax_helpdesk_get_bug_code', array( $this, 'handle_get_bug_code' ) );
        add_action( 'wp_ajax_helpdesk_get_bug_code_by_kod', array( $this, 'handle_get_bug_code_by_kod' ) );
        add_action( 'wp_ajax_helpdesk_get_products', array( $this, 'handle_get_products' ) );
        add_action( 'wp_ajax_helpdesk_get_product', array( $this, 'handle_get_product' ) );
        add_action( 'wp_ajax_helpdesk_save_bug_code', array( $this, 'handle_save_bug_code' ) );
        add_action( 'wp_ajax_helpdesk_delete_bug_code', array( $this, 'handle_delete_bug_code' ) );
        add_action( 'wp_ajax_helpdesk_save_product', array( $this, 'handle_save_product' ) );
        add_action( 'wp_ajax_helpdesk_delete_product', array( $this, 'handle_delete_product' ) );
        add_action( 'wp_ajax_helpdesk_save_contact', array( $this, 'handle_save_contact' ) );
        add_action( 'wp_ajax_helpdesk_delete_contact', array( $this, 'handle_delete_contact' ) );
        add_action( 'wp_ajax_helpdesk_get_contact', array( $this, 'handle_get_contact' ) );
        add_action( 'wp_ajax_helpdesk_get_contacts', array( $this, 'handle_get_contacts' ) );
        add_action( 'wp_ajax_helpdesk_search_bugs', array( $this, 'handle_search_bugs' ) );
    }

    /**
     * Register menu
     */
    public function register_menu() {
        // Menu registration happens in Admin class
    }

    /**
     * Handle save bug AJAX
     */
    public function handle_save_bug() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $bug_id = Security::get_post_param( 'id', 0, 'int' );
        
        // Build data array from POST
        $data = array(
            'nazov' => Security::get_post_param( 'nazov', '', 'text' ),
            'popis' => Security::get_post_param( 'popis', '', 'textarea' ),
            'kod_chyby' => Security::get_post_param( 'kod_chyby', '', 'text' ),
            'produkt' => Security::get_post_param( 'produkt', 0, 'int' ),
            'email_1' => Security::get_post_param( 'email_1', '', 'textarea' ),
            'email_2' => Security::get_post_param( 'email_2', '', 'textarea' ),
            'popis_riesenia' => Security::get_post_param( 'popis_riesenia', '', 'textarea' ),
            'podpis_id' => Security::get_post_param( 'podpis_id', null, 'int' ),
            'tagy' => Security::get_post_param( 'tagy', '', 'text' ),
        );

        // Sanitize data
        $sanitized = Validator::sanitize_bug( $data );

        // Validate data
        $errors = Validator::validate_bug( $sanitized );

        if ( ! empty( $errors ) ) {
            wp_send_json_error( array( 'errors' => $errors ) );
        }

        if ( $bug_id ) {
            // Update
            $bug = new Bug( $bug_id );

            if ( ! $bug->exists() ) {
                wp_send_json_error( array( 'message' => __( 'Bug not found', HELPDESK_TEXT_DOMAIN ) ) );
            }

            $bug->update( $sanitized );
            wp_send_json_success( array(
                'message' => __( 'Bug updated successfully', HELPDESK_TEXT_DOMAIN ),
                'bug' => $bug->get_all_data(),
            ) );
        } else {
            // Create
            $bug = new Bug();
            
            error_log( 'Creating bug with data: ' . wp_json_encode( $sanitized ) );
            $id = $bug->create( $sanitized );

            if ( $id ) {
                $bug->load( $id );
                wp_send_json_success( array(
                    'message' => __( 'Bug created successfully', HELPDESK_TEXT_DOMAIN ),
                    'bug' => $bug->get_all_data(),
                ) );
            } else {
                error_log( 'Bug creation failed. Sanitized data: ' . print_r( $sanitized, true ) );
                wp_send_json_error( array( 'message' => __( 'Failed to create bug', HELPDESK_TEXT_DOMAIN ) ) );
            }
        }
    }

    /**
     * Handle delete bug AJAX
     */
    public function handle_delete_bug() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $bug_id = Security::get_post_param( 'id', 0, 'int' );

        if ( ! $bug_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid bug ID', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $bug = new Bug( $bug_id );

        if ( ! $bug->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Bug not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        if ( $bug->delete() ) {
            wp_send_json_success( array( 'message' => __( 'Bug deleted successfully', HELPDESK_TEXT_DOMAIN ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete bug', HELPDESK_TEXT_DOMAIN ) ) );
        }
    }

    /**
     * Handle get bug AJAX
     */
    public function handle_get_bug() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $bug_id = Security::get_post_param( 'id', 0, 'int' );

        if ( ! $bug_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid bug ID', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $bug = new Bug( $bug_id );

        if ( ! $bug->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Bug not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $bug_data = $bug->get_all_data();
        
        // Get product name
        $product_name = '';
        if ( ! empty( $bug_data['produkt'] ) ) {
            $product = new Product( $bug_data['produkt'] );
            if ( $product->exists() ) {
                $product_name = $product->get( 'nazov' );
            }
        }
        $bug_data['product_name'] = $product_name;

        // Get signature data if podpis_id exists
        $signature_data = array();
        if ( ! empty( $bug_data['podpis_id'] ) ) {
            $signature = new Signature( $bug_data['podpis_id'] );
            if ( $signature->exists() ) {
                $signature_data = array(
                    'id' => $signature->get( 'id' ),
                    'podpis' => $signature->get( 'podpis' ),
                    'text_podpisu' => $signature->get( 'text_podpisu' )
                );
            }
        }
        $bug_data['signature'] = $signature_data;

        wp_send_json_success( array( 'bug' => $bug_data ) );
    }

    /**
     * Handle get bugs AJAX
     */
    public function handle_get_bugs() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $bugs = Bug::get_all();
        wp_send_json_success( array( 'bugs' => $bugs ) );
    }

    /**
     * Get AP HD (APHD) worker for signature auto-preset
     */
    public function handle_get_ap_hd_worker() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        // Get AP HD settings from options
        $ap_hd_settings = get_option( 'helpdesk_ap_hd_settings', array( 'pracovnik_id' => 0 ) );
        $pracovnik_id = $ap_hd_settings['pracovnik_id'] ?? 0;

        if ( ! $pracovnik_id ) {
            wp_send_json_error( array( 'message' => __( 'AP HD worker not configured', HELPDESK_TEXT_DOMAIN ) ) );
        }

        wp_send_json_success( array( 'pracovnik_id' => $pracovnik_id ) );
    }

    /**
     * Handle export bugs to CSV
     */
    public function handle_export_bugs() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $bugs = Bug::get_all();

        if ( empty( $bugs ) ) {
            wp_send_json_error( array( 'message' => __( 'No bugs to export', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Prepare CSV data (bez ID - ID sa generuje automaticky pri importe)
        $csv_data = [];
        foreach ( $bugs as $bug ) {
            $csv_data[] = array(
                $bug['nazov'] ?? '',
                $bug['popis'] ?? '',
                $bug['stav'] ?? 'novy',
                $bug['projekt_id'] ?? '',
                $bug['pracovnik_id'] ?? '',
                $bug['datum_zaznamu'] ?? '',
            );
        }

        // Generate CSV content
        require_once HELPDESK_PLUGIN_DIR . 'includes/utils/class-csv.php';
        $csv_content = \HelpDesk\Utils\CSV::generate(
            $csv_data,
            array( 'Názov', 'Popis', 'Stav', 'ID Projektu', 'ID Pracovníka', 'Dátum záznamu' )
        );

        $filename = 'chyby_' . date( 'Y-m-d_H-i-s' ) . '.csv';

        wp_send_json_success( array(
            'content' => $csv_content,
            'filename' => $filename,
        ) );
    }
    /**
     * Handle import bugs from CSV
     */
    public function handle_import_bugs() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        if ( ! isset( $_FILES['csv_file'] ) ) {
            error_log( 'HelpDesk: $_FILES not set. FILES: ' . print_r( $_FILES, true ) );
            wp_send_json_error( array( 'message' => __( 'No file uploaded', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $file = $_FILES['csv_file'];

        // Check for upload errors
        if ( ! empty( $file['error'] ) ) {
            error_log( 'HelpDesk: File upload error: ' . $file['error'] );
            wp_send_json_error( array( 'message' => __( 'File upload error', HELPDESK_TEXT_DOMAIN ) ) );
        }

        // Validate file type (more lenient)
        $allowed_types = array( 'text/csv', 'text/plain', 'application/vnd.ms-excel' );
        if ( ! in_array( $file['type'], $allowed_types ) ) {
            error_log( 'HelpDesk: Invalid file type: ' . $file['type'] );
        }

        // Read file content
        if ( ! file_exists( $file['tmp_name'] ) ) {
            error_log( 'HelpDesk: Temp file does not exist: ' . $file['tmp_name'] );
            wp_send_json_error( array( 'message' => __( 'Uploaded file not found', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $content = file_get_contents( $file['tmp_name'] );

        if ( ! $content ) {
            error_log( 'HelpDesk: Failed to read file content' );
            wp_send_json_error( array( 'message' => __( 'Failed to read file', HELPDESK_TEXT_DOMAIN ) ) );
        }

        if ( ! $content ) {
            wp_send_json_error( array( 'message' => __( 'Failed to read file', HELPDESK_TEXT_DOMAIN ) ) );
        }

        require_once HELPDESK_PLUGIN_DIR . 'includes/utils/class-csv.php';
        $rows = \HelpDesk\Utils\CSV::parse( $content );

        if ( empty( $rows ) ) {
            wp_send_json_error( array( 'message' => __( 'CSV file is empty', HELPDESK_TEXT_DOMAIN ) ) );
        }

        $imported = 0;
        $errors = [];

        foreach ( $rows as $index => $row ) {
            $line_number = $index + 2;

            $data = array(
                'nazov' => isset( $row['Názov'] ) ? \HelpDesk\Utils\CSV::sanitize_value( $row['Názov'] ) : '',
                'popis' => isset( $row['Popis'] ) ? \HelpDesk\Utils\CSV::sanitize_value( $row['Popis'] ) : '',
                'stav' => isset( $row['Stav'] ) ? \HelpDesk\Utils\CSV::sanitize_value( $row['Stav'] ) : 'novy',
                'projekt_id' => isset( $row['ID Projektu'] ) ? absint( $row['ID Projektu'] ) : null,
                'pracovnik_id' => isset( $row['ID Pracovníka'] ) ? absint( $row['ID Pracovníka'] ) : null,
            );

            // Validate
            $validation_errors = Validator::validate_bug( $data );
            if ( ! empty( $validation_errors ) ) {
                $errors[] = sprintf(
                    __( 'Line %d: %s', HELPDESK_TEXT_DOMAIN ),
                    $line_number,
                    implode( ', ', $validation_errors )
                );
                continue;
            }

            // Create
            $bug = new Bug();
            if ( $bug->create( $data ) ) {
                $imported++;
            } else {
                $errors[] = sprintf(
                    __( 'Line %d: Failed to create bug', HELPDESK_TEXT_DOMAIN ),
                    $line_number
                );
            }
        }

        $message = sprintf(
            __( 'Imported %d bugs', HELPDESK_TEXT_DOMAIN ),
            $imported
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
     * Handle get bug codes AJAX
     */
    public function handle_get_bug_codes() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $bug_codes = \HelpDesk\Models\BugCode::get_all();
        wp_send_json_success( array( 'codes' => $bug_codes ) );
    }

    /**
     * Handle get single bug code AJAX
     */
    public function handle_get_bug_code() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $id = Security::get_post_param( 'id', 0, 'int' );

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Neplatné ID', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        global $wpdb;
        $table = \HelpDesk\Utils\Database::get_bug_codes_table();
        
        $code = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . esc_sql( $table ) . " WHERE id = %d", $id ),
            ARRAY_A
        );

        if ( ! $code ) {
            wp_send_json_error( array( 'message' => __( 'Kód nenájdený', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        wp_send_json_success( array( 'code' => $code ) );
    }

    /**
     * Handle get products AJAX
     */
    public function handle_get_products() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $products = \HelpDesk\Models\Product::get_all();
        wp_send_json_success( array( 'products' => $products ) );
    }

    /**
     * Handle save bug code AJAX
     */
    public function handle_save_bug_code() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $id = Security::get_post_param( 'id', 0, 'int' );
        $kod = Security::get_post_param( 'kod', '', 'text' );
        $popis = Security::get_post_param( 'popis', '', 'textarea' );
        $uplny_popis = Security::get_post_param( 'uplny_popis', '', 'textarea' );
        $operacny_system = Security::get_post_param( 'operacny_system', '', 'text' );
        $produkt = Security::get_post_param( 'produkt', 0, 'int' );
        $aktivny = Security::get_post_param( 'aktivny', 1, 'int' );

        if ( empty( $kod ) ) {
            wp_send_json_error( array( 'message' => __( 'Kód je povinný', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        $data = array(
            'kod' => $kod,
            'popis' => $popis,
            'uplny_popis' => $uplny_popis,
            'operacny_system' => $operacny_system,
            'produkt' => $produkt > 0 ? $produkt : null,
            'aktivny' => $aktivny,
        );

        try {
            if ( $id > 0 ) {
                // Update
                $result = \HelpDesk\Models\BugCode::update_code( $id, $data );
                if ( $result === false ) {
                    global $wpdb;
                    wp_send_json_error( array( 'message' => 'Database error: ' . $wpdb->last_error ) );
                    return;
                }
                $message = __( 'Kód bol aktualizovaný', HELPDESK_TEXT_DOMAIN );
            } else {
                // Create
                $id = \HelpDesk\Models\BugCode::create( $data );
                if ( ! $id ) {
                    global $wpdb;
                    wp_send_json_error( array( 'message' => 'Database error: ' . $wpdb->last_error ) );
                    return;
                }
                $message = __( 'Kód bol vytvorený', HELPDESK_TEXT_DOMAIN );
            }
            wp_send_json_success( array( 'message' => $message, 'id' => $id ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    /**
     * Handle delete bug code AJAX
     */
    public function handle_delete_bug_code() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $id = Security::get_post_param( 'id', 0, 'int' );

        if ( $id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Neplatné ID', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        \HelpDesk\Models\BugCode::delete_code( $id );
        wp_send_json_success( array( 'message' => __( 'Kód bol odstránený', HELPDESK_TEXT_DOMAIN ) ) );
    }

    /**
     * Handle save product AJAX
     */
    public function handle_get_product() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $product_id = Security::get_post_param( 'id', 0, 'int' );

        if ( ! $product_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid product ID', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        $product = new \HelpDesk\Models\Product( $product_id );

        if ( ! $product->exists() ) {
            wp_send_json_error( array( 'message' => __( 'Product not found', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        wp_send_json_success( array( 'product' => $product->get_all_data() ) );
    }

    /**
     * Handle save product AJAX
     */
    public function handle_save_product() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $id = Security::get_post_param( 'id', 0, 'int' );
        $nazov = Security::get_post_param( 'nazov', '', 'text' );
        $popis = Security::get_post_param( 'popis', '', 'textarea' );
        $link = Security::get_post_param( 'link', '', 'url' );
        $aktivny = Security::get_post_param( 'aktivny', 1, 'int' );

        if ( empty( $nazov ) ) {
            wp_send_json_error( array( 'message' => __( 'Názov je povinný', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        $data = array(
            'nazov' => $nazov,
            'popis' => $popis,
            'link' => $link,
            'aktivny' => $aktivny,
        );

        try {
            if ( $id > 0 ) {
                // Update
                $result = \HelpDesk\Models\Product::update_product( $id, $data );
                if ( $result === false ) {
                    global $wpdb;
                    wp_send_json_error( array( 'message' => 'Database error: ' . $wpdb->last_error ) );
                    return;
                }
                $message = __( 'Produkt bol aktualizovaný', HELPDESK_TEXT_DOMAIN );
            } else {
                // Create
                $id = \HelpDesk\Models\Product::create( $data );
                if ( ! $id ) {
                    global $wpdb;
                    wp_send_json_error( array( 'message' => 'Database error: ' . $wpdb->last_error ) );
                    return;
                }
                $message = __( 'Produkt bol vytvorený', HELPDESK_TEXT_DOMAIN );
            }
            wp_send_json_success( array( 'message' => $message, 'id' => $id ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    /**
     * Handle delete product AJAX
     */
    public function handle_delete_product() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $id = Security::get_post_param( 'id', 0, 'int' );

        if ( $id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Neplatné ID', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        \HelpDesk\Models\Product::delete_product( $id );
        wp_send_json_success( array( 'message' => __( 'Produkt bol odstránený', HELPDESK_TEXT_DOMAIN ) ) );
    }

    /**
     * Handle save contact AJAX
     */
    public function handle_save_contact() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $id = Security::get_post_param( 'id', 0, 'int' );
        $nazov = Security::get_post_param( 'nazov', '', 'text' );
        $kontaktna_osoba = Security::get_post_param( 'kontaktna_osoba', '', 'text' );
        $klapka = Security::get_post_param( 'klapka', '', 'text' );
        $telefon = Security::get_post_param( 'telefon', '', 'text' );
        $email = Security::get_post_param( 'email', '', 'email' );
        $poznamka = Security::get_post_param( 'poznamka', '', 'textarea' );
        $aktivny = Security::get_post_param( 'aktivny', 1, 'int' );

        $data = array(
            'nazov' => $nazov,
            'kontaktna_osoba' => $kontaktna_osoba,
            'klapka' => $klapka,
            'telefon' => $telefon,
            'email' => $email,
            'poznamka' => $poznamka,
            'aktivny' => $aktivny,
        );

        try {
            if ( $id > 0 ) {
                // Update
                $result = \HelpDesk\Models\Contact::update_contact( $id, $data );
                if ( $result === false ) {
                    global $wpdb;
                    wp_send_json_error( array( 'message' => 'Database error: ' . $wpdb->last_error ) );
                    return;
                }
                $message = __( 'Kontakt bol aktualizovaný', HELPDESK_TEXT_DOMAIN );
            } else {
                // Create
                $id = \HelpDesk\Models\Contact::create( $data );
                if ( ! $id ) {
                    global $wpdb;
                    wp_send_json_error( array( 'message' => 'Database error: ' . $wpdb->last_error ) );
                    return;
                }
                $message = __( 'Kontakt bol vytvorený', HELPDESK_TEXT_DOMAIN );
            }
            wp_send_json_success( array( 'message' => $message, 'id' => $id ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    /**
     * Handle delete contact AJAX
     */
    public function handle_delete_contact() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $id = Security::get_post_param( 'id', 0, 'int' );

        if ( $id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Neplatné ID', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        \HelpDesk\Models\Contact::delete_contact( $id );
        wp_send_json_success( array( 'message' => __( 'Kontakt bol odstránený', HELPDESK_TEXT_DOMAIN ) ) );
    }

    /**
     * Handle get contact AJAX (single contact)
     */
    public function handle_get_contact() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $id = Security::get_post_param( 'id', 0, 'int' );

        if ( $id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Neplatné ID', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        global $wpdb;
        $table = \HelpDesk\Utils\Database::get_contacts_table();
        $contact = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
            ARRAY_A
        );

        if ( ! $contact ) {
            wp_send_json_error( array( 'message' => __( 'Kontakt nebol nájdený', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        wp_send_json_success( array( 'contact' => $contact ) );
    }

    /**
     * Handle get contacts AJAX (for selects, etc)
     */
    public function handle_get_contacts() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $active_only = Security::get_post_param( 'active_only', true, 'bool' );
        $contacts = \HelpDesk\Models\Contact::get_all( $active_only );
        wp_send_json_success( array( 'contacts' => $contacts ) );
    }

    /**
     * Handle search bugs AJAX
     */
    public function handle_search_bugs() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        // Get search term
        $search_term = Security::get_post_param( 'search', '', 'text' );

        if ( empty( $search_term ) ) {
            wp_send_json_success( array( 'bugs' => array() ) );
            wp_die();
        }

        global $wpdb;
        $bugs_table = \HelpDesk\Utils\Database::get_bugs_table();
        $products_table = \HelpDesk\Utils\Database::get_products_table();
        $like = '%' . $wpdb->esc_like( $search_term ) . '%';

        // Search in kod_chyby, nazov, tagy with product name lookup
        $bugs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT b.*, p.nazov as product_name FROM {$bugs_table} b 
                 LEFT JOIN {$products_table} p ON b.produkt = p.id
                 WHERE b.kod_chyby LIKE %s OR b.nazov LIKE %s OR b.tagy LIKE %s 
                 ORDER BY b.nazov ASC LIMIT 20",
                $like,
                $like,
                $like
            ),
            ARRAY_A
        );

        wp_send_json_success( array( 'bugs' => $bugs ? $bugs : array() ) );
        wp_die();
    }

    /**
     * Handle get bug code by kod (for autocomplete)
     */
    public function handle_get_bug_code_by_kod() {
        if ( ! Security::verify_ajax_request() ) {
            return;
        }

        $kod = Security::get_post_param( 'kod', '', 'text' );

        if ( empty( $kod ) ) {
            wp_send_json_error( array( 'message' => __( 'Kód nie je zadaný', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        global $wpdb;
        $table = \HelpDesk\Utils\Database::get_bug_codes_table();
        
        $code = $wpdb->get_row(
            $wpdb->prepare( "SELECT id, kod, popis, uplny_popis FROM " . esc_sql( $table ) . " WHERE kod = %s", $kod ),
            ARRAY_A
        );

        if ( ! $code ) {
            wp_send_json_error( array( 'message' => __( 'Kód nenájdený', HELPDESK_TEXT_DOMAIN ) ) );
            return;
        }

        wp_send_json_success( array( 'code' => $code ) );
    }
}
