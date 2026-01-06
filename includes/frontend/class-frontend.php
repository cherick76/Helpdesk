<?php
/**
 * Frontend Class
 */

namespace HelpDesk\Frontend;

class Frontend {
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
        add_shortcode( 'helpdesk', array( $this, 'render_frontend' ) );
        add_shortcode( 'helpdesk_dashboard', array( $this, 'render_dashboard' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_footer', array( $this, 'print_modals' ) );
        
        // Custom template for full screen
        add_filter( 'template_include', array( $this, 'helpdesk_template' ) );
    }

    /**
     * Custom template for HelpDesk page
     */
    public function helpdesk_template( $template ) {
        if ( is_singular() && has_shortcode( get_post()->post_content, 'helpdesk' ) ) {
            return HELPDESK_PLUGIN_DIR . 'includes/frontend/template-helpdesk.php';
        }
        return $template;
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if ( ! is_singular() || ! has_shortcode( get_post()->post_content, 'helpdesk' ) ) {
            return;
        }

        wp_enqueue_style( 'helpdesk-frontend', HELPDESK_PLUGIN_URL . 'assets/css/frontend.css', array(), HELPDESK_VERSION );
        
        // Enqueue jQuery for AJAX and tab navigation
        wp_enqueue_script( 'jquery' );
        
        // Enqueue frontend script - jQuery is optional, script works with or without it
        wp_enqueue_script( 
            'helpdesk-frontend', 
            HELPDESK_PLUGIN_URL . 'assets/js/frontend.js', 
            array( 'jquery' ), 
            HELPDESK_VERSION, 
            true  // Load in footer
        );

        // Pass PHP data to JavaScript
        wp_localize_script( 'helpdesk-frontend', 'helpdesk', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'helpdesk-nonce' ),
            'debug' => defined( 'WP_DEBUG' ) && WP_DEBUG,
        ) );
    }

    /**
     * Render frontend dashboard
     */
    public function render_frontend() {
        if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
            return '<div class="helpdesk-notice">' . __( 'Please log in to access HelpDesk.', HELPDESK_TEXT_DOMAIN ) . '</div>';
        }

        // Enqueue scripts and styles now (needed for shortcode rendering)
        // This ensures scripts are loaded before content is rendered
        $this->enqueue_scripts();


        // Get projects and bugs data
        $projects = \HelpDesk\Models\Project::get_all();
        $bugs = \HelpDesk\Models\Bug::get_all();
        $current_user = wp_get_current_user();

        // Enrich projects with PM, SLA manager names, and detailed employee info
        global $wpdb;
        $employees_table = \HelpDesk\Utils\Database::get_employees_table();
        $project_employee_table = \HelpDesk\Utils\Database::get_project_employee_table();
        $positions_table = \HelpDesk\Utils\Database::get_positions_table();
        $standby_table = \HelpDesk\Utils\Database::get_standby_table();
        $today = current_time( 'Y-m-d' );

        foreach ( $projects as &$project ) {
            // Add PM and SLA manager names
            if ( ! empty( $project['pm_manazer_id'] ) ) {
                $pm = $wpdb->get_row( $wpdb->prepare(
                    "SELECT meno_priezvisko FROM $employees_table WHERE id = %d",
                    $project['pm_manazer_id']
                ), ARRAY_A );
                $project['pm_name'] = $pm ? $pm['meno_priezvisko'] : '';
            }
            if ( ! empty( $project['sla_manazer_id'] ) ) {
                $sla = $wpdb->get_row( $wpdb->prepare(
                    "SELECT meno_priezvisko FROM $employees_table WHERE id = %d",
                    $project['sla_manazer_id']
                ), ARRAY_A );
                $project['sla_name'] = $sla ? $sla['meno_priezvisko'] : '';
            }

            // Get detailed employee information (like in handle_search_projects)
            $project_employees = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT e.id, e.meno_priezvisko, e.klapka, e.mobil, e.poznamka, e.pozicia_id, e.nepritomnost_od, e.nepritomnost_do, e.komunikacne_kanaly, pp.is_hlavny, pp.nemenit, 'project' as emp_type
                     FROM {$employees_table} e
                     INNER JOIN {$project_employee_table} pp ON e.id = pp.pracovnik_id
                     WHERE pp.projekt_id = %d
                     ORDER BY pp.is_hlavny DESC, e.meno_priezvisko ASC",
                    $project['id']
                ),
                ARRAY_A
            );

            // Get standby employees (only those with active standby)
            $standby_employees = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT e.id, e.meno_priezvisko, e.klapka, e.mobil, e.poznamka, e.pozicia_id, e.nepritomnost_od, e.nepritomnost_do, e.komunikacne_kanaly, 0 as is_hlavny, 0 as nemenit, 'standby' as emp_type, s.pohotovost_od, s.pohotovost_do
                     FROM {$employees_table} e
                     INNER JOIN {$standby_table} s ON e.id = s.pracovnik_id
                     WHERE (e.id, s.id) IN (
                        SELECT pracovnik_id, MAX(id) 
                        FROM {$standby_table}
                        WHERE projekt_id = %d AND je_aktivna = 1
                        GROUP BY pracovnik_id
                     )
                     AND s.projekt_id = %d
                     ORDER BY e.meno_priezvisko ASC",
                    $project['id'],
                    $project['id']
                ),
                ARRAY_A
            );

            // Merge employees
            $all_employees = $project_employees;
            if ( $standby_employees ) {
                $project_emp_ids = wp_list_pluck( $project_employees, 'id' );
                foreach ( $standby_employees as $standby_emp ) {
                    if ( ! in_array( $standby_emp['id'], $project_emp_ids, true ) ) {
                        $all_employees[] = $standby_emp;
                    }
                }
            }

            // Get position names and standby info for employees
            if ( $all_employees ) {
                foreach ( $all_employees as &$emp ) {
                    // Get position name if pozicia_id exists
                    if ( ! empty( $emp['pozicia_id'] ) ) {
                        $emp['pozicia_nazov'] = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT profesia FROM {$positions_table} WHERE id = %d",
                                $emp['pozicia_id']
                            )
                        );
                    } else {
                        $emp['pozicia_nazov'] = null;
                    }

                    // Get standby info if not already set
                    if ( empty( $emp['pohotovost_od'] ) || empty( $emp['pohotovost_do'] ) ) {
                        $standby = $wpdb->get_row(
                            $wpdb->prepare(
                                "SELECT pohotovost_od, pohotovost_do FROM {$standby_table}
                                 WHERE pracovnik_id = %d AND projekt_id = %d
                                 AND je_aktivna = 1
                                 ORDER BY pohotovost_od DESC
                                 LIMIT 1",
                                $emp['id'],
                                $project['id']
                            ),
                            ARRAY_A
                        );

                        if ( $standby ) {
                            $emp['pohotovost_od'] = $standby['pohotovost_od'];
                            $emp['pohotovost_do'] = $standby['pohotovost_do'];
                        }
                    }
                }
                unset( $emp );
            }

            // Check if employees have standby for today
            if ( $all_employees ) {
                foreach ( $all_employees as &$emp ) {
                    $has_standby = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT COUNT(*) FROM {$standby_table}
                             WHERE pracovnik_id = %d AND projekt_id = %d
                             AND pohotovost_od <= %s AND pohotovost_do >= %s
                             AND je_aktivna = 1",
                            $emp['id'],
                            $project['id'],
                            $today,
                            $today
                        )
                    );
                    $emp['has_standby_today'] = $has_standby > 0 ? 1 : 0;
                }
                unset( $emp );
            }

            $project['employees'] = $all_employees;
        }
        unset( $project );

        // Debug: Log number of projects and bugs
        error_log( 'HelpDesk Frontend: Projects count=' . count( $projects ) . ', Bugs count=' . count( $bugs ) );
        
        // Log first project to see structure
        if ( ! empty( $projects ) ) {
            error_log( 'First project: ' . wp_json_encode( $projects[0] ) );
        }

        ob_start();
        ?>
        <style>
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                height: 100% !important;
                width: 100% !important;
                overflow: hidden !important;
            }
            #wpadminbar, #wpadminbar * {
                display: none !important;
            }
            body.admin-bar {
                padding-top: 0 !important;
            }
            .site-header, header.site-header, header, .header {
                display: none !important;
            }
            footer, .site-footer, .wp-site-blocks footer, .footer {
                display: none !important;
            }
            #page, .site-content, .entry-content, main, main.site-main, .wp-site-blocks, .container {
                margin: 0 !important;
                padding: 0 !important;
                height: 100% !important;
                width: 100% !important;
                max-width: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                overflow: hidden !important;
            }
            .wp-block-group, .wp-block-column {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .helpdesk-frontend-wrapper {
                width: 100vw !important;
                height: 100vh !important;
            }
        </style>
        <div class="helpdesk-frontend-wrapper">
            <div class="helpdesk-frontend-header">
                <nav class="helpdesk-frontend-nav">
                    <ul>
                        <li><a href="#dashboard" class="nav-link active" data-section="dashboard">📊 Dashboard</a></li>
                        <li><a href="#projects" class="nav-link" data-section="projects">📁 Projekty</a></li>
                        <li><a href="#bugs" class="nav-link" data-section="bugs">✅ Riešenia</a></li>
                        <li class="nav-user">
                            <span><?php echo esc_html( $current_user->display_name ); ?></span>
                            <a href="<?php echo esc_url( wp_logout_url() ); ?>">🚪 Odhlásenie</a>
                        </li>
                    </ul>
                </nav>
            </div>

            <div class="helpdesk-frontend-container">
                <!-- Dashboard Section -->
                <section id="dashboard" class="helpdesk-section active">
                    <div class="helpdesk-section-content">
                        <h2 style="margin-top: 0; color: #0073aa;">📊 Dashboard</h2>
                        
                        <!-- Statistics -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
                            <div style="background: linear-gradient(135deg, #0073aa 0%, #005a87 100%); color: white; padding: 20px; border-radius: 8px;">
                                <p style="margin: 0; opacity: 0.9;">Projektov</p>
                                <h3 style="margin: 10px 0 0 0; font-size: 28px;"><?php echo count( array_filter( $projects, function( $p ) { return ! empty( $p['zakaznicke_cislo'] ); } ) ); ?></h3>
                            </div>
                            <div style="background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%); color: white; padding: 20px; border-radius: 8px;">
                                <p style="margin: 0; opacity: 0.9;">Riešení</p>
                                <h3 style="margin: 10px 0 0 0; font-size: 28px;"><?php echo count( $bugs ); ?></h3>
                            </div>
                        </div>

                        <div class="helpdesk-dashboard-wrapper">
                            <!-- Projects Search -->
                            <div class="helpdesk-admin-container">
                                <h3>📁 Vyhľadať Projekty</h3>
                                <input type="text" id="dashboard-project-search" class="helpdesk-search-input" placeholder="Napíšte názov projektu...">
                                <div id="dashboard-search-results" style="margin-top: 15px;">
                                    <div id="dashboard-search-results-tbody" style="display: grid; gap: 10px;">
                                        <?php if ( ! empty( $projects ) ) : ?>
                                            <?php 
                                                $visible_projects = 0;
                                                foreach ( $projects as $project ) : 
                                                    if ( empty( $project['zakaznicke_cislo'] ) ) continue;
                                                    $visible_projects++;
                                                    
                                                    // Separate employees into project and standby
                                                    $employees = $project['employees'] ?? array();
                                                    $excel_employees = array_filter( $employees, function( $e ) { return $e['emp_type'] === 'project'; } );
                                                    $standby_employees = array_filter( $employees, function( $e ) { return $e['emp_type'] === 'standby'; } );
                                            ?>
                                                <div class="project-result-item" style="padding: 15px; background: white; border: 1px solid #ddd; border-left: 4px solid #0073aa; border-radius: 4px;" data-project-name="<?php echo esc_attr( strtolower( $project['zakaznicke_cislo'] ?? '' ) ); ?>">
                                                    <div style="margin-bottom: 12px;">
                                                        <strong style="font-size: 16px; color: #0073aa;">Zákaznícke číslo:</strong> <?php echo esc_html( $project['zakaznicke_cislo'] ?? '--' ); ?>
                                                    </div>

                                                    <?php if ( ! empty( $excel_employees ) ) : ?>
                                                        <div style="margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                                                            <strong style="display: block; margin-bottom: 6px;">📋 Pracovníci z excelu:</strong>
                                                            <div style="font-size: 13px; margin-left: 8px;">
                                                                <?php foreach ( $excel_employees as $emp ) : 
                                                                    $is_hlavny = $emp['is_hlavny'] == 1 || $emp['is_hlavny'] === '1' || $emp['is_hlavny'] === true;
                                                                    $has_standby = ! empty( $emp['pohotovost_od'] ) && ! empty( $emp['pohotovost_do'] );
                                                                    $badge = $is_hlavny ? '⭐ ' : '';
                                                                    $standby_badge = $has_standby ? '🚨 ' : '';
                                                                    $style = $is_hlavny ? 'font-weight: bold; color: #d47e2e;' : '';
                                                                    
                                                                    $info_parts = array();
                                                                    $info_parts[] = esc_html( $emp['klapka'] ?? '' );
                                                                    if ( ! empty( $emp['mobil'] ) ) {
                                                                        $info_parts[] = esc_html( $emp['mobil'] );
                                                                    }
                                                                    if ( ! empty( $emp['pozicia_nazov'] ) ) {
                                                                        $info_parts[] = esc_html( $emp['pozicia_nazov'] );
                                                                    }
                                                                    $info_str = implode( ' | ', array_filter( $info_parts ) );
                                                                ?>
                                                                    <div style="<?php echo $style; ?> margin-bottom: 4px;"><?php echo $standby_badge . $badge . esc_html( $emp['meno_priezvisko'] ) . ' (' . $info_str . ')'; ?></div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ( ! empty( $standby_employees ) ) : ?>
                                                        <div style="margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                                                            <strong style="display: block; margin-bottom: 6px;">📅 Pracovníci z pohotovosti v CRM:</strong>
                                                            <div style="font-size: 13px; margin-left: 8px;">
                                                                <?php foreach ( $standby_employees as $emp ) : 
                                                                    $info_parts = array();
                                                                    $info_parts[] = esc_html( $emp['klapka'] ?? '' );
                                                                    if ( ! empty( $emp['mobil'] ) ) {
                                                                        $info_parts[] = esc_html( $emp['mobil'] );
                                                                    }
                                                                    if ( ! empty( $emp['pozicia_nazov'] ) ) {
                                                                        $info_parts[] = esc_html( $emp['pozicia_nazov'] );
                                                                    }
                                                                    $info_str = implode( ' | ', array_filter( $info_parts ) );
                                                                ?>
                                                                    <div style="margin-bottom: 4px;">🚨 <?php echo esc_html( $emp['meno_priezvisko'] ) . ' (' . $info_str . ')'; ?></div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ( empty( $excel_employees ) && empty( $standby_employees ) ) : ?>
                                                        <em style="color: #999;">Žiadni pracovníci</em>
                                                    <?php endif; ?>

                                                    <?php if ( ! empty( $project['poznamka'] ) ) : ?>
                                                        <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid #eee; font-size: 12px;">
                                                            <strong>Poznámka:</strong><br><?php echo nl2br( esc_html( $project['poznamka'] ) ); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if ( $visible_projects === 0 ) : ?>
                                                <p style="color: #999; font-style: italic;">Žiadne projekty s číslom</p>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <p style="color: #999; font-style: italic;">Žiadne projekty v databáze</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Bugs Search -->
                            <div class="helpdesk-admin-container">
                                <h3>✅ Vyhľadať Riešenia</h3>
                                <input type="text" id="dashboard-bug-search" class="helpdesk-search-input" placeholder="Napíšte názov riešenia...">
                                <div id="dashboard-bug-results" style="margin-top: 15px;">
                                    <div id="dashboard-bug-results-tbody" style="display: grid; gap: 10px;">
                                        <?php if ( ! empty( $bugs ) ) : ?>
                                            <?php foreach ( $bugs as $bug ) : 
                                                if ( empty( $bug['nazov'] ) ) continue;
                                                $product_name = '--';
                                                if ( ! empty( $bug['produkt_id'] ) ) {
                                                    $product = \HelpDesk\Models\Product::get_by_id( absint( $bug['produkt_id'] ) );
                                                    if ( $product ) {
                                                        $product_name = $product->get( 'nazov' );
                                                    }
                                                }
                                            ?>
                                                <div class="bug-result-item" style="padding: 12px; background: white; border-left: 4px solid #4CAF50; border-radius: 4px; cursor: pointer;" data-bug-name="<?php echo esc_attr( strtolower( $bug['nazov'] ?? '' ) ); ?>" data-bug-id="<?php echo absint( $bug['id'] ); ?>">
                                                    <strong><?php echo esc_html( $bug['nazov'] ?? '--' ); ?></strong>
                                                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">📦 <?php echo esc_html( $product_name ); ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Projects Section -->
                <section id="projects" class="helpdesk-section">
                    <div class="helpdesk-section-content">
                        <h2 style="margin-top: 0; color: #0073aa;">📁 Projekty Helpdesk</h2>
                        <input type="text" id="projects-search" class="helpdesk-search-input" placeholder="Vyhľadať projekt...">
                        
                        <div style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-top: 20px;" id="projects-grid">
                            <?php if ( ! empty( $projects ) ) : ?>
                                <?php foreach ( $projects as $project ) : 
                                    if ( empty( $project['zakaznicke_cislo'] ) ) continue;
                                    
                                    // Separate employees into project and standby
                                    $employees = $project['employees'] ?? array();
                                    $excel_employees = array_filter( $employees, function( $e ) { return $e['emp_type'] === 'project'; } );
                                    $standby_employees = array_filter( $employees, function( $e ) { return $e['emp_type'] === 'standby'; } );
                                ?>
                                    <div class="project-card" style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd; border-left: 4px solid #0073aa;" data-project-name="<?php echo esc_attr( strtolower( $project['zakaznicke_cislo'] ?? '' ) ); ?>">
                                        <div style="margin-bottom: 15px;">
                                            <h3 style="margin: 0 0 8px 0; color: #0073aa; font-size: 18px;"><?php echo esc_html( $project['zakaznicke_cislo'] ?? '--' ); ?></h3>
                                            <p style="margin: 0 0 10px 0; color: #666; font-size: 13px;">
                                                📞 <?php echo esc_html( $project['hd_kontakt'] ?? '--' ); ?>
                                            </p>
                                            <div style="background: #f9f9f9; padding: 8px; border-radius: 4px; font-size: 12px; color: #555; margin-bottom: 10px;">
                                                <?php if ( ! empty( $project['pm_name'] ) ) : ?>
                                                    <div style="margin-bottom: 4px;"><strong>PM:</strong> <?php echo esc_html( $project['pm_name'] ); ?></div>
                                                <?php endif; ?>
                                                <?php if ( ! empty( $project['sla_name'] ) ) : ?>
                                                    <div><strong>SLA:</strong> <?php echo esc_html( $project['sla_name'] ); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <?php if ( ! empty( $excel_employees ) ) : ?>
                                            <div style="margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                                                <strong style="display: block; margin-bottom: 6px; color: #333;">📋 Pracovníci z excelu:</strong>
                                                <div style="font-size: 12px; margin-left: 8px;">
                                                    <?php foreach ( $excel_employees as $emp ) : 
                                                        $is_hlavny = $emp['is_hlavny'] == 1 || $emp['is_hlavny'] === '1' || $emp['is_hlavny'] === true;
                                                        $has_standby = ! empty( $emp['pohotovost_od'] ) && ! empty( $emp['pohotovost_do'] );
                                                        $badge = $is_hlavny ? '⭐ ' : '';
                                                        $standby_badge = $has_standby ? '🚨 ' : '';
                                                        $style = $is_hlavny ? 'font-weight: bold; color: #d47e2e;' : '';
                                                        
                                                        $info_parts = array();
                                                        $info_parts[] = esc_html( $emp['klapka'] ?? '' );
                                                        if ( ! empty( $emp['mobil'] ) ) {
                                                            $info_parts[] = esc_html( $emp['mobil'] );
                                                        }
                                                        if ( ! empty( $emp['pozicia_nazov'] ) ) {
                                                            $info_parts[] = esc_html( $emp['pozicia_nazov'] );
                                                        }
                                                        $info_str = implode( ' | ', array_filter( $info_parts ) );
                                                    ?>
                                                        <div style="<?php echo $style; ?> margin-bottom: 4px;"><?php echo $standby_badge . $badge . esc_html( $emp['meno_priezvisko'] ) . ' (' . $info_str . ')'; ?></div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ( ! empty( $standby_employees ) ) : ?>
                                            <div style="margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                                                <strong style="display: block; margin-bottom: 6px; color: #333;">📅 Pracovníci z pohotovosti v CRM:</strong>
                                                <div style="font-size: 12px; margin-left: 8px;">
                                                    <?php foreach ( $standby_employees as $emp ) : 
                                                        $info_parts = array();
                                                        $info_parts[] = esc_html( $emp['klapka'] ?? '' );
                                                        if ( ! empty( $emp['mobil'] ) ) {
                                                            $info_parts[] = esc_html( $emp['mobil'] );
                                                        }
                                                        if ( ! empty( $emp['pozicia_nazov'] ) ) {
                                                            $info_parts[] = esc_html( $emp['pozicia_nazov'] );
                                                        }
                                                        $info_str = implode( ' | ', array_filter( $info_parts ) );
                                                    ?>
                                                        <div style="margin-bottom: 4px;">🚨 <?php echo esc_html( $emp['meno_priezvisko'] ) . ' (' . $info_str . ')'; ?></div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ( empty( $excel_employees ) && empty( $standby_employees ) ) : ?>
                                            <div style="margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                                                <em style="color: #999;">Žiadni pracovníci</em>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ( ! empty( $project['poznamka'] ) ) : ?>
                                            <div style="font-size: 12px; color: #555;">
                                                <strong>Poznámka:</strong><br><?php echo nl2br( esc_html( $project['poznamka'] ) ); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p style="color: #999;">Žiadne projekty</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Bugs Section -->
                <section id="bugs" class="helpdesk-section">
                    <div class="helpdesk-section-content">
                        <h2 style="margin-top: 0; color: #0073aa;">✅ Aplikačná Podpora - Riešenia</h2>
                        <input type="text" id="bugs-search" class="helpdesk-search-input" placeholder="Vyhľadať riešenie...">
                        
                        <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                            <thead>
                                <tr style="background: #f5f5f5;">
                                    <th style="padding: 15px; border-bottom: 2px solid #ddd;">🔧 Názov</th>
                                    <th style="padding: 15px; border-bottom: 2px solid #ddd;">📦 Produkt</th>
                                    <th style="padding: 15px; border-bottom: 2px solid #ddd;">🏷️ Tagy</th>
                                    <th style="padding: 15px; border-bottom: 2px solid #ddd; text-align: center;">⚙️ Akcie</th>
                                </tr>
                            </thead>
                            <tbody id="bugs-table-body">
                                <?php if ( ! empty( $bugs ) ) : ?>
                                    <?php foreach ( $bugs as $bug ) : 
                                        $product_name = '--';
                                        if ( ! empty( $bug['produkt_id'] ) ) {
                                            $product = \HelpDesk\Models\Product::get_by_id( absint( $bug['produkt_id'] ) );
                                            if ( $product ) {
                                                $product_name = $product->get( 'nazov' );
                                            }
                                        }
                                        
                                        $tagy = array();
                                        if ( ! empty( $bug['tagy'] ) ) {
                                            $decoded = json_decode( $bug['tagy'], true );
                                            $tagy = is_array( $decoded ) ? $decoded : array();
                                        }
                                    ?>
                                        <tr style="border-bottom: 1px solid #ddd;" data-bug-name="<?php echo esc_attr( strtolower( $bug['nazov'] ?? '' ) ); ?>">
                                            <td style="padding: 15px; font-weight: 500;"><?php echo esc_html( $bug['nazov'] ?? '--' ); ?></td>
                                            <td style="padding: 15px;"><?php echo esc_html( $product_name ); ?></td>
                                            <td style="padding: 15px;">
                                                <?php if ( ! empty( $tagy ) ) : ?>
                                                    <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                                                        <?php foreach ( $tagy as $tag ) : ?>
                                                            <span style="background-color: #e7f3ff; color: #0073aa; padding: 3px 8px; border-radius: 3px; font-size: 12px; white-space: nowrap;">
                                                                <?php echo esc_html( $tag ); ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else : ?>
                                                    <span style="color: #999;">--</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 15px; text-align: center;">
                                                <button class="button button-small button-primary frontend-view-bug" data-bug-id="<?php echo absint( $bug['id'] ); ?>" style="cursor: pointer;">👁️ Detaily</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4" style="padding: 40px; text-align: center; color: #999;">Žiadne riešenia</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <!-- Bug Detail Modal -->
        <div id="bug-detail-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 10000; padding: 20px;">
            <div style="background: white; border-radius: 8px; max-width: 700px; max-height: 80vh; overflow-y: auto; margin: auto; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); padding: 30px;">
                <button type="button" id="close-modal-btn" style="position: absolute; top: 10px; right: 15px; border: none; background: none; font-size: 28px; cursor: pointer; color: #999;">×</button>
                <div id="bug-detail-content">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Print modal templates
     */
    public function print_modals() {
        if ( ! is_singular() || ! has_shortcode( get_post()->post_content, 'helpdesk' ) ) {
            return;
        }

        // Modals will be printed here
        echo '<!-- HelpDesk Modals -->';
    }

    /**
     * Render dashboard with project search
     */
    public function render_dashboard() {
        if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
            return '<div class="helpdesk-notice">' . __( 'Prosím prihláste sa pre prístup k HelpDesk.', HELPDESK_TEXT_DOMAIN ) . '</div>';
        }

        // Enqueue scripts and styles
        $this->enqueue_scripts();

        // Load dashboard template
        ob_start();
        include HELPDESK_PLUGIN_DIR . 'includes/frontend/dashboard-template.php';
        return ob_get_clean();
    }
}
