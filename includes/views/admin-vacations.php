<?php
/**
 * Vacations Admin View
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use HelpDesk\Utils\Database;

$vacations_table = Database::get_vacations_table();
$employees_table = Database::get_employees_table();
$standby_table = Database::get_standby_table();
global $wpdb;

// Get vacations
$vacations = $wpdb->get_results(
    "SELECT * FROM {$vacations_table} ORDER BY meno_pracovnika ASC",
    ARRAY_A
);

// Get employees for filter
$employees = $wpdb->get_results(
    "SELECT id, meno_priezvisko FROM {$employees_table} ORDER BY meno_priezvisko ASC",
    ARRAY_A
);

// Get standby periods for conflict checking
$standby_periods = $wpdb->get_results(
    "SELECT pracovnik_id, pohotovost_od, pohotovost_do FROM {$standby_table}",
    ARRAY_A
);

// Get date range
$date_range = $wpdb->get_row(
    "SELECT MIN(nepritomnost_od) as min_date, MAX(nepritomnost_do) as max_date FROM {$vacations_table}",
    ARRAY_A
);
$min_date = $date_range['min_date'] ? date_i18n( 'd.m.Y', strtotime( $date_range['min_date'] ) ) : '';
$max_date = $date_range['max_date'] ? date_i18n( 'd.m.Y', strtotime( $date_range['max_date'] ) ) : '';
$date_range_text = ( $min_date && $max_date ) ? "{$min_date} - {$max_date}, " : '';
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Nepritomnosti', HELPDESK_TEXT_DOMAIN ); ?> 
        <span style="font-size: 18px; color: #666; font-weight: normal;">
            (<?php echo esc_html( $date_range_text ); ?><?php echo esc_html( count( $vacations ) ); ?> <?php echo count( $vacations ) === 1 ? 'záznam' : 'záznamov'; ?> - po filtrácii: <span id="vacation-count-after-filter"><?php echo esc_html( count( $vacations ) ); ?></span> záznamov)
        </span>
    </h1>

    <div class="helpdesk-admin-container">
        <div class="helpdesk-button-group">
            <button class="button button-primary helpdesk-btn-import-vacations">
                <?php echo esc_html__( '📥 Importovať nepritomnosti z CSV', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
            <button class="button helpdesk-btn-check-vacation-duplicates" style="color: #dc3545;">
                <?php echo esc_html__( 'Skontrolovať duplikáty', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
            <button class="button helpdesk-btn-delete-old-vacations" style="background-color: #fff3cd; color: #856404; border-color: #ffc107;">
                <?php echo esc_html__( '🗑️ Vymazať staré nepritomnosti', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
        </div>

        <!-- Hidden file input for CSV import -->
        <input type="file" id="helpdesk-vacations-csv-input" accept=".csv" style="display: none;">

        <div style="margin-top: 20px;">
            <h2><?php echo esc_html__( 'Tabuľka nepritomností', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <p style="color: #666; font-size: 13px;">
                <?php echo esc_html__( 'Krok 1: Importujte CSV so stĺpcami: Pracovník, Od, Do', HELPDESK_TEXT_DOMAIN ); ?>
                <br>
                <?php echo esc_html__( 'Krok 2: Kliknite na "Synchronizovať ID" aby sa doplnili ID pracovníkov', HELPDESK_TEXT_DOMAIN ); ?>
                <br>
                <?php echo esc_html__( 'Krok 3: Kliknite na "Aplikovať nepritomnosti" aby sa skopírovali do tabuľky pracovníkov', HELPDESK_TEXT_DOMAIN ); ?>
            </p>
        </div>

        <!-- Filters -->
        <div style="margin-bottom: 20px; margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px;">
                <div>
                    <label for="filter-vacation-employee" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Pracovník', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="filter-vacation-employee" class="widefat" style="padding: 8px;">
                        <option value=""><?php echo esc_html__( 'Všetci pracovníci', HELPDESK_TEXT_DOMAIN ); ?></option>
                        <?php foreach ( $employees as $emp ) : ?>
                            <option value="<?php echo esc_attr( $emp['meno_priezvisko'] ); ?>"><?php echo esc_html( $emp['meno_priezvisko'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="filter-vacation-date-from" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Nepritomnosť Od', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <input type="date" id="filter-vacation-date-from" class="widefat" style="padding: 8px;">
                </div>

                <div>
                    <label for="filter-vacation-date-to" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Nepritomnosť Do', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <input type="date" id="filter-vacation-date-to" class="widefat" style="padding: 8px;">
                </div>

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Možnosti', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <div style="display: flex; gap: 5px;">
                        <button id="btn-vacation-today" class="button" style="flex: 1; padding: 8px;">
                            <?php echo esc_html__( 'Dnes', HELPDESK_TEXT_DOMAIN ); ?>
                        </button>
                        <button id="btn-reset-vacation-filters" class="button" style="flex: 1; padding: 8px;">
                            <?php echo esc_html__( 'Vynulovať', HELPDESK_TEXT_DOMAIN ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="helpdesk-table-wrapper" style="margin-top: 15px;">
            <table class="wp-list-table widefat fixed striped" id="helpdesk-vacations-table">
                <thead>
                    <tr>
                        <th scope="col" style="width: 40px;"><input type="checkbox" id="helpdesk-select-all-vacations" title="<?php echo esc_attr__( 'Vybrať všetky', HELPDESK_TEXT_DOMAIN ); ?>"></th>
                        <th scope="col" class="column-meno"><?php echo esc_html__( 'Pracovník', HELPDESK_TEXT_DOMAIN ); ?></th>
                        <th scope="col" class="column-id"><?php echo esc_html__( 'ID Pracovníka', HELPDESK_TEXT_DOMAIN ); ?></th>
                        <th scope="col" class="column-od"><?php echo esc_html__( 'Nepritomnosť Od', HELPDESK_TEXT_DOMAIN ); ?></th>
                        <th scope="col" class="column-do"><?php echo esc_html__( 'Nepritomnosť Do', HELPDESK_TEXT_DOMAIN ); ?></th>
                        <th scope="col" class="column-status"><?php echo esc_html__( 'Stav', HELPDESK_TEXT_DOMAIN ); ?></th>
                        <th scope="col" class="column-actions"><?php echo esc_html__( 'Akcie', HELPDESK_TEXT_DOMAIN ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $vacations ) ) : ?>
                        <?php foreach ( $vacations as $vacation ) : ?>
                            <?php
                            // Check if this vacation conflicts with any standby period
                            $has_standby_conflict = false;
                            if ( ! empty( $standby_periods ) ) {
                                foreach ( $standby_periods as $standby ) {
                                    if ( $standby['pracovnik_id'] == $vacation['pracovnik_id'] ) {
                                        // Check if dates overlap
                                        if ( strtotime( $standby['pohotovost_od'] ) <= strtotime( $vacation['nepritomnost_do'] ) &&
                                             strtotime( $standby['pohotovost_do'] ) >= strtotime( $vacation['nepritomnost_od'] ) ) {
                                            $has_standby_conflict = true;
                                            break;
                                        }
                                    }
                                }
                            }
                            $row_class = $has_standby_conflict ? 'helpdesk-vacation-with-standby' : '';
                            ?>
                            <tr data-vacation-id="<?php echo absint( $vacation['id'] ); ?>" data-employee="<?php echo esc_attr( $vacation['meno_pracovnika'] ); ?>" data-date-from="<?php echo esc_attr( $vacation['nepritomnost_od'] ); ?>" data-date-to="<?php echo esc_attr( $vacation['nepritomnost_do'] ); ?>" class="<?php echo esc_attr( $row_class ); ?>">
                                <td><input type="checkbox" class="helpdesk-vacation-checkbox" value="<?php echo absint( $vacation['id'] ); ?>"></td>
                                <td class="column-meno"><?php echo esc_html( $vacation['meno_pracovnika'] ); ?></td>
                                <td class="column-id">
                                    <?php if ( $vacation['pracovnik_id'] ) : ?>
                                        <span style="background-color: #d4edda; color: #155724; padding: 3px 8px; border-radius: 3px; font-weight: 600; font-size: 11px;">
                                            ✓ <?php echo absint( $vacation['pracovnik_id'] ); ?>
                                        </span>
                                    <?php else : ?>
                                        <span style="background-color: #f8d7da; color: #721c24; padding: 3px 8px; border-radius: 3px; font-weight: 600; font-size: 11px;">
                                            ✗ Chýba
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-od"><?php echo esc_html( wp_date( 'd.m.Y', strtotime( $vacation['nepritomnost_od'] ) ) ); ?></td>
                                <td class="column-do"><?php echo esc_html( wp_date( 'd.m.Y', strtotime( $vacation['nepritomnost_do'] ) ) ); ?></td>
                                <td class="column-status">
                                    <?php 
                                        if ( ! $vacation['pracovnik_id'] ) {
                                            echo '<span style="color: #dc3545;">Chýba ID</span>';
                                        } else {
                                            echo '<span style="color: #28a745;">Hotovo</span>';
                                        }
                                    ?>
                                </td>
                                <td class="column-actions" style="text-align: center;">
                                    <button class="button button-small helpdesk-btn-edit-vacation" data-id="<?php echo absint( $vacation['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Upraviť', HELPDESK_TEXT_DOMAIN ); ?>">
                                        ✏️
                                    </button>
                                    <button class="button button-small helpdesk-btn-delete-vacation" data-id="<?php echo absint( $vacation['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Zmazať', HELPDESK_TEXT_DOMAIN ); ?>">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7" class="center"><?php echo esc_html__( 'Žiadne nepritomnosti. Importujte CSV súbor.', HELPDESK_TEXT_DOMAIN ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 15px; display: flex; gap: 10px;">
            <button id="helpdesk-sync-vacation-ids" class="button button-primary" style="display: none;">
                <?php echo esc_html__( 'Synchronizovať ID pracovníkov', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
            <button id="helpdesk-apply-vacations" class="button button-primary" style="display: none;">
                <?php echo esc_html__( '✓ Aplikovať nepritomnosti na pracovníkov', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
            <button id="helpdesk-delete-all-vacations" class="button button-link-delete">
                <?php echo esc_html__( '🗑️ Vymazať všetky nepritomnosti', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
        </div>
    </div>
</div>

<style>
.helpdesk-admin-container {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
    margin-top: 20px;
}

.helpdesk-button-group {
    margin-bottom: 15px;
}

.helpdesk-button-group button {
    margin-right: 10px;
}

.helpdesk-table-wrapper {
    overflow-x: auto;
}

.wp-list-table {
    margin-top: 15px;
}

.helpdesk-vacation-checkbox {
    width: auto;
    margin: 0;
}

.column-meno {
    font-weight: 500;
}

.column-id {
    text-align: center;
}

.column-status {
    text-align: center;
    font-size: 12px;
}

.column-actions {
    text-align: center;
}
</style>

<!-- Edit Absence Modal -->
<div id="helpdesk-vacation-edit-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content" style="max-width: 500px;">
        <div class="helpdesk-modal-header">
            <h2><?php echo esc_html__( 'Upraviť nepritomnosť', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close" type="button">&times;</button>
        </div>
        <form id="helpdesk-vacation-edit-form" class="helpdesk-form" style="padding: 20px;">
            <input type="hidden" id="edit-vacation-id" value="">

            <div class="form-group">
                <label for="edit-vacation-meno">
                    <?php echo esc_html__( 'Pracovník', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <input type="text" id="edit-vacation-meno" name="meno_pracovnika" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
            </div>

            <div class="form-group">
                <label for="edit-vacation-od">
                    <?php echo esc_html__( 'Nepritomnosť Od:', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <input type="date" id="edit-vacation-od" name="nepritomnost_od" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
            </div>

            <div class="form-group">
                <label for="edit-vacation-do">
                    <?php echo esc_html__( 'Nepritomnosť Do:', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <input type="date" id="edit-vacation-do" name="nepritomnost_do" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">
                    <?php echo esc_html__( 'Uložiť zmeny', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
                <button type="button" class="button helpdesk-modal-close-btn">
                    <?php echo esc_html__( 'Zrušiť', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.helpdesk-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
}

.helpdesk-modal-content {
    background-color: #fefefe;
    margin: 10% auto;
    border: 1px solid #888;
    width: 80%;
    border-radius: 5px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.helpdesk-modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.helpdesk-modal-header h2 {
    margin: 0;
    font-size: 18px;
}

.helpdesk-modal-close {
    background: none;
    border: none;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.helpdesk-modal-close:hover {
    color: #000;
}

.helpdesk-modal-close-btn {
    margin-left: auto;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    font-size: 12px;
}

.form-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 12px;
}

.form-actions button {
    padding: 6px 12px;
    font-size: 12px;
}

.helpdesk-vacation-with-standby {
    opacity: 0.7;
    background-color: #fff3cd !important;
}

.helpdesk-vacation-with-standby td {
    color: #666;
}

.helpdesk-duplicates-list {
    margin-top: 15px;
    padding: 12px;
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 4px;
}

.helpdesk-duplicates-list h3 {
    margin-top: 0;
    color: #856404;
}

.helpdesk-duplicate-item {
    margin-bottom: 10px;
    padding: 8px;
    background: white;
    border-radius: 3px;
}

.helpdesk-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.helpdesk-modal-content {
    background: white;
    padding: 30px;
    border-radius: 8px;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.helpdesk-spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 20px auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.helpdesk-result-item {
    margin: 10px 0;
    padding: 8px;
    background: #f9f9f9;
    border-left: 3px solid #0073aa;
}

.helpdesk-result-item.success {
    border-left-color: #28a745;
}

.helpdesk-result-item.error {
    border-left-color: #dc3545;
}

.helpdesk-modal-close-btn {
    float: right;
    font-size: 28px;
    font-weight: bold;
    line-height: 20px;
    color: #aaa;
    cursor: pointer;
}

.helpdesk-modal-close-btn:hover {
    color: #000;
}
</style>

<script>
(function($) {
    'use strict';
    
    // Check for duplicate vacations
    $('.helpdesk-btn-check-vacation-duplicates').on('click', function() {
        // Show modal with spinner
        showProcessModal('Kontrola duplikátov nepritomností...', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'helpdesk_check_vacation_duplicates',
                nonce: helpdeskNonce
            },
            timeout: 30000, // 30 second timeout
            success: function(response) {
                if (response.success) {
                    let html = '<h2>Výsledok kontroly duplikátov</h2>';
                    html += '<div class="helpdesk-result-item success">';
                    html += '<strong>✓ ' + response.data.message + '</strong>';
                    html += '</div>';
                    
                    if (response.data.count > 0) {
                        html += '<div style="margin-top: 15px;"><h3>Nájdené duplikáty (prvých ' + response.data.count + '):</h3>';
                        
                        response.data.duplicates.forEach(function(dup) {
                            html += '<div class="helpdesk-result-item error">';
                            html += '<strong>👤 Pracovník:</strong> ' + dup.employee + '<br>';
                            html += '<strong>📅 Záznam 1:</strong> ' + dup.record1.od + ' - ' + dup.record1.do + ' (ID: ' + dup.record1.id + ')<br>';
                            html += '<strong>📅 Záznam 2:</strong> ' + dup.record2.od + ' - ' + dup.record2.do + ' (ID: ' + dup.record2.id + ')';
                            html += '</div>';
                        });
                        
                        if (response.data.count >= 50) {
                            html += '<div class="helpdesk-result-item" style="background-color: #fff3cd;">';
                            html += '<em>... a ďalšie duplikáty. Zobrazené prvých 50.</em>';
                            html += '</div>';
                        }
                        
                        html += '</div>';
                    }
                    
                    showProcessModal(html, false);
                } else {
                    showProcessModal('<h2>Chyba</h2><div class="helpdesk-result-item error"><strong>✗ ' + response.data.message + '</strong></div>', false);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                let errorMsg = 'Chyba pri kontrole duplikátov';
                if (textStatus === 'timeout') {
                    errorMsg = 'Timeout - operácia trvá príliš dlho';
                }
                showProcessModal('<h2>Chyba</h2><div class="helpdesk-result-item error"><strong>✗ ' + errorMsg + '</strong></div>', false);
            }
        });
    });
    
    // Delete old vacations
    $('.helpdesk-btn-delete-old-vacations').on('click', function() {
        if (!confirm('Naozaj chcete vymazať všetky staré nepritomnosti (do dnešného dňa)? Toto sa neda vratiť!')) {
            return;
        }
        
        // Show modal with spinner and progress bar
        showProcessModal('<div style="text-align: center;"><p>Mazanie starých nepritomností...</p><div style="margin: 20px 0;"><div style="height: 30px; background: #e9ecef; border-radius: 4px; overflow: hidden;"><div id="progress-bar" style="height: 100%; width: 0%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;"><span id="progress-text">0%</span></div></div></div><p id="progress-message">Spracováva sa...</p></div>', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'helpdesk_delete_old_vacations',
                nonce: helpdeskNonce
            },
            timeout: 60000, // 60 second timeout
            success: function(response) {
                if (response.success) {
                    let html = '<h2>Výsledok mazania</h2>';
                    html += '<div class="helpdesk-result-item success">';
                    html += '<strong>✓ ' + response.data.message + '</strong>';
                    html += '</div>';
                    
                    html += '<div style="margin-top: 20px;">';
                    html += '<h3>📊 Zhrnutie operácie:</h3>';
                    html += '<div class="helpdesk-result-item">';
                    html += '📋 <strong>Záznamov pred mazaním:</strong> ' + response.data.before + '<br>';
                    html += '🗑️ <strong>Vymazaných:</strong> ' + response.data.deleted + '<br>';
                    html += '✅ <strong>Zostávajúcich:</strong> ' + response.data.after + '<br>';
                    if (response.data.before > 0) {
                        html += '<strong style="color: #28a745;">Úspora: -' + Math.round((response.data.deleted / response.data.before) * 100) + '%</strong>';
                    }
                    html += '</div>';
                    html += '</div>';
                    
                    showProcessModal(html, false);
                    
                    // Reload page after 3 seconds
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                } else {
                    let html = '<h2>Chyba pri mazaní</h2>';
                    html += '<div class="helpdesk-result-item error">';
                    html += '<strong>✗ ' + response.data.message + '</strong>';
                    html += '</div>';
                    
                    if (response.data.total_records) {
                        html += '<p>Celkem záznamov: ' + response.data.total_records + '</p>';
                    }
                    
                    showProcessModal(html, false);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                let errorMsg = 'Chyba pri mazaní nepritomností';
                if (textStatus === 'timeout') {
                    errorMsg = 'Timeout - mazanie trvá príliš dlho. Skúste neskôr.';
                }
                showProcessModal('<h2>Chyba</h2><div class="helpdesk-result-item error"><strong>✗ ' + errorMsg + '</strong></div>', false);
            }
        });
    });
    
    // Helper function to show process modal
    function showProcessModal(content, showSpinner) {
        let html = '<div class="helpdesk-modal-overlay">';
        html += '<div class="helpdesk-modal-content">';
        html += '<span class="helpdesk-modal-close-btn" onclick="jQuery(this).closest(\'.helpdesk-modal-overlay\').remove();">×</span>';
        
        if (showSpinner) {
            html += '<div class="helpdesk-spinner"></div>';
        }
        
        html += content;
        
        if (!showSpinner) {
            html += '<div style="margin-top: 20px; text-align: right;">';
            html += '<button class="button button-primary" onclick="jQuery(this).closest(\'.helpdesk-modal-overlay\').remove();">Zavrieť</button>';
            html += '</div>';
        }
        
        html += '</div></div>';
        
        // Remove existing modal if any
        $('.helpdesk-modal-overlay').remove();
        
        // Add new modal
        $('body').append(html);
    }
})(jQuery);
</script>

