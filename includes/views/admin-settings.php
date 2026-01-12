<?php
/**
 * Settings Admin View
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$module_status = get_option( 'helpdesk_modules', array(
    'employees' => true,
    'projects' => true,
    'bugs' => true,
    'standby' => true,
) );

$hd_enabled = get_option( 'helpdesk_enable_hd_module', true );
$ap_enabled = get_option( 'helpdesk_enable_ap_module', true );

$dashboard_display = get_option( 'helpdesk_dashboard_display', array(
    'nazov_projektu' => true,
    'klapka' => true,
    'mobil' => true,
    'pozicia' => true,
    'poznamka_pracovnika' => true,
    'hd_kontakt' => true,
) );

$dashboard_filters = get_option( 'helpdesk_dashboard_filters', array(
    'show_nw_projects' => false,
) );

$standby_settings = get_option( 'helpdesk_standby_settings', array(
    'auto_delete_enabled' => false,
    'auto_delete_days' => 365,
) );

$ap_hd_settings = get_option( 'helpdesk_ap_hd_settings', array(
    'pracovnik_id' => 0,
) );

// Get active tab from query parameter
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'ap';

if ( isset( $_POST['submit'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'helpdesk-settings' ) ) {
    // Save HD and AP module enable/disable settings
    update_option( 'helpdesk_enable_hd_module', isset( $_POST['enable_hd_module'] ) ? true : false );
    update_option( 'helpdesk_enable_ap_module', isset( $_POST['enable_ap_module'] ) ? true : false );

    $module_status['employees'] = isset( $_POST['module_employees'] ) ? true : false;
    $module_status['projects'] = isset( $_POST['module_projects'] ) ? true : false;
    $module_status['bugs'] = isset( $_POST['module_bugs'] ) ? true : false;
    $module_status['standby'] = isset( $_POST['module_standby'] ) ? true : false;

    update_option( 'helpdesk_modules', $module_status );

    // Save dashboard display settings
    $dashboard_display['nazov_projektu'] = isset( $_POST['display_nazov_projektu'] ) ? true : false;
    $dashboard_display['klapka'] = isset( $_POST['display_klapka'] ) ? true : false;
    $dashboard_display['mobil'] = isset( $_POST['display_mobil'] ) ? true : false;
    $dashboard_display['pozicia'] = isset( $_POST['display_pozicia'] ) ? true : false;
    $dashboard_display['poznamka_pracovnika'] = isset( $_POST['display_poznamka_pracovnika'] ) ? true : false;
    $dashboard_display['hd_kontakt'] = isset( $_POST['display_hd_kontakt'] ) ? true : false;

    update_option( 'helpdesk_dashboard_display', $dashboard_display );

    // Save dashboard filter settings
    $dashboard_filters['show_nw_projects'] = isset( $_POST['show_nw_projects'] ) ? true : false;

    update_option( 'helpdesk_dashboard_filters', $dashboard_filters );

    // Save standby settings
    $standby_settings['auto_delete_enabled'] = isset( $_POST['standby_auto_delete_enabled'] ) ? true : false;
    $standby_settings['auto_delete_days'] = isset( $_POST['standby_auto_delete_days'] ) ? absint( $_POST['standby_auto_delete_days'] ) : 365;

    update_option( 'helpdesk_standby_settings', $standby_settings );

    // Save AP HD Settings
    $ap_hd_settings['pracovnik_id'] = isset( $_POST['ap_hd_pracovnik_id'] ) ? absint( $_POST['ap_hd_pracovnik_id'] ) : 0;
    update_option( 'helpdesk_ap_hd_settings', $ap_hd_settings );

    // Handle cron scheduling for standby auto-delete
    if ( $standby_settings['auto_delete_enabled'] ) {
        if ( ! wp_next_scheduled( 'helpdesk_delete_old_standby_cron' ) ) {
            wp_schedule_event( time(), 'daily', 'helpdesk_delete_old_standby_cron' );
        }
    } else {
        if ( $timestamp = wp_next_scheduled( 'helpdesk_delete_old_standby_cron' ) ) {
            wp_unschedule_event( $timestamp, 'helpdesk_delete_old_standby_cron' );
        }
    }

    echo '<div class="notice notice-success"><p>' . esc_html__( 'Nastavenia boli uložené.', HELPDESK_TEXT_DOMAIN ) . '</p></div>';
}
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'HelpDesk Nastavenia', HELPDESK_TEXT_DOMAIN ); ?></h1>

    <!-- Tab Navigation -->
    <div class="helpdesk-tabs-nav">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=helpdesk-settings&tab=ap' ) ); ?>" class="nav-tab <?php echo $active_tab === 'ap' ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html__( 'AP', HELPDESK_TEXT_DOMAIN ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=helpdesk-settings&tab=hd' ) ); ?>" class="nav-tab <?php echo $active_tab === 'hd' ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html__( 'HD', HELPDESK_TEXT_DOMAIN ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=helpdesk-settings&tab=info' ) ); ?>" class="nav-tab <?php echo $active_tab === 'info' ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html__( 'Info', HELPDESK_TEXT_DOMAIN ); ?>
        </a>
    </div>

    <div class="helpdesk-settings">
        <form method="post" action="">
            <?php wp_nonce_field( 'helpdesk-settings' ); ?>

            <!-- AP Tab -->
            <div class="helpdesk-tab-content <?php echo $active_tab === 'ap' ? 'active' : ''; ?>" id="tab-ap">
                <h2><?php echo esc_html__( 'Aplikačná Podpora HD (APHD) Nastavenia', HELPDESK_TEXT_DOMAIN ); ?></h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="ap_hd_pracovnik_id">
                                    <?php echo esc_html__( 'Pracovník APHD HD', HELPDESK_TEXT_DOMAIN ); ?>
                                </label>
                            </th>
                            <td>
                                <select id="ap_hd_pracovnik_id" name="ap_hd_pracovnik_id" class="widefat">
                                    <option value="">-- Vyberte pracovníka --</option>
                                    <?php
                                    // Get employees with SWD Aplikačná podpora HD position (skratka = APHD)
                                    $employees = \HelpDesk\Models\Employee::get_by_position( 'APHD' );
                                    if ( $employees && count( $employees ) > 0 ) {
                                        foreach ( $employees as $emp ) {
                                            $selected = selected( $ap_hd_settings['pracovnik_id'] ?? 0, $emp['id'], false );
                                            echo '<option value="' . esc_attr( $emp['id'] ) . '"' . $selected . '>' . esc_html( $emp['meno_priezvisko'] ) . '</option>';
                                        }
                                    } else {
                                        // If no APHD employees, show AP employees as fallback
                                        $employees = \HelpDesk\Models\Employee::get_by_position( 'AP' );
                                        if ( $employees && count( $employees ) > 0 ) {
                                            echo '<option value="">-- Žiadni pracovníci s pozíciou APHD (zobrazujem AP) --</option>';
                                            foreach ( $employees as $emp ) {
                                                $selected = selected( $ap_hd_settings['pracovnik_id'] ?? 0, $emp['id'], false );
                                                echo '<option value="' . esc_attr( $emp['id'] ) . '"' . $selected . '>' . esc_html( $emp['meno_priezvisko'] ) . ' (AP)</option>';
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                                <p style="margin-top: 8px; color: #666; font-size: 13px;">
                                    <?php echo esc_html__( 'Pracovník s pozíciou SWD Aplikačná podpora HD, ktorý je automaticky vybraný na podpis v riešeniach', HELPDESK_TEXT_DOMAIN ); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- HD Tab -->
            <div class="helpdesk-tab-content <?php echo $active_tab === 'hd' ? 'active' : ''; ?>" id="tab-hd">
                <h2><?php echo esc_html__( 'HD Nastavenia', HELPDESK_TEXT_DOMAIN ); ?></h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Dashboard - Zobrazovanie', HELPDESK_TEXT_DOMAIN ); ?></th>
                            <td>
                                <p style="margin-top: 0; color: #666; font-size: 13px;"><?php echo esc_html__( 'Vyberte, ktoré informácie sa majú zobrazovať na dashboarde:', HELPDESK_TEXT_DOMAIN ); ?></p>
                                <label>
                                    <input type="checkbox" name="display_nazov_projektu" value="1" <?php checked( $dashboard_display['nazov_projektu'] ?? true ); ?>>
                                    <?php echo esc_html__( 'Názov projektu', HELPDESK_TEXT_DOMAIN ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="display_klapka" value="1" <?php checked( $dashboard_display['klapka'] ?? true ); ?>>
                                    <?php echo esc_html__( 'Klapka pracovníka', HELPDESK_TEXT_DOMAIN ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="display_mobil" value="1" <?php checked( $dashboard_display['mobil'] ?? true ); ?>>
                                    <?php echo esc_html__( 'Mobil pracovníka', HELPDESK_TEXT_DOMAIN ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="display_pozicia" value="1" <?php checked( $dashboard_display['pozicia'] ?? true ); ?>>
                                    <?php echo esc_html__( 'Pozícia pracovníka', HELPDESK_TEXT_DOMAIN ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="display_poznamka_pracovnika" value="1" <?php checked( $dashboard_display['poznamka_pracovnika'] ?? true ); ?>>
                                    <?php echo esc_html__( 'Poznámka pracovníka', HELPDESK_TEXT_DOMAIN ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="display_hd_kontakt" value="1" <?php checked( $dashboard_display['hd_kontakt'] ?? true ); ?>>
                                    <?php echo esc_html__( 'HD Kontakt projektu', HELPDESK_TEXT_DOMAIN ); ?>
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Dashboard - Filtrovanie Projektov', HELPDESK_TEXT_DOMAIN ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="show_nw_projects" value="1" <?php checked( $dashboard_filters['show_nw_projects'] ?? false ); ?>>
                                    <?php echo esc_html__( 'Zobrazovať aj projekty s "-nw" v zákaznickom čísle', HELPDESK_TEXT_DOMAIN ); ?>
                                </label>
                                <p style="margin: 6px 0 0 26px; font-size: 12px; color: #666;">
                                    <?php echo esc_html__( 'Ak je zaškrtnuté, na dashboarde sa budú zobrazovať aj projekty, ktoré majú "-nw" v zákaznickom čísle. Ak nie je zaškrtnuté, tieto projekty budú filtrované.', HELPDESK_TEXT_DOMAIN ); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Pohotovosť - Automatické Mazanie', HELPDESK_TEXT_DOMAIN ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="standby_auto_delete_enabled" value="1" <?php checked( $standby_settings['auto_delete_enabled'] ?? false ); ?>>
                                    <?php echo esc_html__( 'Automaticky mazať staré pohotovosti', HELPDESK_TEXT_DOMAIN ); ?>
                                </label>
                                <p style="margin: 6px 0 0 26px; font-size: 12px; color: #666;">
                                    <?php echo esc_html__( 'Ak je zaškrtnuté, pohotovosti starší ako nastavený počet dní budú automaticky vymazané.', HELPDESK_TEXT_DOMAIN ); ?>
                                </p>
                                
                                <div style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-radius: 3px; border-left: 3px solid #0073aa;">
                                    <label for="standby_auto_delete_days" style="display: block; margin-bottom: 5px;">
                                        <?php echo esc_html__( 'Počet dní na uchovávanie pohotovostí:', HELPDESK_TEXT_DOMAIN ); ?>
                                    </label>
                                    <input type="number" id="standby_auto_delete_days" name="standby_auto_delete_days" value="<?php echo esc_attr( $standby_settings['auto_delete_days'] ?? 365 ); ?>" min="1" max="3650" style="width: 100px; padding: 5px; border: 1px solid #ddd; border-radius: 3px;">
                                    <span style="color: #666; font-size: 12px; margin-left: 8px;">
                                        <?php echo esc_html__( 'dní (predvolené: 365)', HELPDESK_TEXT_DOMAIN ); ?>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Info Tab -->
            <div class="helpdesk-tab-content <?php echo $active_tab === 'info' ? 'active' : ''; ?>" id="tab-info">
                <h2><?php echo esc_html__( 'Informácie a Nastavenia', HELPDESK_TEXT_DOMAIN ); ?></h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Zapínanie/Vypínanie Modulov', HELPDESK_TEXT_DOMAIN ); ?></th>
                            <td>
                                <div style="background: #f9f9f9; padding: 15px; margin-bottom: 15px; border-radius: 3px; border-left: 3px solid #0073aa;">
                                    <p style="margin-top: 0; margin-bottom: 10px; font-weight: 600; color: #0073aa;"><?php echo esc_html__( 'Hlavné Moduly', HELPDESK_TEXT_DOMAIN ); ?></p>
                                    <label style="display: flex; align-items: center; margin-bottom: 8px;">
                                        <input type="checkbox" id="enable_hd_module" name="enable_hd_module" value="1" <?php checked( $hd_enabled ); ?> />
                                        <span style="margin-left: 10px;"><?php echo esc_html__( 'HD Modul (Pracovníci, Projekty, Pozície, Pohotovosť...)', HELPDESK_TEXT_DOMAIN ); ?></span>
                                    </label>
                                    <label style="display: flex; align-items: center; margin-bottom: 0;">
                                        <input type="checkbox" id="enable_ap_module" name="enable_ap_module" value="1" <?php checked( $ap_enabled ); ?> />
                                        <span style="margin-left: 10px;"><?php echo esc_html__( 'AP Modul (Riešenia, Problémy, Produkty, Podpisy...)', HELPDESK_TEXT_DOMAIN ); ?></span>
                                    </label>
                                </div>

                                <p style="margin-top: 15px; margin-bottom: 10px; font-weight: 600; color: #333;"><?php echo esc_html__( 'Pod-Moduly', HELPDESK_TEXT_DOMAIN ); ?></p>
                                <label>
                                    <input type="checkbox" name="module_employees" value="1" <?php checked( $module_status['employees'] ?? false ); ?>>
                                    <?php echo esc_html__( 'Pracovníci', HELPDESK_TEXT_DOMAIN ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="module_projects" value="1" <?php checked( $module_status['projects'] ?? false ); ?>>
                                    <?php echo esc_html__( 'Projekty', HELPDESK_TEXT_DOMAIN ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="module_bugs" value="1" <?php checked( $module_status['bugs'] ?? false ); ?>>
                                    <?php echo esc_html__( 'Riešenia', HELPDESK_TEXT_DOMAIN ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="module_standby" value="1" <?php checked( $module_status['standby'] ?? false ); ?>>
                                    <?php echo esc_html__( 'Pohotovosť', HELPDESK_TEXT_DOMAIN ); ?>
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php echo esc_html__( 'O HelpDesk Plugine', HELPDESK_TEXT_DOMAIN ); ?></th>
                            <td>
                                <div class="info-box">
                                    <h3><?php echo esc_html__( 'Informácie', HELPDESK_TEXT_DOMAIN ); ?></h3>
                                    <p><strong><?php echo esc_html__( 'Verzia:', HELPDESK_TEXT_DOMAIN ); ?></strong> <?php echo esc_html( HELPDESK_VERSION ); ?></p>
                                    <p><?php echo esc_html__( 'Plugin umožňuje správu pracovníkov, projektov a problémov v helpdesk prostredí.', HELPDESK_TEXT_DOMAIN ); ?></p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php submit_button(); ?>
        </form>
    </div>
</div>

<style>
.helpdesk-tabs-nav {
    display: flex;
    gap: 5px;
    margin: 20px 0 0 0;
    border-bottom: 1px solid #ccc;
    background-color: #fff;
}

.helpdesk-tabs-nav .nav-tab {
    padding: 8px 20px;
    text-decoration: none;
    color: #666;
    border: 1px solid #ccc;
    border-bottom: none;
    border-radius: 3px 3px 0 0;
    background-color: #f5f5f5;
    margin-right: 2px;
    transition: all 0.2s ease;
}

.helpdesk-tabs-nav .nav-tab:hover {
    background-color: #e8e8e8;
    color: #333;
}

.helpdesk-tabs-nav .nav-tab-active {
    background-color: #fff;
    color: #0073aa;
    border-bottom-color: #fff;
    font-weight: 600;
}

.helpdesk-settings {
    background-color: #fff;
    padding: 20px;
    border-radius: 0 5px 5px 5px;
    margin: 0;
    border: 1px solid #ccc;
    border-top: none;
}

.helpdesk-tab-content {
    display: none;
}

.helpdesk-tab-content.active {
    display: block;
    animation: fadeIn 0.2s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.helpdesk-tab-content h2 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
}

.info-box {
    background-color: #f0f0f0;
    padding: 15px;
    border-radius: 5px;
    border-left: 4px solid #0073aa;
}

.info-box h3 {
    margin-top: 0;
    color: #0073aa;
}

.info-box p {
    margin: 8px 0;
    color: #333;
}

.form-table label {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.form-table label input[type="checkbox"] {
    margin-right: 10px;
    cursor: pointer;
}
</style>
