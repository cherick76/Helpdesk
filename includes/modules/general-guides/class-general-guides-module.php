<?php
/**
 * General Guides Module
 */

namespace HelpDesk\Modules\GeneralGuides;

use HelpDesk\Modules\BaseModule;
use HelpDesk\Models\GeneralGuide;
use HelpDesk\Models\GuideLink;
use HelpDesk\Models\GuideCategory;
use HelpDesk\Models\GuideResource;
use HelpDesk\Utils\Validator;
use HelpDesk\Utils\Security;

class GeneralGuidesModule extends BaseModule {
    protected $module_name = 'Všeobecné návody';
    protected $module_slug = 'general-guides';
    protected $menu_page_id = 'helpdesk-general-guides';

    /**
     * Constructor
     */
    public function __construct() {
        error_log( 'DEBUG: GeneralGuidesModule constructor called, this=' . get_class( $this ) );
        parent::__construct();
        
        error_log( 'DEBUG: About to register AJAX handler wp_ajax_helpdesk_save_guide_category' );
        add_action( 'wp_ajax_helpdesk_save_guide_category', array( $this, 'handle_save_category' ) );
        error_log( 'DEBUG: Handler registered, checking: ' . ( isset( $GLOBALS['wp_filter']['wp_ajax_helpdesk_save_guide_category'] ) ? 'FOUND' : 'NOT FOUND' ) );
        
        add_action( 'wp_ajax_helpdesk_delete_general_guide', array( $this, 'handle_delete_guide' ) );
        add_action( 'wp_ajax_helpdesk_get_general_guide', array( $this, 'handle_get_guide' ) );
        add_action( 'wp_ajax_helpdesk_get_general_guides', array( $this, 'handle_get_guides' ) );
        add_action( 'wp_ajax_helpdesk_search_general_guides', array( $this, 'handle_search_guides' ) );
        add_action( 'wp_ajax_helpdesk_save_guide_link', array( $this, 'handle_save_link' ) );
        add_action( 'wp_ajax_helpdesk_delete_guide_link', array( $this, 'handle_delete_link' ) );
        add_action( 'wp_ajax_helpdesk_get_guide_link', array( $this, 'handle_get_link' ) );
        add_action( 'wp_ajax_helpdesk_save_general_guide', array( $this, 'handle_save_guide' ) );
        
        // Guide categories AJAX handlers
        add_action( 'wp_ajax_helpdesk_delete_guide_category', array( $this, 'handle_delete_category' ) );
        add_action( 'wp_ajax_helpdesk_get_guide_category', array( $this, 'handle_get_category' ) );
        error_log( 'DEBUG: GeneralGuidesModule AJAX handlers registered' );
    }

    /**
     * Register menu
     */
    public function register_menu() {
        // Menu registration happens in Admin class
    }

    /**
     * Handle save guide AJAX
     */
    public function handle_save_guide() {
        if ( ! Security::verify_ajax_request( 'nonce' ) ) {
            return;
        }

        $guide_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        
        // Build data array from POST
        $data = array(
            'nazov' => isset( $_POST['nazov'] ) ? sanitize_text_field( $_POST['nazov'] ) : '',
            'kategoria' => isset( $_POST['kategoria'] ) ? sanitize_text_field( $_POST['kategoria'] ) : '',
            'produkt' => isset( $_POST['produkt'] ) ? absint( $_POST['produkt'] ) : 0,
            'popis' => isset( $_POST['popis'] ) ? sanitize_textarea_field( $_POST['popis'] ) : '',
            'tagy' => isset( $_POST['tagy'] ) ? sanitize_text_field( $_POST['tagy'] ) : '',
            'aktivny' => isset( $_POST['aktivny'] ) ? absint( $_POST['aktivny'] ) : 1,
        );

        // Validate data
        if ( empty( $data['nazov'] ) ) {
            wp_send_json_error( array( 'message' => 'Názov je povinný' ) );
        }

        if ( $guide_id ) {
            // Update
            $guide = new GeneralGuide( $guide_id );

            if ( ! $guide->exists() ) {
                wp_send_json_error( array( 'message' => 'Návod nenájdený' ) );
            }

            $guide->update( $data );
            wp_send_json_success( array(
                'message' => 'Návod bol aktualizovaný',
                'guide' => $guide->get_all_data(),
            ) );
        } else {
            // Create
            $guide = new GeneralGuide();
            $new_id = $guide->create( $data );

            if ( ! $new_id ) {
                wp_send_json_error( array( 'message' => 'Chyba pri vytváraní návodu' ) );
            }

            $guide = new GeneralGuide( $new_id );
            wp_send_json_success( array(
                'message' => 'Návod bol vytvorený',
                'guide' => $guide->get_all_data(),
            ) );
        }
    }

    /**
     * Handle delete guide AJAX
     */
    public function handle_delete_guide() {
        if ( ! Security::verify_ajax_request( 'nonce' ) ) {
            return;
        }

        $guide_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $guide_id ) {
            wp_send_json_error( array( 'message' => 'ID návodu chýba' ) );
        }

        $guide = new GeneralGuide( $guide_id );

        if ( ! $guide->exists() ) {
            wp_send_json_error( array( 'message' => 'Návod nenájdený' ) );
        }

        if ( $guide->delete() ) {
            // Delete associated links
            GuideLink::delete_by_guide( $guide_id );
            wp_send_json_success( array( 'message' => 'Návod bol odstránený' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Chyba pri odstraňovaní návodu' ) );
        }
    }

    /**
     * Handle get guide AJAX
     */
    public function handle_get_guide() {
        if ( ! Security::verify_ajax_request( 'nonce' ) ) {
            return;
        }

        $guide_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

        if ( ! $guide_id ) {
            wp_send_json_error( array( 'message' => 'ID návodu chýba' ) );
        }

        $guide = new GeneralGuide( $guide_id );

        if ( ! $guide->exists() ) {
            wp_send_json_error( array( 'message' => 'Návod nenájdený' ) );
        }

        $guide_data = $guide->get_all_data();
        $guide_data['links'] = GuideLink::get_by_guide( $guide_id );

        wp_send_json_success( array(
            'guide' => $guide_data,
        ) );
    }

    /**
     * Handle get guides AJAX
     */
    public function handle_get_guides() {
        if ( ! Security::verify_ajax_request( 'nonce' ) ) {
            return;
        }

        $guides = GeneralGuide::get_all_active();

        wp_send_json_success( array(
            'guides' => $guides,
        ) );
    }

    /**
     * Handle search guides AJAX
     */
    public function handle_search_guides() {
        if ( ! Security::verify_ajax_request( 'nonce' ) ) {
            return;
        }

        $search_term = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';

        if ( empty( $search_term ) ) {
            wp_send_json_error( array( 'message' => 'Hľadaný výraz je prázdny' ) );
        }

        $guides = GeneralGuide::search( $search_term );

        wp_send_json_success( array(
            'guides' => $guides,
        ) );
    }

    /**
     * Handle save link AJAX
     */
    public function handle_save_link() {
        if ( ! Security::verify_ajax_request( 'nonce' ) ) {
            return;
        }

        $link_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $navod_id = isset( $_POST['navod_id'] ) ? absint( $_POST['navod_id'] ) : 0;
        
        // If navod_id is provided, save to GuideLink (links in guides)
        if ( $navod_id ) {
            // Check if guide exists
            $guide = new GeneralGuide( $navod_id );
            if ( ! $guide->exists() ) {
                wp_send_json_error( array( 'message' => 'Návod nenájdený' ) );
            }

            // Build data array from POST
            $data = array(
                'navod_id' => $navod_id,
                'nazov' => isset( $_POST['nazov'] ) ? sanitize_text_field( $_POST['nazov'] ) : '',
                'url' => isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '',
                'produkt' => isset( $_POST['produkt'] ) ? absint( $_POST['produkt'] ) : 0,
            );

            // Validate data
            if ( empty( $data['nazov'] ) || empty( $data['url'] ) ) {
                wp_send_json_error( array( 'message' => 'Názov a URL sú povinné' ) );
            }

            if ( $link_id ) {
                // Update
                $link = new GuideLink( $link_id );

                if ( ! $link->exists() ) {
                    wp_send_json_error( array( 'message' => 'Linka nenájdená' ) );
                }

                $link->update( $data );
                wp_send_json_success( array(
                    'message' => 'Linka bola aktualizovaná',
                    'link' => $link->get_all_data(),
                ) );
            } else {
                // Create
                $link = new GuideLink();
                $new_id = $link->create( $data );

                if ( ! $new_id ) {
                    wp_send_json_error( array( 'message' => 'Chyba pri vytváraní linky' ) );
                }

                $link = new GuideLink( $new_id );
                wp_send_json_success( array(
                    'message' => 'Linka bola vytvorená',
                    'link' => $link->get_all_data(),
                ) );
            }
        } else {
            // Save to GuideResource (standalone links for Linky návodov)
            $data = array(
                'nazov' => isset( $_POST['nazov'] ) ? sanitize_text_field( $_POST['nazov'] ) : '',
                'url' => isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '',
                'typ' => isset( $_POST['typ'] ) ? sanitize_text_field( $_POST['typ'] ) : 'externe',
                'aktivny' => isset( $_POST['aktivny'] ) ? absint( $_POST['aktivny'] ) : 1,
            );

            if ( empty( $data['nazov'] ) || empty( $data['url'] ) ) {
                wp_send_json_error( array( 'message' => 'Názov a URL sú povinné' ) );
            }

            if ( $link_id ) {
                // Update
                $resource = new GuideResource( $link_id );
                if ( ! $resource->exists() ) {
                    wp_send_json_error( array( 'message' => 'Zdroj nenájdený' ) );
                }
                $resource->update( $data );
                wp_send_json_success( array(
                    'message' => 'Zdroj bol aktualizovaný',
                    'resource' => $resource->get_all_data(),
                ) );
            } else {
                // Create
                $resource = new GuideResource();
                $new_id = $resource->create( $data );

                if ( ! $new_id ) {
                    wp_send_json_error( array( 'message' => 'Chyba pri vytváraní zdroja' ) );
                }

                $resource = new GuideResource( $new_id );
                wp_send_json_success( array(
                    'message' => 'Zdroj bol vytvorený',
                    'resource' => $resource->get_all_data(),
                ) );
            }
        }
    }

    /**
     * Handle delete link AJAX
     */
    public function handle_delete_link() {
        if ( ! Security::verify_ajax_request( 'nonce' ) ) {
            return;
        }

        $link_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $link_id ) {
            wp_send_json_error( array( 'message' => 'ID linky chýba' ) );
        }

        // Try GuideResource first (standalone links)
        $link = new GuideResource( $link_id );

        if ( ! $link->exists() ) {
            // Fall back to GuideLink (links in guides)
            $link = new GuideLink( $link_id );
        }

        if ( ! $link->exists() ) {
            wp_send_json_error( array( 'message' => 'Linka nenájdená' ) );
        }

        if ( $link->delete() ) {
            wp_send_json_success( array( 'message' => 'Linka bola odstránená' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Chyba pri odstraňovaní linky' ) );
        }
    }

    /**
     * Handle get link AJAX
     */
    public function handle_get_link() {
        if ( ! Security::verify_ajax_request( 'nonce' ) ) {
            return;
        }

        $link_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $link_id ) {
            wp_send_json_error( array( 'message' => 'ID linky chýba' ) );
        }

        // Try GuideResource first (standalone links)
        $link = new GuideResource( $link_id );

        if ( ! $link->exists() ) {
            // Fall back to GuideLink (links in guides)
            $link = new GuideLink( $link_id );
        }

        if ( ! $link->exists() ) {
            wp_send_json_error( array( 'message' => 'Linka nenájdená' ) );
        }

        wp_send_json_success( array(
            'link' => $link->get_all_data(),
        ) );
    }

    /**
     * Handle save guide category AJAX
     */
    public function handle_save_category() {
        error_log( 'DEBUG: handle_save_category called' );
        
        // Verify nonce without dying, so we can send proper JSON response
        if ( ! Security::verify_ajax_request( 'nonce' ) ) {
            return;
        }

        $category_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        
        $data = array(
            'nazov' => isset( $_POST['nazov'] ) ? sanitize_text_field( $_POST['nazov'] ) : '',
            'popis' => isset( $_POST['popis'] ) ? sanitize_textarea_field( $_POST['popis'] ) : '',
            'poradie' => isset( $_POST['poradie'] ) ? absint( $_POST['poradie'] ) : 0,
            'aktivny' => isset( $_POST['aktivny'] ) ? absint( $_POST['aktivny'] ) : 1,
        );

        if ( empty( $data['nazov'] ) ) {
            wp_send_json_error( array( 'message' => 'Názov kategórie je povinný' ) );
        }

        if ( $category_id ) {
            // Update
            $category = new GuideCategory( $category_id );

            if ( ! $category->exists() ) {
                wp_send_json_error( array( 'message' => 'Kategória nenájdená' ) );
            }

            $category->update( $data );
            wp_send_json_success( array(
                'message' => 'Kategória bola aktualizovaná',
                'category' => $category->get_all_data(),
            ) );
        } else {
            // Create
            $category = new GuideCategory();
            $new_id = $category->create( $data );

            if ( ! $new_id ) {
                wp_send_json_error( array( 'message' => 'Chyba pri vytváraní kategórie' ) );
            }

            $category = new GuideCategory( $new_id );
            wp_send_json_success( array(
                'message' => 'Kategória bola vytvorená',
                'category' => $category->get_all_data(),
            ) );
        }
    }

    /**
     * Handle delete guide category AJAX
     */
    public function handle_delete_category() {
        if ( ! Security::verify_ajax_request( 'nonce' ) ) {
            return;
        }

        $category_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $category_id ) {
            wp_send_json_error( array( 'message' => 'ID kategórie chýba' ) );
        }

        $category = new GuideCategory( $category_id );

        if ( ! $category->exists() ) {
            wp_send_json_error( array( 'message' => 'Kategória nenájdená' ) );
        }

        if ( $category->delete() ) {
            wp_send_json_success( array( 'message' => 'Kategória bola odstránená' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Chyba pri odstraňovaní kategórie' ) );
        }
    }

    /**
     * Handle get guide category AJAX
     */
    public function handle_get_category() {
        if ( ! Security::verify_ajax_request( 'nonce' ) ) {
            return;
        }

        $category_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $category_id ) {
            wp_send_json_error( array( 'message' => 'ID kategórie chýba' ) );
        }

        $category = new GuideCategory( $category_id );

        if ( ! $category->exists() ) {
            wp_send_json_error( array( 'message' => 'Kategória nenájdená' ) );
        }

        wp_send_json_success( array(
            'category' => $category->get_all_data(),
        ) );
    }
}
