<?php
/**
 * Standby (Pohotovosť) Admin View
 */

use HelpDesk\Utils\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get employees from database directly
global $wpdb;
$employees_table = Database::get_employees_table();
$employees = $wpdb->get_results(
    "SELECT id, meno_priezvisko, klapka FROM {$employees_table} ORDER BY meno_priezvisko ASC",
    ARRAY_A
);

// Get projects from database directly
$projects_table = Database::get_projects_table();
$projects = $wpdb->get_results(
    "SELECT id, zakaznicke_cislo, nazov FROM {$projects_table} ORDER BY zakaznicke_cislo ASC",
    ARRAY_A
);

// Get standby periods
$standby_table = Database::get_standby_table();
$standby_periods = $wpdb->get_results(
    "SELECT DISTINCT s.id, s.pracovnik_id, s.projekt_id, s.pohotovost_od, s.pohotovost_do, s.zdroj,
            e.meno_priezvisko, e.klapka, e.pozicia_id, 
            p.zakaznicke_cislo, p.nazov,
            CONCAT(p.zakaznicke_cislo, ' - ', p.nazov) as project_name
     FROM {$standby_table} s
     LEFT JOIN {$employees_table} e ON s.pracovnik_id = e.id
     LEFT JOIN {$projects_table} p ON s.projekt_id = p.id
     ORDER BY s.pohotovost_od DESC",
    ARRAY_A
);

// Get vacations for conflict checking
$vacations_table = Database::get_vacations_table();
$vacations = $wpdb->get_results(
    "SELECT pracovnik_id, nepritomnost_od, nepritomnost_do FROM {$vacations_table}",
    ARRAY_A
);

// Get min/max dates for standby periods
$date_range = $wpdb->get_row(
    "SELECT MIN(pohotovost_od) as min_date, MAX(pohotovost_do) as max_date FROM {$standby_table}",
    ARRAY_A
);
$min_date = $date_range['min_date'] ? date_i18n( 'd.m.Y', strtotime( $date_range['min_date'] ) ) : '';
$max_date = $date_range['max_date'] ? date_i18n( 'd.m.Y', strtotime( $date_range['max_date'] ) ) : '';
$date_range_text = ( $min_date && $max_date ) ? "{$min_date} - {$max_date}, " : '';
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Pohotovosť', HELPDESK_TEXT_DOMAIN ); ?> 
        <span style="font-size: 18px; color: #666; font-weight: normal;">
            (<?php echo esc_html( $date_range_text ); ?><?php echo esc_html( count( $standby_periods ) ); ?> <?php echo count( $standby_periods ) === 1 ? 'záznam' : 'záznamov'; ?>)
        </span>
    </h1>

    <div class="helpdesk-admin-container">
        <!-- Tabs for Manual and Auto Generation -->
        <div class="helpdesk-tabs" style="margin-bottom: 20px; border-bottom: 2px solid #ddd;">
            <span style="padding: 10px 20px; font-weight: 500; margin-right: 10px;">Pohotovosť</span>
        </div>

        <!-- Manual Tab -->
        <div class="helpdesk-tab-content active">
            <div class="helpdesk-button-group" style="margin-bottom: 20px;">
                <button class="button button-primary helpdesk-btn-new-standby">
                    <?php echo esc_html__( '+ Pridať pohotovosť', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
                <button class="button helpdesk-btn-import-standby">
                    <?php echo esc_html__( '📥 Krok 1: Importovať pohotovosti', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
                <button class="button helpdesk-btn-update-positions" style="background-color: #d4af37; color: #333; border-color: #d4af37;">
                    <?php echo esc_html__( '⚙️ Krok 2: Aktualizovať pozície', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
                <button class="button helpdesk-btn-check-duplicates" style="color: #dc3545;">
                    <?php echo esc_html__( 'Skontrolovať duplikáty', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
                <button class="button helpdesk-btn-delete-old-standby" style="background-color: #fff3cd; color: #856404; border-color: #ffc107;">
                    <?php echo esc_html__( '🗑️ Vymazať staré pohotovosti', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
                <button class="button helpdesk-btn-delete-all-standby" style="color: #dc3545; background-color: #f8d7da; border-color: #dc3545;">
                    <?php echo esc_html__( '🗑️ Vymazať všetky pohotovosti', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
            </div>

            <!-- Hidden file input for CSV import -->
            <input type="file" id="helpdesk-standby-csv-input" accept=".csv" style="display: none;">
            <!-- Hidden file input for CSV update positions -->
            <input type="file" id="helpdesk-standby-csv-input-update" accept=".csv" style="display: none;">
            
            <!-- Import Loading Overlay -->
            <div id="helpdesk-import-loading" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); display: none; align-items: center; justify-content: center; z-index: 10001;">
                <div style="background: white; padding: 40px; border-radius: 8px; text-align: center; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);">
                    <div style="margin-bottom: 20px;">
                        <div style="display: inline-block; width: 50px; height: 50px; border: 4px solid #f3f3f3; border-top: 4px solid #0073aa; border-radius: 50%; animation: helpdesk-spin 1s linear infinite;"></div>
                    </div>
                    <h3 style="margin: 0 0 10px 0; font-size: 18px;">Spracovávam import...</h3>
                    <p style="margin: 0; color: #666; font-size: 14px;">Prosím čakajte, môže to trvať niekoľko sekúnd.</p>
                </div>
            </div>

            <!-- Filters -->
            <div style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr; gap: 15px;">
                    <div>
                        <label for="filter-employee" style="display: block; font-weight: 500; margin-bottom: 5px;">
                            <?php echo esc_html__( 'Pracovník', HELPDESK_TEXT_DOMAIN ); ?>
                        </label>
                        <select id="filter-employee" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                            <option value="">-- <?php echo esc_html__( 'Všetci', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                            <?php foreach ( $employees as $emp ) : ?>
                                <option value="<?php echo absint( $emp['id'] ); ?>">
                                    <?php echo esc_html( $emp['meno_priezvisko'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="filter-project" style="display: block; font-weight: 500; margin-bottom: 5px;">
                            <?php echo esc_html__( 'Projekt', HELPDESK_TEXT_DOMAIN ); ?>
                        </label>
                        <select id="filter-project" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                            <option value="">-- <?php echo esc_html__( 'Všetky', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                            <?php foreach ( $projects as $proj ) : ?>
                                <option value="<?php echo absint( $proj['id'] ); ?>">
                                    <?php echo esc_html( $proj['zakaznicke_cislo'] ); ?> - <?php echo esc_html( $proj['nazov'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="filter-position" style="display: block; font-weight: 500; margin-bottom: 5px;">
                            <?php echo esc_html__( 'Pozícia', HELPDESK_TEXT_DOMAIN ); ?>
                        </label>
                        <select id="filter-position" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                            <option value="">-- <?php echo esc_html__( 'Všetky', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                            <option value="has">✓ <?php echo esc_html__( 'Má pozíciu', HELPDESK_TEXT_DOMAIN ); ?></option>
                            <option value="no">✗ <?php echo esc_html__( 'Nemá pozíciu', HELPDESK_TEXT_DOMAIN ); ?></option>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 5px;">
                            <?php echo esc_html__( 'Časový filter', HELPDESK_TEXT_DOMAIN ); ?>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; padding: 8px; border: 1px solid #ddd; border-radius: 3px; background: white; cursor: pointer;">
                            <input type="checkbox" id="filter-today" style="cursor: pointer;">
                            <span><?php echo esc_html__( 'Len dnes', HELPDESK_TEXT_DOMAIN ); ?></span>
                        </label>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 5px; visibility: hidden;">
                            <?php echo esc_html__( 'Akcia', HELPDESK_TEXT_DOMAIN ); ?>
                        </label>
                        <button id="btn-reset-filters" class="button" style="width: 100%;">
                            <?php echo esc_html__( 'Vynulovať filtre', HELPDESK_TEXT_DOMAIN ); ?>
                        </button>
                    </div>
                </div>
            </div>

        <div class="helpdesk-table-wrapper">
        <table class="wp-list-table widefat fixed striped" id="helpdesk-standby-table">
            <thead>
                <tr>
                    <th scope="col" class="column-pracovnik sortable" data-sort-field="meno_priezvisko" style="cursor: pointer;">
                        <span><?php echo esc_html__( 'Pracovník', HELPDESK_TEXT_DOMAIN ); ?></span>
                        <span class="sort-indicator" style="margin-left: 5px; display: none;">↑</span>
                    </th>
                    <th scope="col" class="column-projekt sortable" data-sort-field="zakaznicke_cislo" style="cursor: pointer;">
                        <span><?php echo esc_html__( 'Projekt', HELPDESK_TEXT_DOMAIN ); ?></span>
                        <span class="sort-indicator" style="margin-left: 5px; display: none;">↑</span>
                    </th>
                    <th scope="col" class="column-od sortable" data-sort-field="pohotovost_od" style="cursor: pointer;">
                        <span><?php echo esc_html__( 'Od', HELPDESK_TEXT_DOMAIN ); ?></span>
                        <span class="sort-indicator" style="margin-left: 5px; display: none;">↑</span>
                    </th>
                    <th scope="col" class="column-do sortable" data-sort-field="pohotovost_do" style="cursor: pointer;">
                        <span><?php echo esc_html__( 'Do', HELPDESK_TEXT_DOMAIN ); ?></span>
                        <span class="sort-indicator" style="margin-left: 5px; display: none;">↑</span>
                    </th>
                    <th scope="col" class="column-zdroj sortable" data-sort-field="zdroj" style="cursor: pointer;">
                        <span><?php echo esc_html__( 'Zdroj', HELPDESK_TEXT_DOMAIN ); ?></span>
                        <span class="sort-indicator" style="margin-left: 5px; display: none;">↑</span>
                    </th>
                    <th scope="col" class="column-actions"><?php echo esc_html__( 'Akcie', HELPDESK_TEXT_DOMAIN ); ?></th>
                </tr>
            </thead>
            <tbody id="helpdesk-standby-list">
                <?php if ( ! empty( $standby_periods ) ) : ?>
                    <?php foreach ( $standby_periods as $period ) : ?>
                        <?php
                        // Check if this standby period conflicts with any vacation
                        $has_vacation_conflict = false;
                        if ( ! empty( $vacations ) ) {
                            foreach ( $vacations as $vacation ) {
                                if ( $vacation['pracovnik_id'] == $period['pracovnik_id'] ) {
                                    // Check if dates overlap
                                    if ( strtotime( $period['pohotovost_od'] ) <= strtotime( $vacation['nepritomnost_do'] ) &&
                                         strtotime( $period['pohotovost_do'] ) >= strtotime( $vacation['nepritomnost_od'] ) ) {
                                        $has_vacation_conflict = true;
                                        break;
                                    }
                                }
                            }
                        }
                        $row_class = $has_vacation_conflict ? 'helpdesk-standby-with-vacation' : '';
                        $has_position = ! empty( $period['pozicia_id'] ) ? 'yes' : 'no';
                        
                        // Get source beacon emoji
                        $zdroj_emoji = '🔵'; // Default blue
                        $zdroj_title = '';
                        switch ( $period['zdroj'] ) {
                            case 'IS':
                                $zdroj_emoji = '🔴';
                                $zdroj_title = __( 'Importované zo súboru', HELPDESK_TEXT_DOMAIN );
                                break;
                            case 'MP':
                                $zdroj_emoji = '🔵';
                                $zdroj_title = __( 'Manuálne pridané', HELPDESK_TEXT_DOMAIN );
                                break;
                            case 'AG':
                                $zdroj_emoji = '🔵';
                                $zdroj_title = __( 'Automaticky generované', HELPDESK_TEXT_DOMAIN );
                                break;
                        }
                        ?>
                        <tr class="helpdesk-standby-row <?php echo esc_attr( $row_class ); ?>" data-standby-id="<?php echo esc_attr( $period['id'] ); ?>" data-employee-id="<?php echo esc_attr( $period['pracovnik_id'] ); ?>" data-project-id="<?php echo esc_attr( $period['projekt_id'] ); ?>" data-date-from="<?php echo esc_attr( $period['pohotovost_od'] ); ?>" data-date-to="<?php echo esc_attr( $period['pohotovost_do'] ); ?>" data-position="<?php echo esc_attr( $has_position ); ?>">
                            <td>
                                <strong><?php echo esc_html( $period['meno_priezvisko'] ); ?> (<?php echo esc_html( $period['klapka'] ); ?>)</strong>
                            </td>
                            <td><?php echo esc_html( $period['project_name'] ); ?></td>
                            <td><?php echo esc_html( date_i18n( 'd.m.Y', strtotime( $period['pohotovost_od'] ) ) ); ?></td>
                            <td><?php echo esc_html( date_i18n( 'd.m.Y', strtotime( $period['pohotovost_do'] ) ) ); ?></td>
                            <td title="<?php echo esc_attr( $zdroj_title ); ?>">
                                <?php echo $zdroj_emoji; ?>
                            </td>
                            <td>
                                <button class="button button-small helpdesk-btn-edit-standby" data-standby-id="<?php echo esc_attr( $period['id'] ); ?>">
                                    <?php echo esc_html__( 'Upraviť', HELPDESK_TEXT_DOMAIN ); ?>
                                </button>
                                <button class="button button-small button-link-delete helpdesk-btn-delete-standby" data-standby-id="<?php echo esc_attr( $period['id'] ); ?>">
                                    <?php echo esc_html__( 'Vymazať', HELPDESK_TEXT_DOMAIN ); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" class="center"><?php echo esc_html__( 'Žádné periody pohotovosti.', HELPDESK_TEXT_DOMAIN ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <!-- Auto Generation Tab -->
        <div id="auto-tab" class="helpdesk-tab-content">
            <div style="background: #f9f9f9; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                <h3><?php echo esc_html__( 'Automatické generovanie pohotovosti', HELPDESK_TEXT_DOMAIN ); ?></h3>
                <p><?php echo esc_html__( 'Vygeneruje pohotovosti podľa rotačného plánu (napr. týždeň áno, týždeň nie, týždeň áno...)', HELPDESK_TEXT_DOMAIN ); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Standby Modal -->
<div id="helpdesk-standby-modal" class="helpdesk-modal">
    <div class="helpdesk-modal-content" style="max-width: 700px;">
        <div class="helpdesk-modal-header">
            <h2 id="standby-modal-title" style="margin: 0;"><?php echo esc_html__( 'Pridať pohotovosť', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close" type="button">&times;</button>
        </div>

        <form id="helpdesk-standby-modal-form" class="helpdesk-form">
            <input type="hidden" id="standby-id" value="">
            <input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>">
            <input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( $_SERVER['REQUEST_URI'] ); ?>">

            <!-- Typ generovania -->
            <div style="background: #f0f0f0; padding: 15px; margin-bottom: 15px; border-radius: 3px;">
                <label style="display: flex; align-items: center; margin-bottom: 10px; cursor: pointer;">
                    <input type="radio" name="standby-type" id="standby-type-manual" value="manual" checked style="margin-right: 10px;">
                    <span><?php echo esc_html__( 'Manuálne - Pridať jednu pohotovosť', HELPDESK_TEXT_DOMAIN ); ?></span>
                </label>
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="radio" name="standby-type" id="standby-type-auto" value="auto" style="margin-right: 10px;">
                    <span><?php echo esc_html__( 'Automatické - Generovať podľa plánu', HELPDESK_TEXT_DOMAIN ); ?></span>
                </label>
            </div>

            <!-- MANUÁLNE SEKCIA -->
            <div id="standby-manual-section">
                <div class="form-group">
                    <label for="standby-employee-id"><?php echo esc_html__( 'Pracovník', HELPDESK_TEXT_DOMAIN ); ?> <span style="color: red;">*</span></label>
                    <select id="standby-employee-id" name="employee_id" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                        <option value="">-- <?php echo esc_html__( 'Vyberte pracovníka', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                        <?php foreach ( $employees as $emp ) : ?>
                            <option value="<?php echo absint( $emp['id'] ); ?>">
                                <?php echo esc_html( $emp['meno_priezvisko'] ); ?> (<?php echo esc_html( $emp['klapka'] ); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="standby-project-id"><?php echo esc_html__( 'Projekt', HELPDESK_TEXT_DOMAIN ); ?> <span style="color: red;">*</span></label>
                    <select id="standby-project-id" name="project_id" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                        <option value="">-- <?php echo esc_html__( 'Vyberte projekt', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                        <?php foreach ( $projects as $proj ) : ?>
                            <option value="<?php echo absint( $proj['id'] ); ?>">
                                <?php echo esc_html( $proj['zakaznicke_cislo'] ); ?> - <?php echo esc_html( $proj['nazov'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="standby-od"><?php echo esc_html__( 'Od', HELPDESK_TEXT_DOMAIN ); ?> <span style="color: red;">*</span></label>
                        <input type="date" id="standby-od" name="date_from" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box;">
                    </div>
                    <div class="form-group">
                        <label for="standby-do"><?php echo esc_html__( 'Do', HELPDESK_TEXT_DOMAIN ); ?> <span style="color: red;">*</span></label>
                        <input type="date" id="standby-do" name="date_to" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box;">
                    </div>
                </div>
            </div>

            <!-- AUTOMATICKÉ SEKCIA -->
            <div id="standby-auto-section" style="display: none;">
                <!-- KROK 1: Výber projektu -->
                <div style="background: #e8f4f8; padding: 12px; border-radius: 3px; margin-bottom: 15px; border-left: 4px solid #0073aa;">
                    <strong style="color: #0073aa;">1. Výber projektu a pracovníkov</strong>
                </div>

                <div class="form-group">
                    <label style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Projekty', HELPDESK_TEXT_DOMAIN ); ?> <span style="color: red;">*</span>
                    </label>
                    <div id="modal-auto-projects-checkboxes" style="border: 1px solid #ddd; border-radius: 3px; padding: 10px; background: #f9f9f9; max-height: 250px; overflow-y: auto;">
                        <?php foreach ( $projects as $proj ) : ?>
                            <label style="display: flex; align-items: center; padding: 8px; margin: 0; cursor: pointer; border-radius: 3px; transition: background 0.2s;" class="project-checkbox-label">
                                <input type="checkbox" class="modal-auto-project-checkbox" value="<?php echo absint( $proj['id'] ); ?>" style="width: 18px; height: 18px; margin-right: 10px; cursor: pointer;">
                                <span style="flex: 1;">
                                    <strong><?php echo esc_html( $proj['zakaznicke_cislo'] ); ?></strong> - <?php echo esc_html( $proj['nazov'] ); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <small style="display: block; color: #666; margin-top: 5px;">Vyber jeden alebo viacero projektov</small>
                </div>

                <!-- Pracovníci na projekte -->
                <div class="form-group">
                    <label style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Pracovníci', HELPDESK_TEXT_DOMAIN ); ?> <span style="color: red;">*</span>
                    </label>
                    <small style="display: block; color: #666; margin-bottom: 8px;">Klikni na pracovníka na zmenu poradia (drag-drop) a vyber pomocou checkboxu</small>
                    <div id="auto-employees-list" style="border: 2px solid #0073aa; border-radius: 3px; padding: 10px; background: #f0f8ff; min-height: 150px; max-height: 250px; overflow-y: auto; display: block;">
                        <!-- Bude naplnené AJAX -->
                    </div>
                    <p id="auto-employees-loading" style="color: #666; display: none;">Načítam pracovníkov...</p>
                    <p id="auto-employees-empty" style="color: #666; display: none;">Vyberte projekt aby sa zobrazili pracovníci</p>
                </div>

                <!-- KROK 2: Dátumy -->
                <div style="background: #e8f4f8; padding: 12px; border-radius: 3px; margin: 20px 0 15px 0; border-left: 4px solid #0073aa;">
                    <strong style="color: #0073aa;">2. Obdobie pohotovosti</strong>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label for="auto-start-date" style="display: block; font-weight: 500; margin-bottom: 5px;">
                            <?php echo esc_html__( 'Začiatok', HELPDESK_TEXT_DOMAIN ); ?> <span style="color: red;">*</span>
                        </label>
                        <input type="date" id="auto-start-date" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 14px;">
                        <small style="display: block; color: #666; margin-top: 4px;">Prvý deň pohotovosti</small>
                    </div>

                    <div class="form-group">
                        <label for="auto-end-date" style="display: block; font-weight: 500; margin-bottom: 5px;">
                            <?php echo esc_html__( 'Koniec', HELPDESK_TEXT_DOMAIN ); ?> <span style="color: red;">*</span>
                        </label>
                        <input type="date" id="auto-end-date" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 14px;">
                        <small style="display: block; color: #666; margin-top: 4px;">Posledný deň pohotovosti</small>
                    </div>
                </div>

                <!-- KROK 3: Interval striedania -->
                <div style="background: #e8f4f8; padding: 12px; border-radius: 3px; margin: 20px 0 15px 0; border-left: 4px solid #0073aa;">
                    <strong style="color: #0073aa;">3. Systém striedania</strong>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label for="auto-interval-type" style="display: block; font-weight: 500; margin-bottom: 5px;">
                            <?php echo esc_html__( 'Jednotka', HELPDESK_TEXT_DOMAIN ); ?> <span style="color: red;">*</span>
                        </label>
                        <select id="auto-interval-type" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 14px;">
                            <option value="days"><?php echo esc_html__( 'Dni', HELPDESK_TEXT_DOMAIN ); ?></option>
                            <option value="weeks" selected><?php echo esc_html__( 'Týždne', HELPDESK_TEXT_DOMAIN ); ?></option>
                            <option value="months"><?php echo esc_html__( 'Mesiace', HELPDESK_TEXT_DOMAIN ); ?></option>
                        </select>
                        <small style="display: block; color: #666; margin-top: 4px;">V akých jednotkách počítať</small>
                    </div>

                    <div class="form-group">
                        <label for="auto-work-interval" style="display: block; font-weight: 500; margin-bottom: 5px;">
                            <?php echo esc_html__( 'Pohotovosť', HELPDESK_TEXT_DOMAIN ); ?> <span style="color: red;">*</span>
                        </label>
                        <input type="number" id="auto-work-interval" value="1" min="1" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 14px;">
                        <small style="display: block; color: #666; margin-top: 4px;">Počet jednotiek na pohotovosti</small>
                    </div>

                    <div class="form-group">
                        <label for="auto-free-interval" style="display: block; font-weight: 500; margin-bottom: 5px;">
                            <?php echo esc_html__( 'Voľno', HELPDESK_TEXT_DOMAIN ); ?> <span style="color: red;">*</span>
                        </label>
                        <input type="number" id="auto-free-interval" value="1" min="0" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 14px;">
                        <small style="display: block; color: #666; margin-top: 4px;">Počet jednotiek voľna medzi</small>
                    </div>
                </div>

                <div style="background: #fffbea; padding: 12px; border-radius: 3px; border-left: 4px solid #ff9800; margin-bottom: 20px;">
                    <strong style="color: #ff9800;">Príklad:</strong> Ak je jednotka "Týždne", Pohotovosť=1, Voľno=2 → pracovník bude 1 týždeň na pohotovosti, potom 2 týždne voľna
                </div>
            </div>

            <div class="form-actions" style="display: flex; gap: 8px; justify-content: flex-start; margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
                <button type="submit" class="button button-primary" style="padding: 6px 16px; font-size: 13px; height: auto;"><?php echo esc_html__( 'Uložiť', HELPDESK_TEXT_DOMAIN ); ?></button>
                <button type="button" class="button helpdesk-modal-close-btn" style="padding: 6px 16px; font-size: 13px; height: auto;"><?php echo esc_html__( 'Zrušiť', HELPDESK_TEXT_DOMAIN ); ?></button>
            </div>

            <div class="error-message" style="display: none; color: #d32f2f; margin-top: 10px;"></div>
        </form>
    </div>
</div>

<style>
.helpdesk-standby-with-vacation {
    opacity: 0.6;
    text-decoration: line-through;
    background-color: #fff3cd !important;
}

.helpdesk-standby-with-vacation td {
    color: #666;
}

#manual-tab,
#auto-tab {
    display: none;
}

#manual-tab.active,
#auto-tab.active {
    display: block;
}

/* Modální okno - centrování a styling */
.helpdesk-modal {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(0, 0, 0, 0.5) !important;
    display: none !important;
    align-items: center !important;
    justify-content: center !important;
    z-index: 10000 !important;
}

.helpdesk-modal.show {
    display: flex !important;
}

.helpdesk-modal-content {
    background: white;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    max-width: 700px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    padding: 0;
}

.helpdesk-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
    background: #f5f5f5;
}

.helpdesk-modal-header h2 {
    margin: 0;
    font-size: 18px;
}

.helpdesk-modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.helpdesk-modal-close:hover {
    color: #000;
}

.helpdesk-form {
    padding: 20px;
    display: flex;
    flex-direction: column;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
    box-sizing: border-box;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 5px rgba(0, 115, 170, 0.3);
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-start;
    margin-top: auto;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.form-actions button {
    padding: 8px 16px;
    min-width: auto;
    height: auto;
    font-size: 13px;
    line-height: 1.4;
    flex-shrink: 0;
}

.form-actions .button-primary {
    background-color: #0073aa;
    border-color: #0073aa;
    color: white;
}

.form-actions .button-primary:hover {
    background-color: #005a87;
    border-color: #005a87;
}

.form-actions .button {
    background-color: #f1f1f1;
    border-color: #999;
    color: #333;
    padding: 6px 12px;
    height: auto;
}

.form-actions .button:hover {
    background-color: #e1e1e1;
}

/* Employee list styling */
.auto-employees-sortable {
    list-style: none;
    padding: 0;
    margin: 0;
}

.auto-employee-item {
    display: flex;
    align-items: center;
    padding: 10px;
    margin-bottom: 5px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 3px;
    cursor: move;
}

.auto-employee-item:hover {
    background-color: #f0f0f0;
    border-color: #0073aa;
}

.auto-employee-item.selected {
    background-color: #e8f4f8;
    border-color: #0073aa;
}

.auto-employee-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    min-width: 18px;
    min-height: 18px;
    flex-shrink: 0;
    margin-right: 10px;
    cursor: pointer;
}

.auto-employee-drag-handle {
    margin-right: 10px;
    color: #999;
    cursor: grab;
    font-size: 12px;
}

.auto-employee-drag-handle:active {
    cursor: grabbing;
}

.auto-employee-name {
    flex: 1;
}

.auto-employee-order {
    background: #f0f0f0;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    color: #666;
    margin-left: 10px;
}

.auto-employee-item.selected .auto-employee-order {
    background: #0073aa;
    color: white;
}

/* Loading spinner animation */
@keyframes helpdesk-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
(function() {
    'use strict';

    // Čakaj na DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStandbyModal);
    } else {
        initStandbyModal();
    }

    function initStandbyModal() {
        // Inicializuj ajaxurl ak neexistuje
        if (typeof ajaxurl === 'undefined') {
            window.ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
        }

        // Prepínanie medzi Manuálne a Automatické v modálnom okne
        document.querySelectorAll('input[name="standby-type"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                const manualSection = document.getElementById('standby-manual-section');
                const autoSection = document.getElementById('standby-auto-section');
                const submitBtn = document.querySelector('#helpdesk-standby-modal-form button[type="submit"]');

                if (this.value === 'manual') {
                    manualSection.style.display = 'block';
                    autoSection.style.display = 'none';
                    submitBtn.textContent = 'Uložiť';
                } else if (this.value === 'auto') {
                    manualSection.style.display = 'none';
                    autoSection.style.display = 'block';
                    submitBtn.textContent = 'Vygenerovať pohotovosti';
                }
            });
        });

        // Načítanie pracovníkov projektu pri zmene - s jQuery
        // Čakaj na jQuery ak nie je dostupná
        if (typeof jQuery !== 'undefined') {
            console.log('jQuery is available, calling attachProjectChangeHandler');
            attachProjectChangeHandler();
        } else {
            console.log('jQuery NOT available, setting timeout');
            // Skúš znovu po chvíli
            setTimeout(function() {
                if (typeof jQuery !== 'undefined') {
                    console.log('jQuery became available after timeout, calling attachProjectChangeHandler');
                    attachProjectChangeHandler();
                }
            }, 100);
        }
    }

    function attachProjectChangeHandler() {
        jQuery(document).on('change', '.modal-auto-project-checkbox', function() {
            // Get all selected project IDs
            const selectedProjects = [];
            jQuery('.modal-auto-project-checkbox:checked').each(function() {
                selectedProjects.push(jQuery(this).val());
            });

            const employeesList = document.getElementById('auto-employees-list');
            const loadingMsg = document.getElementById('auto-employees-loading');
            const emptyMsg = document.getElementById('auto-employees-empty');

            if (selectedProjects.length === 0) {
                employeesList.style.display = 'none';
                loadingMsg.style.display = 'none';
                emptyMsg.style.display = 'block';
                emptyMsg.textContent = 'Vyberte projekt aby sa zobrazili pracovníci';
                return;
            }

            loadingMsg.style.display = 'block';
            emptyMsg.style.display = 'none';
            employeesList.style.display = 'none';

            const formData = new FormData();
            formData.append('action', 'helpdesk_get_project_employees');
            const nonceField = document.querySelector('input[name="_ajax_nonce"]');
            const nonceValue = nonceField ? nonceField.value : '';
            if (nonceValue) {
                formData.append('_ajax_nonce', nonceValue);
            }
            // Pass all selected project IDs as JSON
            formData.append('project_ids', JSON.stringify(selectedProjects));

            fetch(window.ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(result => {
                loadingMsg.style.display = 'none';
                
                if (result.success && result.data.employees && result.data.employees.length > 0) {
                    renderEmployeesList(result.data.employees);
                    employeesList.style.display = 'block';
                } else {
                    emptyMsg.textContent = result.data?.message || 'Žiadni pracovníci na vybraných projektoch';
                    emptyMsg.style.display = 'block';
                }
            })
            .catch(error => {
                loadingMsg.style.display = 'none';
                emptyMsg.textContent = 'Chyba pri načítaní pracovníkov: ' + error.message;
                emptyMsg.style.display = 'block';
            });
        });
    }

    // Vyrenderuj zoznam pracovníkov s checkboxami
    function renderEmployeesList(employees) {
        const container = document.getElementById('auto-employees-list');
        
        if (!container) {
            return;
        }
        
        let html = '<ul class="auto-employees-sortable">';
        
        employees.forEach((emp, index) => {
            const name = escapeHtml(emp.meno_priezvisko);
            const klapka = escapeHtml(emp.klapka);
            html += `
                <li class="auto-employee-item" draggable="true" data-employee-id="${emp.id}">
                    <span class="auto-employee-drag-handle">☰</span>
                    <input type="checkbox" class="employee-checkbox" value="${emp.id}" checked style="cursor: pointer;">
                    <span class="auto-employee-name">${name} (${klapka})</span>
                    <span class="auto-employee-order">#${index + 1}</span>
                </li>
            `;
        });
        
        html += '</ul>';
        container.innerHTML = html;

        // Drag and drop
        const items = container.querySelectorAll('.auto-employee-item');
        let draggedElement = null;

        items.forEach(item => {
            item.addEventListener('dragstart', function() {
                draggedElement = this;
                this.style.opacity = '0.5';
            });

            item.addEventListener('dragend', function() {
                this.style.opacity = '1';
                draggedElement = null;
            });

            item.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderTop = '2px solid #0073aa';
            });

            item.addEventListener('dragleave', function() {
                this.style.borderTop = 'none';
            });

            item.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderTop = 'none';

                if (draggedElement && draggedElement !== this) {
                    const list = this.parentNode;
                    if (draggedElement.compareDocumentPosition(this) & Node.DOCUMENT_POSITION_FOLLOWING) {
                        draggedElement.parentNode.insertBefore(this, draggedElement);
                    } else {
                        draggedElement.parentNode.insertBefore(draggedElement, this);
                    }
                    updateEmployeeOrders();
                }
            });

            // Toggle selected state
            item.querySelector('.employee-checkbox').addEventListener('change', function() {
                if (this.checked) {
                    item.classList.add('selected');
                } else {
                    item.classList.remove('selected');
                }
            });
        });

        updateEmployeeOrders();
    }

    // Aktualizuj čísla poradia
    function updateEmployeeOrders() {
        const items = document.querySelectorAll('.auto-employee-item');
        let order = 1;
        items.forEach(item => {
            const orderSpan = item.querySelector('.auto-employee-order');
            if (orderSpan) {
                orderSpan.textContent = '#' + order;
                order++;
            }
        });
    }

    // Helper na escape HTML
    function escapeHtml(text) {
        if (!text) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Spracovanie formulára
    const form = document.getElementById('helpdesk-standby-modal-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Načítaj aktuálny typ
            const standbyType = document.querySelector('input[name="standby-type"]:checked').value;
            let data;

            if (standbyType === 'manual') {
                // Manuálne - podľa pôvodného formulára
                data = {
                    action: 'helpdesk_save_standby',
                    nonce: '<?php echo esc_attr( wp_create_nonce( 'add_standby' ) ); ?>',
                    employee_id: document.getElementById('standby-employee-id').value,
                    project_id: document.getElementById('standby-project-id').value,
                    date_from: document.getElementById('standby-od').value,
                    date_to: document.getElementById('standby-do').value
                };

                // Validácia
                if (!data.employee_id || !data.project_id || !data.date_from || !data.date_to) {
                    showErrorMessage('Všetky polia sú povinné');
                    return;
                }
            } else if (standbyType === 'auto') {
                // Automatické generovanie - zber vybraných pracovníkov v poradí
                const selectedEmployees = [];
                const employeeItems = document.querySelectorAll('.auto-employee-item');
                
                employeeItems.forEach(item => {
                    const checkbox = item.querySelector('.employee-checkbox');
                    if (checkbox && checkbox.checked) {
                        selectedEmployees.push(checkbox.value);
                    }
                });

                if (selectedEmployees.length === 0) {
                    showErrorMessage('Vyber aspoň jedného pracovníka');
                    return;
                }

                // Čítaj IDs elementov
                const selectedProjects = [];
                document.querySelectorAll('.modal-auto-project-checkbox:checked').forEach(checkbox => {
                    selectedProjects.push(checkbox.value);
                });
                
                const startEl = document.getElementById('auto-start-date');
                const endEl = document.getElementById('auto-end-date');
                const typeEl = document.getElementById('auto-interval-type');
                const workEl = document.getElementById('auto-work-interval');
                const freeEl = document.getElementById('auto-free-interval');

                data = {
                    action: 'generate_standby_rotation',
                    _ajax_nonce: document.querySelector('input[name="_ajax_nonce"]').value,
                    project_ids: JSON.stringify(selectedProjects),
                    start_date: startEl?.value || '',
                    end_date: endEl?.value || '',
                    interval_type: typeEl?.value || 'weeks',
                    work_interval: workEl?.value || '1',
                    free_interval: freeEl?.value || '1',
                    employee_ids: selectedEmployees.join(',')
                };

                // Validácia - hlásenie o chýbajúcich poliach
                let missingFields = [];
                if (selectedProjects.length === 0) missingFields.push('Projekt');
                if (!data.start_date || data.start_date === '') missingFields.push('Začiatok');
                if (!data.end_date || data.end_date === '') missingFields.push('Koniec');
                
                if (missingFields.length > 0) {
                    showErrorMessage('Vyplňte povinné polia: ' + missingFields.join(', '));
                    return;
                }
            }

            // AJAX požiadavka
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    closeModal();
                    location.reload();
                } else {
                    showErrorMessage(result.data.message || 'Chyba pri ukladaní');
                }
            })
            .catch(error => {
                showErrorMessage('Chyba: ' + error.message);
            });
        });
    }

    function showErrorMessage(message) {
        const errorDiv = document.querySelector('#helpdesk-standby-modal-form .error-message');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        }
    }

    function closeModal() {
        const modal = document.getElementById('helpdesk-standby-modal');
        if (modal) {
            modal.classList.remove('show');
        }
    }

    // Zatváranie modálu
    document.querySelectorAll('.helpdesk-modal-close, .helpdesk-modal-close-btn').forEach(function(btn) {
        btn.addEventListener('click', closeModal);
    });

    // Otvorenie modálu pri kliknutí "Pridať pohotovosť"
    document.addEventListener('click', function(e) {
        // Skontroluj či bolo kliknuté na tlačidlo s triedou helpdesk-btn-new-standby
        if (e.target.classList && e.target.classList.contains('helpdesk-btn-new-standby')) {
            e.preventDefault();
            // Reset formulára
            document.getElementById('helpdesk-standby-modal-form').reset();
            // Nastav na Manual mode by default
            document.getElementById('standby-type-manual').checked = true;
            document.getElementById('standby-manual-section').style.display = 'block';
            document.getElementById('standby-auto-section').style.display = 'none';
            // Otvor modál - použij CSS class
            document.getElementById('helpdesk-standby-modal').classList.add('show');
        }
    });
})();
</script>
