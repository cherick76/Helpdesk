<?php
/**
 * Employees Admin View
 */

use HelpDesk\Models\Employee;
use HelpDesk\Models\Position;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$employees = Employee::get_all();
$positions = Position::get_all();
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Pracovníci', HELPDESK_TEXT_DOMAIN ); ?></h1>

    <div class="helpdesk-admin-container">
        <div class="helpdesk-button-group">
            <button class="button button-primary helpdesk-btn-new-employee">
                <?php echo esc_html__( '+ Pridať pracovníka', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
            <button class="button helpdesk-btn-export-employees">
                <?php echo esc_html__( 'Exportovať do CSV', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
            <button class="button helpdesk-btn-import-employees">
                <?php echo esc_html__( 'Importovať z CSV', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
        </div>

        <div style="margin-bottom: 20px;">
            <input type="text" id="helpdesk-employees-search" class="helpdesk-search-input" placeholder="<?php echo esc_attr__( 'Vyhľadať pracovníka...', HELPDESK_TEXT_DOMAIN ); ?>">
        </div>

        <!-- Filters -->
        <div style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px;">
                <div>
                    <label for="filter-employees-position" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Pozícia', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="filter-employees-position" class="widefat" style="padding: 8px;">
                        <option value="">-- <?php echo esc_html__( 'Všetky', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                        <?php foreach ( $positions as $pos ) : ?>
                            <option value="<?php echo absint( $pos['id'] ); ?>"><?php echo esc_html( $pos['profesia'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="filter-employees-standby" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Pohotovosť', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="filter-employees-standby" class="widefat" style="padding: 8px;">
                        <option value="">-- <?php echo esc_html__( 'Všetci', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                        <option value="has">✓ <?php echo esc_html__( 'Má pohotovosť', HELPDESK_TEXT_DOMAIN ); ?></option>
                        <option value="no">✗ <?php echo esc_html__( 'Nemá pohotovosť', HELPDESK_TEXT_DOMAIN ); ?></option>
                    </select>
                </div>

                <div style="opacity: 0; pointer-events: none;">
                    <label style="display: block; font-weight: 500; margin-bottom: 5px;">.</label>
                    <div style="height: 36px;"></div>
                </div>

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 5px; visibility: hidden;">Akcia</label>
                    <button id="btn-reset-employees-filters" class="button" style="width: 100%; padding: 8px;">
                        <?php echo esc_html__( 'Vynulovať filtre', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden file input for CSV import -->
        <input type="file" id="helpdesk-employees-csv-input" accept=".csv" style="display: none;">

        <div class="helpdesk-table-wrapper">
        <table class="wp-list-table widefat fixed striped" id="helpdesk-employees-table">
            <thead>
                <tr>
                    <th scope="col" style="width: 40px;"><input type="checkbox" id="helpdesk-select-all-employees" title="Vybrať všetkých"></th>
                    <th scope="col" class="column-name sortable" data-sort-field="meno_priezvisko" style="cursor: pointer;">
                        <?php echo esc_html__( 'Meno a Priezvisko', HELPDESK_TEXT_DOMAIN ); ?>
                        <span class="sort-indicator"></span>
                    </th>
                    <th scope="col" class="column-klapka sortable" data-sort-field="klapka" style="cursor: pointer;">
                        <?php echo esc_html__( 'Klapka', HELPDESK_TEXT_DOMAIN ); ?>
                        <span class="sort-indicator"></span>
                    </th>
                    <th scope="col" class="column-mobil sortable" data-sort-field="mobil" style="cursor: pointer;">
                        <?php echo esc_html__( 'Mobil', HELPDESK_TEXT_DOMAIN ); ?>
                        <span class="sort-indicator"></span>
                    </th>
                    <th scope="col" class="column-pozicia sortable" data-sort-field="pozicia_id" style="cursor: pointer;">
                        <?php echo esc_html__( 'Pozícia', HELPDESK_TEXT_DOMAIN ); ?>
                        <span class="sort-indicator"></span>
                    </th>
                    <th scope="col" class="column-projekty"><?php echo esc_html__( 'Projekty', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-pohotovost" style="text-align: center;"><?php echo esc_html__( 'Pohotovosť', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-actions"><?php echo esc_html__( 'Akcie', HELPDESK_TEXT_DOMAIN ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $employees ) ) : ?>
                    <?php foreach ( $employees as $employee ) : ?>
                        <?php 
                            $emp = new \HelpDesk\Models\Employee( $employee['id'] );
                            $projects_count = count( $emp->get_projects() );
                            $standby_count = $emp->get_standby_periods_count();
                            $has_position = ! empty( $employee['pozicia_id'] ) ? absint( $employee['pozicia_id'] ) : '0';
                            $has_standby = $standby_count > 0 ? 'yes' : 'no';
                        ?>
                        <tr data-employee-id="<?php echo absint( $employee['id'] ); ?>" data-position="<?php echo esc_attr( $has_position ); ?>" data-standby="<?php echo esc_attr( $has_standby ); ?>">
                            <td><input type="checkbox" class="helpdesk-employee-checkbox" value="<?php echo absint( $employee['id'] ); ?>"></td>
                            <td><?php echo esc_html( $employee['meno_priezvisko'] ); ?></td>
                            <td class="column-klapka"><?php echo esc_html( $employee['klapka'] ); ?></td>
                            <td class="column-mobil"><?php echo esc_html( $employee['mobil'] ?? '' ); ?></td>
                            <td class="column-pozicia"><?php 
                                $position_id = $employee['pozicia_id'] ?? null;
                                if ($position_id) {
                                    $positions = \HelpDesk\Models\Position::get_all();
                                    foreach ($positions as $pos) {
                                        if ($pos['id'] == $position_id) {
                                            echo esc_html($pos['profesia']);
                                            break;
                                        }
                                    }
                                } else {
                                    echo '--';
                                }
                            ?></td>
                            <td class="column-projekty"><strong><?php echo absint( $projects_count ); ?></strong></td>
                            <td class="column-pohotovost" style="text-align: center;">
                                <?php if ( $standby_count > 0 ) : ?>
                                    <span style="background-color: #d4edda; color: #155724; padding: 3px 8px; border-radius: 3px; font-weight: 600; font-size: 11px;">
                                        ✓ <?php echo absint( $standby_count ); ?>
                                    </span>
                                <?php else : ?>
                                    <span style="background-color: #f8d7da; color: #721c24; padding: 3px 8px; border-radius: 3px; font-weight: 600; font-size: 11px;">
                                        ✗ Nemá
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions" style="text-align: center; font-size: 18px;">
                                <button class="button button-small helpdesk-btn-edit" data-id="<?php echo absint( $employee['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Upraviť', HELPDESK_TEXT_DOMAIN ); ?>">
                                    ✏️
                                </button>
                                <button class="button button-small helpdesk-btn-standby" data-id="<?php echo absint( $employee['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Prideľovať pohotovosť', HELPDESK_TEXT_DOMAIN ); ?>">
                                    📅
                                </button>
                                <button class="button button-small helpdesk-btn-vacation" data-id="<?php echo absint( $employee['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Nepritomnosť', HELPDESK_TEXT_DOMAIN ); ?>">
                                    🏖️
                                </button>
                                <button class="button button-small button-link-delete helpdesk-btn-delete" data-id="<?php echo absint( $employee['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Zmazať', HELPDESK_TEXT_DOMAIN ); ?>">
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8" class="center"><?php echo esc_html__( 'Žádní pracovníci nebyli nalezeni.', HELPDESK_TEXT_DOMAIN ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <div style="margin-top: 15px;">
            <button id="helpdesk-bulk-assign-projects" class="button button-primary" style="display: none;">
                <?php echo esc_html__( 'Hromadne priradiť projekty', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
        </div>
    </div>
</div><!-- Employee Detail Modal (read-only) -->
<div id="helpdesk-employee-detail-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content" style="max-width: 400px;">
        <div class="helpdesk-modal-header">
            <h2 id="employee-detail-title" style="margin: 0;"></h2>
            <button class="helpdesk-modal-close" type="button">&times;</button>
        </div>
        <div style="padding: 20px; display: grid; grid-template-columns: auto 1fr; gap: 15px 10px;">
            <strong><?php echo esc_html__( 'Meno:', HELPDESK_TEXT_DOMAIN ); ?></strong>
            <div id="employee-detail-meno" style="color: #333;"></div>
            
            <strong><?php echo esc_html__( 'Klapka:', HELPDESK_TEXT_DOMAIN ); ?></strong>
            <div id="employee-detail-klapka" style="color: #333;"></div>
            
            <strong><?php echo esc_html__( 'Telefón:', HELPDESK_TEXT_DOMAIN ); ?></strong>
            <div id="employee-detail-mobil" style="color: #333;"></div>
            
            <strong><?php echo esc_html__( 'Poznámka:', HELPDESK_TEXT_DOMAIN ); ?></strong>
            <div id="employee-detail-poznamka" style="color: #333; grid-column: 2;"></div>
        </div>
        <div style="padding: 15px 20px; background-color: #f5f5f5; border-top: 1px solid #ddd; text-align: right;">
            <button type="button" class="button helpdesk-modal-close-btn">Zatvoriť</button>
        </div>
    </div>
</div><!-- Employee Modal -->
<div id="helpdesk-employee-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content">
        <div class="helpdesk-modal-header">
            <h2 id="employee-modal-title"><?php echo esc_html__( 'Pridať pracovníka', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close">&times;</button>
        </div>
        <div class="helpdesk-modal-body">
            <form id="helpdesk-employee-form" class="helpdesk-form" style="display: contents;">
            <input type="hidden" id="employee-id" name="id" value="">

            <div class="form-group">
                <label for="employee-meno_priezvisko">
                    <?php echo esc_html__( 'Meno a Priezvisko', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                </label>
                <input type="text" id="employee-meno_priezvisko" name="meno_priezvisko" class="widefat" required>
                <span class="error-message" id="error-meno_priezvisko"></span>
            </div>

            <div class="form-group">
                <label for="employee-klapka">
                    <?php echo esc_html__( 'Klapka', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <input type="text" id="employee-klapka" name="klapka" class="widefat" placeholder="0000" maxlength="4">
                <span class="error-message" id="error-klapka"></span>
            </div>

            <div class="form-group">
                <label for="employee-mobil">
                    <?php echo esc_html__( 'Mobil', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <input type="text" id="employee-mobil" name="mobil" class="widefat">
                <span class="error-message" id="error-mobil"></span>
            </div>

            <div class="form-group">
                <label for="employee-pozicia">
                    <?php echo esc_html__( 'Pozícia', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <select id="employee-pozicia" name="pozicia_id" class="widefat">
                    <option value=""><?php echo esc_html__( '-- Vyberte pozíciu --', HELPDESK_TEXT_DOMAIN ); ?></option>
                </select>
                <span class="error-message" id="error-pozicia_id"></span>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label for="employee-poznamka">
                    <?php echo esc_html__( 'Poznámka', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <textarea id="employee-poznamka" name="poznamka" class="widefat" rows="4"></textarea>
                <span class="error-message" id="error-poznamka"></span>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label><?php echo esc_html__( 'Projekty', HELPDESK_TEXT_DOMAIN ); ?></label>
                <input type="text" id="employee-projects-search" class="widefat" placeholder="<?php echo esc_attr__( 'Vyhľadať projekt...', HELPDESK_TEXT_DOMAIN ); ?>" style="margin-bottom: 10px;">
                <div id="employee-projects-list" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; border: 1px solid #ddd; border-radius: 3px; max-height: 250px; overflow-y: auto; padding: 10px;">
                    <!-- Projektami sa napĺňa JavaScript -->
                </div>
            </div>

            <div class="form-group" style="grid-column: 1 / -1; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="employee-pohotovost-checkbox" name="pohotovost" value="1">
                <label for="employee-pohotovost-checkbox" style="margin: 0; font-weight: normal;">
                    <?php echo esc_html__( 'Pracovník je v pohotovosti', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">
                    <?php echo esc_html__( 'Uložiť', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
                <button type="button" class="button helpdesk-modal-close-btn">
                    <?php echo esc_html__( 'Zrušiť', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Standby Assignment Modal -->
<div id="helpdesk-standby-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content" style="max-width: 800px;">
        <div class="helpdesk-modal-header">
            <h2><?php echo esc_html__( 'Prideľovanie pohotovosti', HELPDESK_TEXT_DOMAIN ); ?> - <span id="standby-employee-name"></span></h2>
            <button class="helpdesk-modal-close">&times;</button>
        </div>
        <div style="padding: 20px;">
            <!-- MANUÁLNE GENEROVANIE POHOTOVOSTI -->
            <div style="border: 2px solid #28a745; border-radius: 3px; padding: 15px; margin-bottom: 20px; background-color: #f1f8f4;">
                <h3 style="margin-top: 0; color: #28a745;">✓ <?php echo esc_html__( 'Manuálne prideľovanie pohotovosti', HELPDESK_TEXT_DOMAIN ); ?></h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="standby-project-select">
                            <?php echo esc_html__( 'Projekt', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                        </label>
                        <select id="standby-project-select" style="width: 100%; padding: 5px 6px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
                            <option value="">-- <?php echo esc_html__( 'Vyberte projekt', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                        </select>
                    </div>

                    <div class="form-group" style="grid-column: 1;">
                        <label for="standby-od">
                            <?php echo esc_html__( 'Od', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                        </label>
                        <input type="date" id="standby-od" style="width: 100%; padding: 5px 6px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
                    </div>

                    <div class="form-group" style="grid-column: 2;">
                        <label for="standby-do">
                            <?php echo esc_html__( 'Do', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                        </label>
                        <input type="date" id="standby-do" style="width: 100%; padding: 5px 6px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
                    </div>
                </div>

                <button type="button" id="btn-add-standby" class="button button-primary" style="margin-top: 10px;">
                    <?php echo esc_html__( 'Pridať pohotovosť', HELPDESK_TEXT_DOMAIN ); ?>
                </button>

                <div id="standby-periods-list" style="margin-top: 15px;">
                    <!-- Pohotovostné obdobia sa budú zobrazovať tu -->
                </div>
            </div>

            <!-- AUTOMATICKÉ GENEROVANIE POHOTOVOSTI -->
            <div style="border: 1px dashed #6c757d; border-radius: 3px; padding: 15px; background-color: #f8f9fa;">
                <h4 style="margin-top: 0; color: #6c757d;">⚙ <?php echo esc_html__( 'Automatické generovanie pohotovosti', HELPDESK_TEXT_DOMAIN ); ?></h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="standby-start-date">
                                <?php echo esc_html__( 'Začiatok striedania', HELPDESK_TEXT_DOMAIN ); ?>
                            </label>
                            <input type="date" id="standby-start-date" style="width: 100%; padding: 5px 6px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
                        </div>

                        <div class="form-group">
                            <label for="standby-interval-type">
                                <?php echo esc_html__( 'Typ intervalu', HELPDESK_TEXT_DOMAIN ); ?>
                            </label>
                            <select id="standby-interval-type" style="width: 100%; padding: 5px 6px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
                                <option value="week">Týždeň</option>
                                <option value="weeks">X Týždňov</option>
                                <option value="month">Mesiac</option>
                                <option value="months">X Mesiacov</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="standby-interval-count">
                                <?php echo esc_html__( 'Trvanie intervalu', HELPDESK_TEXT_DOMAIN ); ?>
                            </label>
                            <input type="number" id="standby-interval-count" value="1" min="1" style="width: 100%; padding: 5px 6px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
                        </div>

                        <div class="form-group">
                            <label for="standby-num-periods" id="standby-num-periods-label">
                                <?php echo esc_html__( 'Počet periód', HELPDESK_TEXT_DOMAIN ); ?>
                            </label>
                            <input type="number" id="standby-num-periods" value="12" min="1" style="width: 100%; padding: 5px 6px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="standby-rotation-employees">
                            <?php echo esc_html__( 'Ďalší pracovníci v rotácii', HELPDESK_TEXT_DOMAIN ); ?>
                        </label>
                        <select id="standby-rotation-employees" multiple style="width: 100%; height: 80px; padding: 5px 6px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
                            <!-- Naplní sa JavaScriptom -->
                        </select>
                        <small style="display: block; margin-top: 5px;">Ctrl+Click na vyber viacerých</small>
                    </div>

                    <div class="form-group">
                        <label><?php echo esc_html__( 'Projekty', HELPDESK_TEXT_DOMAIN ); ?></label>
                        <div id="standby-projects-list" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; border: 1px solid #ddd; border-radius: 3px; padding: 8px; max-height: 120px; overflow-y: auto;">
                            <!-- Checkboxes pre projekty sa naplnia JavaScriptom -->
                        </div>
                    </div>

                    <button type="button" id="btn-generate-standby" class="button button-primary" style="margin-top: 10px;">
                        <?php echo esc_html__( 'Generovať a vložiť', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </div>

            <div style="margin-top: 15px; text-align: right;">
                <button type="button" class="button helpdesk-modal-close-btn">
                    <?php echo esc_html__( 'Zatvoriť', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.helpdesk-admin-container {
    margin-top: 20px;
}

.helpdesk-button-group {
    margin-bottom: 20px;
}

.column-id, .column-klapka, .column-mobil, .column-actions {
    width: 150px;
}

.helpdesk-button-group {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.helpdesk-search-input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 3px;
    min-width: 250px;
}

.column-name {
    width: 250px;
}

.helpdesk-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.4);
}

.helpdesk-modal-content {
    background-color: #fefefe;
    margin: 3% auto;
    padding: 12px;
    border: 1px solid #888;
    border-radius: 5px;
    width: 95%;
    max-width: 900px;
    max-height: 85vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.helpdesk-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.helpdesk-modal-header h2 {
    margin: 0;
    font-size: 18px;
}

.helpdesk-modal-close {
    font-size: 24px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
    border: none;
    background: none;
}

.helpdesk-modal-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    overflow-y: auto;
    flex-grow: 1;
    padding-bottom: 10px;
}

.form-group {
    margin-bottom: 8px;
}

.form-group label {
    display: block;
    margin-bottom: 3px;
    font-weight: 600;
    font-size: 12px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 5px 6px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 12px;
    box-sizing: border-box;
}

.form-group input[type="checkbox"] {
    width: auto;
    height: 16px;
    width: 16px;
    padding: 0;
    margin-right: 6px;
}

.form-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 12px;
    grid-column: 1 / -1;
}

.form-actions button {
    padding: 6px 12px;
    font-size: 12px;
}

.error-message {
    display: block;
    color: #d32f2f;
    font-size: 11px;
    margin-top: 2px;
}

.required {
    color: #d32f2f;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}
</style>

<!-- Bulk Assign Projects Modal -->
<div id="helpdesk-bulk-projects-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content">
        <div class="helpdesk-modal-header">
            <h2><?php echo esc_html__( 'Hromadne priradiť projekty', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close">&times;</button>
        </div>
        <form id="helpdesk-bulk-projects-form" class="helpdesk-form">
            <div class="form-group">
                <label><?php echo esc_html__( 'Vyberte projekty:', HELPDESK_TEXT_DOMAIN ); ?></label>
                <div id="bulk-projects-list" style="border: 1px solid #ddd; border-radius: 3px; max-height: 300px; overflow-y: auto; padding: 10px;">
                    <!-- Filled by JavaScript -->
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">
                    <?php echo esc_html__( 'Priradiť', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
                <button type="button" class="button helpdesk-modal-close-btn">
                    <?php echo esc_html__( 'Zrušiť', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Vacation Modal -->
<div id="helpdesk-vacation-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content" style="max-width: 400px;">
        <div class="helpdesk-modal-header">
            <h2><?php echo esc_html__( 'Nepritomnosť', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close" type="button">&times;</button>
        </div>
        <form id="helpdesk-vacation-form" class="helpdesk-form" style="padding: 20px;">
            <input type="hidden" id="vacation-employee-id" value="">

            <div class="form-group">
                <label for="vacation-od">
                    <?php echo esc_html__( 'Nepritomnosť od:', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <input type="date" id="vacation-od" name="vacation_od" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
            </div>

            <div class="form-group">
                <label for="vacation-do">
                    <?php echo esc_html__( 'Nepritomnosť do:', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <input type="date" id="vacation-do" name="vacation_do" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">
                    <?php echo esc_html__( 'Uložiť dovolenku', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
                <button type="button" class="button helpdesk-btn-remove-vacation" style="display: none;">
                    <?php echo esc_html__( '🗑️ Vymazať dovolenku', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
                <button type="button" class="button helpdesk-modal-close-btn">
                    <?php echo esc_html__( 'Zrušiť', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
            </div>
        </form>
    </div>

