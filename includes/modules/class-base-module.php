<?php
/**
 * Base Module Class
 */

namespace HelpDesk\Modules;

abstract class BaseModule {
    /**
     * Module name
     */
    protected $module_name = '';

    /**
     * Module slug
     */
    protected $module_slug = '';

    /**
     * Menu page ID
     */
    protected $menu_page_id = '';

    /**
     * Constructor
     */
    public function __construct() {
        $this->register_hooks();
    }

    /**
     * Register WordPress hooks
     */
    protected function register_hooks() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
    }

    /**
     * Register admin menu
     */
    abstract public function register_menu();

    /**
     * Admin init hook
     */
    public function admin_init() {
        $this->register_scripts_styles();
    }

    /**
     * Register scripts and styles
     */
    protected function register_scripts_styles() {
        // Subclasses can override this method
    }

    /**
     * Check if user has capability
     */
    protected function check_capability( $capability = 'manage_helpdesk' ) {
        if ( ! current_user_can( $capability ) ) {
            wp_die( __( 'You do not have permission to access this page.', HELPDESK_TEXT_DOMAIN ) );
        }
    }

    /**
     * Verify nonce
     */
    protected function verify_nonce( $nonce_name, $nonce_value ) {
        if ( ! isset( $_REQUEST[ $nonce_name ] ) || ! wp_verify_nonce( $_REQUEST[ $nonce_name ], $nonce_value ) ) {
            wp_die( __( 'Security check failed.', HELPDESK_TEXT_DOMAIN ) );
        }
    }

    /**
     * Get module slug
     */
    public function get_slug() {
        return $this->module_slug;
    }

    /**
     * Get module name
     */
    public function get_name() {
        return $this->module_name;
    }
}
