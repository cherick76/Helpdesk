<?php
/**
 * Main HelpDesk Plugin Class
 */

namespace HelpDesk\Core;

class HelpDesk {
    /**
     * Instance of the plugin
     */
    private static $instance = null;

    /**
     * Module instances
     */
    private $modules = array();

    /**
     * Module status (enabled/disabled)
     */
    private $module_status = array();

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_module_status();
        $this->load_dependencies();
        $this->setup_hooks();
    }

    /**
     * Load module status from options
     */
    private function load_module_status() {
        $this->module_status = get_option( 'helpdesk_modules', array(
            'employees' => true,
            'projects' => true,
            'bugs' => true,
            'standby' => true,
            'communication-methods' => true,
            'vacations' => true,
        ) );
    }

    /**
     * Load all dependencies
     */
    private function load_dependencies() {
        // Load database models
        require_once HELPDESK_PLUGIN_DIR . 'includes/models/class-base-model.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/models/class-employee.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/models/class-project.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/models/class-bug.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/models/class-bug-code.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/models/class-product-lookup.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/models/class-contact-lookup.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/models/class-position.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/models/class-communication-method.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/models/class-operating-system.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/models/class-signature.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/models/class-general-guide.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/models/class-link.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/models/class-guide-category.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/models/class-guide-resource.php';

        // Load admin classes
        require_once HELPDESK_PLUGIN_DIR . 'includes/admin/class-admin.php';

        // Load frontend classes
        require_once HELPDESK_PLUGIN_DIR . 'includes/frontend/class-frontend.php';

        // Load modules
        require_once HELPDESK_PLUGIN_DIR . 'includes/modules/class-base-module.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/modules/employees/class-employees-module.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/modules/projects/class-projects-module.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/modules/bugs/class-bugs-module.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/modules/positions/class-positions-module.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/modules/standby/class-standby-module.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/modules/communication-methods/class-communication-methods-module.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/modules/vacations/class-vacations-module.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/modules/operating-systems/class-operating-systems-module.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/modules/signatures/class-signatures-module.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/modules/general-guides/class-general-guides-module.php';

        // Load utilities
        require_once HELPDESK_PLUGIN_DIR . 'includes/utils/class-validator.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/utils/class-database.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/utils/class-security.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/utils/class-query-cache.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/utils/class-migrations.php';
    }

    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        error_log( 'DEBUG: setup_hooks called' );
        add_action( 'init', array( $this, 'initialize_modules' ) );
        add_action( 'wp_ajax_helpdesk_run_migration', array( $this, 'handle_run_migration' ) );
        error_log( 'DEBUG: init hook registered' );
    }

    /**
     * Handle manual migration AJAX request
     */
    public function handle_run_migration() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Not authorized' ) );
        }
        
        check_ajax_referer( 'helpdesk_nonce', '_ajax_nonce' );
        
        \HelpDesk\Utils\Migrations::run_migrations();
        
        wp_send_json_success( array( 
            'message' => 'Migrácia bola vykonaná',
            'db_version' => get_option( 'helpdesk_db_version' )
        ) );
    }

    /**
     * Initialize all enabled modules
     */
    public function initialize_modules() {
        error_log( 'DEBUG: initialize_modules called' );
        
        // Run database migrations
        \HelpDesk\Utils\Migrations::run_migrations();
        
        // Initialize frontend (always available)
        new \HelpDesk\Frontend\Frontend( $this->module_status );

        if ( is_admin() ) {
            error_log( 'DEBUG: is_admin() returned true' );

            // Initialize admin FIRST (this registers the main HelpDesk menu)
            error_log( 'DEBUG: About to create Admin class' );
            new \HelpDesk\Admin\Admin( $this->module_status );
            error_log( 'DEBUG: Admin class created' );

            // Initialize modules
            if ( isset( $this->module_status['employees'] ) && $this->module_status['employees'] ) {
                $this->modules['employees'] = new \HelpDesk\Modules\Employees\EmployeesModule();
            }

            if ( isset( $this->module_status['projects'] ) && $this->module_status['projects'] ) {
                $this->modules['projects'] = new \HelpDesk\Modules\Projects\ProjectsModule();
            }

            if ( isset( $this->module_status['bugs'] ) && $this->module_status['bugs'] ) {
                $this->modules['bugs'] = new \HelpDesk\Modules\Bugs\BugsModule();
            }

            if ( isset( $this->module_status['standby'] ) && $this->module_status['standby'] ) {
                $this->modules['standby'] = new \HelpDesk\Modules\Standby\StandbyModule();
            }

            if ( isset( $this->module_status['communication-methods'] ) && $this->module_status['communication-methods'] ) {
                $this->modules['communication-methods'] = new \HelpDesk\Modules\CommunicationMethods\CommunicationMethodsModule();
            }

            if ( isset( $this->module_status['vacations'] ) && $this->module_status['vacations'] ) {
                $this->modules['vacations'] = new \HelpDesk\Modules\Vacations\VacationsModule();
            }

            // Positions module (always load for support in employee management)
            $this->modules['positions'] = new \HelpDesk\Modules\Positions\PositionsModule();
            
            // Operating Systems module (always load for bug codes and references)
            $this->modules['operating-systems'] = new \HelpDesk\Modules\OperatingSystems\OperatingSystemsModule();

            // Signatures module (always load for Application Support)
            $this->modules['signatures'] = new \HelpDesk\Modules\Signatures\SignaturesModule();

            // General Guides module (always load for Application Support)
            error_log( 'DEBUG: About to create GeneralGuidesModule' );
            $this->modules['general-guides'] = new \HelpDesk\Modules\GeneralGuides\GeneralGuidesModule();
            error_log( 'DEBUG: GeneralGuidesModule created' );
        }
    }

    /**
     * Render dashboard
     */
    public function render_dashboard() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'HelpDesk', HELPDESK_TEXT_DOMAIN ) . '</h1>';
        echo '<p>' . esc_html__( 'Správa HelpDesk modulov', HELPDESK_TEXT_DOMAIN ) . '</p>';
        echo '</div>';
    }

    /**
     * Get module status
     */
    public function is_module_enabled( $module ) {
        return isset( $this->module_status[ $module ] ) && $this->module_status[ $module ];
    }

    /**
     * Set module status
     */
    public function set_module_status( $module, $enabled ) {
        $this->module_status[ $module ] = (bool) $enabled;
        update_option( 'helpdesk_modules', $this->module_status );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        \HelpDesk\Utils\Database::create_tables();
        update_option( 'helpdesk_modules', array(
            'employees' => true,
            'projects' => true,
            'bugs' => true,
            'standby' => true,
            'communication-methods' => true,
            'vacations' => true,
        ) );
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}
