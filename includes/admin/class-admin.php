<?php
/**
 * Admin Class
 */

namespace HelpDesk\Admin;

class Admin {
    /**
     * Module status
     */
    private $module_status = array();

    /**
     * Constructor
     */
    public function __construct( $module_status ) {
        $this->module_status = $module_status;
        $this->setup_hooks();
    }

    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        add_action( 'admin_menu', array( $this, 'register_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Register all admin menus
     */
    public function register_menus() {
        // Main HelpDesk menu
        add_menu_page(
            __( 'HelpDesk', HELPDESK_TEXT_DOMAIN ),
            __( 'HelpDesk', HELPDESK_TEXT_DOMAIN ),
            'manage_helpdesk',
            'helpdesk',
            array( $this, 'render_dashboard' ),
            'dashicons-headphones',
            25
        );

        // Add Dashboard as first submenu item
        add_submenu_page(
            'helpdesk',
            __( 'Dashboard', HELPDESK_TEXT_DOMAIN ),
            __( 'Dashboard', HELPDESK_TEXT_DOMAIN ),
            'manage_helpdesk',
            'helpdesk',
            array( $this, 'render_dashboard' )
        );

        // HD - Human Development / HR Section
        global $submenu;
        if ( ! isset( $submenu['helpdesk'] ) ) {
            $submenu['helpdesk'] = array();
        }
        
        // Check if HD module is enabled
        $hd_enabled = get_option( 'helpdesk_enable_hd_module', true );
        
        if ( $hd_enabled ) {
            // Add HD separator before first HD item
            $submenu['helpdesk'][] = array( '─── HD ───', 'manage_helpdesk', '#', 0 );
            
            // Employees submenu
            if ( $this->module_status['employees'] ?? false ) {
                add_submenu_page(
                    'helpdesk',
                    __( 'Pracovníci', HELPDESK_TEXT_DOMAIN ),
                    __( 'Pracovníci', HELPDESK_TEXT_DOMAIN ),
                    'manage_helpdesk',
                    'helpdesk-employees',
                    array( $this, 'render_employees' )
                );
            }

            // Projects submenu
            if ( $this->module_status['projects'] ?? false ) {
                add_submenu_page(
                    'helpdesk',
                    __( 'Projekty', HELPDESK_TEXT_DOMAIN ),
                    __( 'Projekty', HELPDESK_TEXT_DOMAIN ),
                    'manage_helpdesk',
                    'helpdesk-projects',
                    array( $this, 'render_projects' )
                );
            }

            // Positions submenu
            add_submenu_page(
                'helpdesk',
                __( 'Pozície', HELPDESK_TEXT_DOMAIN ),
                __( 'Pozície', HELPDESK_TEXT_DOMAIN ),
                'manage_helpdesk',
                'helpdesk-positions',
                array( $this, 'render_positions' )
            );

            // Standby submenu
            if ( $this->module_status['standby'] ?? false ) {
                add_submenu_page(
                    'helpdesk',
                    __( 'Pohotovosť', HELPDESK_TEXT_DOMAIN ),
                    __( 'Pohotovosť', HELPDESK_TEXT_DOMAIN ),
                    'manage_helpdesk',
                    'helpdesk-standby',
                    array( $this, 'render_standby' )
                );
            }

            // Vacations submenu
            if ( $this->module_status['vacations'] ?? false ) {
                add_submenu_page(
                    'helpdesk',
                    __( 'Neprítomnosti', HELPDESK_TEXT_DOMAIN ),
                    __( 'Neprítomnosti', HELPDESK_TEXT_DOMAIN ),
                    'manage_helpdesk',
                    'helpdesk-vacations',
                    array( $this, 'render_vacations' )
                );
            }

            // Contacts submenu
            add_submenu_page(
                'helpdesk',
                __( 'Kontakty', HELPDESK_TEXT_DOMAIN ),
                __( 'Kontakty', HELPDESK_TEXT_DOMAIN ),
                'manage_helpdesk',
                'helpdesk-contacts',
                array( $this, 'render_contacts' )
            );

            // Communication Methods submenu
            if ( $this->module_status['communication-methods'] ?? false ) {
                add_submenu_page(
                    'helpdesk',
                    __( 'Spôsoby Komunikácie', HELPDESK_TEXT_DOMAIN ),
                    __( 'Spôsoby Komunikácie', HELPDESK_TEXT_DOMAIN ),
                    'manage_helpdesk',
                    'helpdesk-communication-methods',
                    array( $this, 'render_communication_methods' )
                );
            }
        }

        // AP - Application / Problems Section
        $ap_enabled = get_option( 'helpdesk_enable_ap_module', true );
        
        if ( $ap_enabled && ( $this->module_status['bugs'] ?? false ) ) {
            // Add AP separator before first AP item
            $submenu['helpdesk'][] = array( '─── AP ───', 'manage_helpdesk', '#', 0 );
            
            add_submenu_page(
                'helpdesk',
                __( 'Riešenia', HELPDESK_TEXT_DOMAIN ),
                __( 'Riešenia', HELPDESK_TEXT_DOMAIN ),
                'manage_helpdesk',
                'helpdesk-bugs',
                array( $this, 'render_bugs' )
            );

            add_submenu_page(
                'helpdesk',
                __( 'Problémy', HELPDESK_TEXT_DOMAIN ),
                __( 'Problémy', HELPDESK_TEXT_DOMAIN ),
                'manage_helpdesk',
                'helpdesk-bug-codes',
                array( $this, 'render_bug_codes' )
            );

            add_submenu_page(
                'helpdesk',
                __( 'Produkty', HELPDESK_TEXT_DOMAIN ),
                __( 'Produkty', HELPDESK_TEXT_DOMAIN ),
                'manage_helpdesk',
                'helpdesk-products',
                array( $this, 'render_products' )
            );

            add_submenu_page(
                'helpdesk',
                __( 'Operačné Systémy', HELPDESK_TEXT_DOMAIN ),
                __( 'Operačné Systémy', HELPDESK_TEXT_DOMAIN ),
                'manage_helpdesk',
                'helpdesk-operating-systems',
                array( $this, 'render_operating_systems' )
            );

            add_submenu_page(
                'helpdesk',
                __( 'Podpisy', HELPDESK_TEXT_DOMAIN ),
                __( 'Podpisy', HELPDESK_TEXT_DOMAIN ),
                'manage_helpdesk',
                'helpdesk-signatures',
                array( $this, 'render_signatures' )
            );

            add_submenu_page(
                'helpdesk',
                __( 'Všeobecné návody', HELPDESK_TEXT_DOMAIN ),
                __( 'Všeobecné návody', HELPDESK_TEXT_DOMAIN ),
                'manage_helpdesk',
                'helpdesk-general-guides',
                array( $this, 'render_general_guides' )
            );

            add_submenu_page(
                'helpdesk',
                __( 'Kategórie návodov', HELPDESK_TEXT_DOMAIN ),
                __( 'Kategórie návodov', HELPDESK_TEXT_DOMAIN ),
                'manage_helpdesk',
                'helpdesk-guide-categories',
                array( $this, 'render_guide_categories' )
            );

            add_submenu_page(
                'helpdesk',
                __( 'Linky návodov', HELPDESK_TEXT_DOMAIN ),
                __( 'Linky návodov', HELPDESK_TEXT_DOMAIN ),
                'manage_helpdesk',
                'helpdesk-guide-links',
                array( $this, 'render_guide_links' )
            );
        }

        // Settings submenu
        add_submenu_page(
            'helpdesk',
            __( 'Frontend Dashboard', HELPDESK_TEXT_DOMAIN ),
            __( '👥 Frontend Dashboard', HELPDESK_TEXT_DOMAIN ),
            'read',
            'helpdesk-frontend',
            array( $this, 'render_frontend_dashboard' )
        );

        // Settings submenu
        add_submenu_page(
            'helpdesk',
            __( 'Nastavenia', HELPDESK_TEXT_DOMAIN ),
            __( 'Nastavenia', HELPDESK_TEXT_DOMAIN ),
            'manage_helpdesk',
            'helpdesk-settings',
            array( $this, 'render_settings' )
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts( $hook ) {
        // Only enqueue on HelpDesk pages
        if ( strpos( $hook, 'helpdesk' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'helpdesk-admin',
            HELPDESK_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            HELPDESK_VERSION . '-' . time()
        );

        wp_enqueue_script(
            'helpdesk-admin',
            HELPDESK_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            HELPDESK_VERSION . '-' . time(),
            true
        );

        // Enqueue frontend dashboard script
        if ( strpos( $hook, 'helpdesk-frontend' ) !== false ) {
            wp_enqueue_script(
                'helpdesk-frontend-dashboard',
                HELPDESK_PLUGIN_URL . 'assets/js/frontend-dashboard.js',
                array( 'jquery' ),
                HELPDESK_VERSION,
                true
            );
        }

        // Localize script with data
        $dashboard_display = get_option( 'helpdesk_dashboard_display', array(
            'klapka' => true,
            'mobil' => true,
            'pozicia' => true,
            'poznamka_pracovnika' => true,
            'hd_kontakt' => true,
        ) );

        $dashboard_filters = get_option( 'helpdesk_dashboard_filters', array(
            'show_nw_projects' => false,
        ) );

        // Ensure boolean values for JavaScript
        $dashboard_filters['show_nw_projects'] = (bool) $dashboard_filters['show_nw_projects'];

        wp_localize_script(
            'helpdesk-admin',
            'helpdesk',
            array(
                'nonce' => wp_create_nonce( 'helpdesk-nonce' ),
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'dashboardDisplay' => $dashboard_display,
                'dashboardFilters' => $dashboard_filters,
            )
        );
    }

    /**
     * Render dashboard
     */
    public function render_dashboard() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-dashboard.php';
    }

    /**
     * Render employees page
     */
    public function render_employees() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-employees.php';
    }

    /**
     * Render projects page
     */
    public function render_projects() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-projects.php';
    }

    /**
     * Render bugs page
     */
    public function render_bugs() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-bugs.php';
    }

    /**
     * Render bug codes page
     */
    public function render_bug_codes() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-bug-codes.php';
    }

    /**
     * Render products page
     */
    public function render_products() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-products.php';
    }

    /**
     * Render operating systems page
     */
    public function render_operating_systems() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-operating-systems.php';
    }

    /**
     * Render contacts page
     */
    public function render_contacts() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-contacts.php';
    }

    /**
     * Render positions page
     */
    public function render_positions() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-positions.php';
    }

    /**
     * Render settings page
     */
    public function render_settings() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-settings.php';
    }

    /**
     * Render frontend dashboard page
     */
    public function render_frontend_dashboard() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/frontend-dashboard.php';
    }

    /**
     * Render standby page
     */
    public function render_standby() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-standby.php';
    }

    /**
     * Render communication methods page
     */
    public function render_communication_methods() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-communication-methods.php';
    }

    /**
     * Render signatures page
     */
    public function render_signatures() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-signatures.php';
    }

    /**
     * Render general guides page
     */
    public function render_general_guides() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-general-guides.php';
    }

    /**
     * Render vacations page
     */
    public function render_vacations() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-vacations.php';
    }

    /**
     * Render guide categories page
     */
    public function render_guide_categories() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-guide-categories.php';
    }

    /**
     * Render guide links page
     */
    public function render_guide_links() {
        require_once HELPDESK_PLUGIN_DIR . 'includes/views/admin-guide-links.php';
    }
}
