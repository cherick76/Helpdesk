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
    "SELECT id, zakaznicke_cislo FROM {$projects_table} ORDER BY zakaznicke_cislo ASC",
    ARRAY_A
);

// Get standby periods
$standby_table = Database::get_standby_table();
$standby_periods = $wpdb->get_results(
    "SELECT DISTINCT s.id, s.pracovnik_id, s.projekt_id, s.pohotovost_od, s.pohotovost_do,
            e.meno_priezvisko, e.klapka, e.pozicia_id, p.zakaznicke_cislo as project_name
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
            <button class="helpdesk-tab-btn active" data-tab="manual" style="padding: 10px 20px; background: none; border: none; cursor: pointer; border-bottom: 3px solid transparent; font-weight: 500; margin-right: 10px;">
                <?php echo esc_html__( 'Manuálne', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
            <button class="helpdesk-tab-btn" data-tab="auto" style="padding: 10px 20px; background: none; border: none; cursor: pointer; border-bottom: 3px solid transparent; font-weight: 500;">
                <?php echo esc_html__( 'Automatické generovanie', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
        </div>

        <!-- Manual Tab -->
        <div id="manual-tab" class="helpdesk-tab-content" style="display: block;">
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
                                    <?php echo esc_html( $proj['zakaznicke_cislo'] ); ?>
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
                        ?>
                        <tr class="helpdesk-standby-row <?php echo esc_attr( $row_class ); ?>" data-standby-id="<?php echo esc_attr( $period['id'] ); ?>" data-employee-id="<?php echo esc_attr( $period['pracovnik_id'] ); ?>" data-project-id="<?php echo esc_attr( $period['projekt_id'] ); ?>" data-date-from="<?php echo esc_attr( $period['pohotovost_od'] ); ?>" data-date-to="<?php echo esc_attr( $period['pohotovost_do'] ); ?>" data-position="<?php echo esc_attr( $has_position ); ?>">
                            <td>
                                <strong><?php echo esc_html( $period['meno_priezvisko'] ); ?> (<?php echo esc_html( $period['klapka'] ); ?>)</strong>
                            </td>
                            <td><?php echo esc_html( $period['project_name'] ); ?></td>
                            <td><?php echo esc_html( date_i18n( 'd.m.Y', strtotime( $period['pohotovost_od'] ) ) ); ?></td>
                            <td><?php echo esc_html( date_i18n( 'd.m.Y', strtotime( $period['pohotovost_do'] ) ) ); ?></td>
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
                        <td colspan="5" class="center"><?php echo esc_html__( 'Žádné periody pohotovosti.', HELPDESK_TEXT_DOMAIN ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <!-- Auto Generation Tab -->
        <div id="auto-tab" class="helpdesk-tab-content" style="display: none;">
            <div style="background: #f9f9f9; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                <h3><?php echo esc_html__( 'Automatické generovanie pohotovosti', HELPDESK_TEXT_DOMAIN ); ?></h3>
                <p><?php echo esc_html__( 'Vygeneruje pohotovosti pre pracovníkov na základe rotácie.', HELPDESK_TEXT_DOMAIN ); ?></p>

                <form id="helpdesk-auto-standby-form" class="helpdesk-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label for="auto-project" style="display: block; font-weight: 500; margin-bottom: 5px;">
                                <?php echo esc_html__( 'Projekt', HELPDESK_TEXT_DOMAIN ); ?> <span style="color: red;">*</span>
                            </label>
                            <select id="auto-project" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                                <option value="">-- <?php echo esc_html__( 'Vyberte projekt', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                                <?php foreach ( $projects as $proj ) : ?>
                                    <option value="<?php echo absint( $proj['id'] ); ?>">
                                        <?php echo esc_html( $proj['zakaznicke_cislo'] ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="auto-start-date" style="display: block; font-weight: 500; margin-bottom: 5px;">
                                <?php echo esc_html__( 'Začiatok', HELPDESK_TEXT_DOMAIN ); ?> <span style="color: red;">*</span>
                            </label>
                            <input type="date" id="auto-start-date" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label for="auto-interval-type" style="display: block; font-weight: 500; margin-bottom: 5px;">
                                <?php echo esc_html__( 'Typ intervalu', HELPDESK_TEXT_DOMAIN ); ?> <span style="color: red;">*</span>
                            </label>
                            <select id="auto-interval-type" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                                <option value="weeks">
                                    <?php echo esc_html__( 'Týždne', HELPDESK_TEXT_DOMAIN ); ?>
                                </option>
                                <option value="months">
                                    <?php echo esc_html__( 'Mesiace', HELPDESK_TEXT_DOMAIN ); ?>
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="auto-interval-count" style="display: block; font-weight: 500; margin-bottom: 5px;">
                                <?php echo esc_html__( 'Počet', HELPDESK_TEXT_DOMAIN ); ?> <span style="color: red;">*</span>
                            </label>
                            <input type="number" id="auto-interval-count" value="1" min="1" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box;">
                        </div>

                        <div class="form-group">
                            <label for="auto-num-periods" style="display: block; font-weight: 500; margin-bottom: 5px;">
                                <?php echo esc_html__( 'Počet periód', HELPDESK_TEXT_DOMAIN ); ?> <span style="color: red;">*</span>
                            </label>
                            <input type="number" id="auto-num-periods" value="4" min="1" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box;">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="button button-primary" style="width: auto;">
                            <?php echo esc_html__( 'Vygenerovať pohotovosti', HELPDESK_TEXT_DOMAIN ); ?>
                        </button>
                    </div>

                    <div class="error-message" style="display: none; color: #d32f2f; margin-top: 10px;"></div>
                    <div class="success-message" style="display: none; color: #2e7d32; margin-top: 10px;"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Standby Modal -->
<div id="helpdesk-standby-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content" style="max-width: 700px;">
        <div class="helpdesk-modal-header">
            <h2 id="standby-modal-title" style="margin: 0;"><?php echo esc_html__( 'Pridať pohotovosť', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close" type="button">&times;</button>
        </div>

        <form id="helpdesk-standby-modal-form" class="helpdesk-form">
            <input type="hidden" id="standby-id" value="">

            <div class="form-group">
                <label for="standby-employee-id"><?php echo esc_html__( 'Pracovník', HELPDESK_TEXT_DOMAIN ); ?></label>
                <select id="standby-employee-id" name="employee_id" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                    <option value="">-- <?php echo esc_html__( 'Vyberte pracovníka', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                    <?php foreach ( $employees as $emp ) : ?>
                        <option value="<?php echo absint( $emp['id'] ); ?>">
                            <?php echo esc_html( $emp['meno_priezvisko'] ); ?> (<?php echo esc_html( $emp['klapka'] ); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="standby-project-id"><?php echo esc_html__( 'Projekt', HELPDESK_TEXT_DOMAIN ); ?></label>
                <select id="standby-project-id" name="project_id" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                    <option value="">-- <?php echo esc_html__( 'Vyberte projekt', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                    <?php foreach ( $projects as $proj ) : ?>
                        <option value="<?php echo absint( $proj['id'] ); ?>">
                            <?php echo esc_html( $proj['zakaznicke_cislo'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label for="standby-od"><?php echo esc_html__( 'Od', HELPDESK_TEXT_DOMAIN ); ?></label>
                    <input type="date" id="standby-od" name="date_from" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box;">
                </div>
                <div class="form-group">
                    <label for="standby-do"><?php echo esc_html__( 'Do', HELPDESK_TEXT_DOMAIN ); ?></label>
                    <input type="date" id="standby-do" name="date_to" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box;">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary"><?php echo esc_html__( 'Uložiť', HELPDESK_TEXT_DOMAIN ); ?></button>
                <button type="button" class="button helpdesk-modal-close-btn"><?php echo esc_html__( 'Zrušiť', HELPDESK_TEXT_DOMAIN ); ?></button>
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
</style>
