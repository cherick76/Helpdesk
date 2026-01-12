<?php
/**
 * Plugin Name: HelpDesk
 * Plugin URI: https://example.com/helpdesk
 * Description: Modulárny plugin pre správu číselníkov pracovníkov, projektov a chýb v helpdesk prostredí
 * Version: 1.0.27
 * Author: HelpDesk Team
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: helpdesk
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enable error logging pre debugging
if ( ! defined( 'WP_DEBUG_LOG' ) ) {
    define( 'WP_DEBUG_LOG', true );
}


// Plugin constants
define( 'HELPDESK_VERSION', '1.0.26' );
define( 'HELPDESK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HELPDESK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HELPDESK_PLUGIN_FILE', __FILE__ );
define( 'HELPDESK_TEXT_DOMAIN', 'helpdesk' );

// Include main plugin class
require_once HELPDESK_PLUGIN_DIR . 'includes/class-helpdesk.php';

/**
 * Initialize the plugin
 */
function helpdesk_init() {
    // Load text domain for translations
    load_plugin_textdomain(
        HELPDESK_TEXT_DOMAIN,
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );

    // Initialize the plugin
    \HelpDesk\Core\HelpDesk::get_instance();
}

add_action( 'plugins_loaded', 'helpdesk_init' );

/**
 * Plugin activation hook
 */
function helpdesk_activate() {
    try {
        // Zaistíme, že máme wp-admin funkcie
        if ( ! function_exists( 'dbDelta' ) ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        // Priamo loadneme Database class - nemôžeme sa spoliehať na autoloader v aktivácii
        require_once HELPDESK_PLUGIN_DIR . 'includes/utils/class-database.php';
        require_once HELPDESK_PLUGIN_DIR . 'includes/utils/class-migrations.php';

        // Check database version
        $db_version = get_option('helpdesk_db_version', '0');
        $current_version = HELPDESK_VERSION;
        
        // Always run create_tables - dbDelta is smart enough to handle schema changes
        \HelpDesk\Utils\Database::create_tables();
        
        // Run all migrations
        \HelpDesk\Utils\Migrations::run_migrations();
        
        // Update database version
        update_option('helpdesk_db_version', $current_version);
        
        // Add manage_helpdesk capability to administrator role
        $admin_role = get_role( 'administrator' );
        if ( $admin_role && ! $admin_role->has_cap( 'manage_helpdesk' ) ) {
            $admin_role->add_cap( 'manage_helpdesk' );
        }
        
        \HelpDesk\Core\HelpDesk::get_instance()->activate();
    } catch ( Exception $e ) {
        // Log chybu ale nedopusť aby zablokovala aktiváciu
        error_log( 'HelpDesk aktivácia - chyba: ' . $e->getMessage() );
    }
}

register_activation_hook( HELPDESK_PLUGIN_FILE, 'helpdesk_activate' );

/**
 * Plugin deactivation hook
 */
function helpdesk_deactivate() {
    \HelpDesk\Core\HelpDesk::get_instance()->deactivate();
}

register_deactivation_hook( HELPDESK_PLUGIN_FILE, 'helpdesk_deactivate' );

/**
 * Check and migrate database on admin init (for schema updates after activation)
 */
function helpdesk_check_db_migration() {
    // Ak nie sme v admin, nepokračujeme
    if ( ! is_admin() ) {
        return;
    }

    // Ensure administrator has manage_helpdesk capability
    $admin_role = get_role( 'administrator' );
    if ( $admin_role && ! $admin_role->has_cap( 'manage_helpdesk' ) ) {
        $admin_role->add_cap( 'manage_helpdesk' );
    }

    $db_version = get_option( 'helpdesk_db_version', '0' );
    
    // Ak je verzia iná, spustíme migráciu
    if ( version_compare( $db_version, HELPDESK_VERSION, '<' ) ) {
        \HelpDesk\Utils\Database::create_tables();
        update_option( 'helpdesk_db_version', HELPDESK_VERSION );
    }
}

add_action( 'admin_init', 'helpdesk_check_db_migration' );
/**
 * AJAX action to force database migration
 */
function helpdesk_force_migration() {
    check_ajax_referer( 'helpdesk-nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Insufficient permissions' );
    }
    
    // Delete the version option to force migration
    delete_option( 'helpdesk_db_version' );
    
    // Run migration immediately
    \HelpDesk\Utils\Database::create_tables();
    update_option( 'helpdesk_db_version', HELPDESK_VERSION );
    
    wp_send_json_success( 'Database migration completed' );
}

add_action( 'wp_ajax_helpdesk_force_migration', 'helpdesk_force_migration' );
