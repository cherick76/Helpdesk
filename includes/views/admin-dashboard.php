<?php
/**
 * Dashboard View
 */

use HelpDesk\Models\Employee;
use HelpDesk\Models\Product;
use HelpDesk\Models\Bug;
use HelpDesk\Models\GeneralGuide;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$employees = Employee::get_all();
$products = Product::get_all();
$bugs = Bug::get_all();
$guides = GeneralGuide::get_all_active();
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'HelpDesk Dashboard', HELPDESK_TEXT_DOMAIN ); ?></h1>

    <div class="helpdesk-dashboard-wrapper">
        <!-- LEFT COLUMN: Project Search -->
        <div class="helpdesk-admin-container dashboard-column">
            <div class="dashboard-column-header">
                <h2><?php echo esc_html__( '🔍 Vyhľadávanie projektov', HELPDESK_TEXT_DOMAIN ); ?></h2>
            </div>
            
            <div class="dashboard-search-box">
                <input 
                    type="text" 
                    id="dashboard-project-search" 
                    class="helpdesk-search-input" 
                    placeholder="<?php echo esc_attr__( 'Zadajte názov projektu...', HELPDESK_TEXT_DOMAIN ); ?>"
                    autocomplete="off"
                >
                <div class="search-info"><?php echo esc_html__( 'Vyhľadávajte projekty podľa názvu', HELPDESK_TEXT_DOMAIN ); ?></div>
            </div>

            <div id="dashboard-search-results" class="dashboard-results-container" style="display: none;">
                <div class="results-count">
                    <span id="dashboard-results-count">0</span> <?php echo esc_html__( 'výsledkov', HELPDESK_TEXT_DOMAIN ); ?>
                </div>
                <div id="dashboard-search-results-tbody" class="projects-grid">
                </div>
            </div>

            <div id="dashboard-no-results" class="no-results-message">
                <p><?php echo esc_html__( 'Začnite písaním do vyhľadávacieho poľa...', HELPDESK_TEXT_DOMAIN ); ?></p>
            </div>
        </div>

        <!-- RIGHT COLUMN: Solutions Search (NEW) -->
        <div class="helpdesk-admin-container dashboard-column">
            <div class="dashboard-column-header">
                <h2><?php echo esc_html__( '💡 Riešenia', HELPDESK_TEXT_DOMAIN ); ?></h2>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Produkt', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="dashboard-solutions-product" class="widefat" style="padding: 8px; border: 1px solid #ddd; border-radius: 3px; font-size: 13px;">
                        <option value=""><?php echo esc_html__( '-- Všetky --', HELPDESK_TEXT_DOMAIN ); ?></option>
                        <?php foreach ( $products as $product ) : ?>
                            <option value="<?php echo esc_attr( $product['id'] ); ?>"><?php echo esc_html( $product['nazov'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Problém', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="dashboard-solutions-problem" class="widefat" style="padding: 8px; border: 1px solid #ddd; border-radius: 3px; font-size: 13px;">
                        <option value=""><?php echo esc_html__( '-- Všetky --', HELPDESK_TEXT_DOMAIN ); ?></option>
                        <?php foreach ( $bugs as $bug ) : ?>
                            <option value="<?php echo esc_attr( $bug->id ); ?>"><?php echo esc_html( $bug->nazov ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 12px; font-weight: 500; margin-bottom: 5px;">
                    <?php echo esc_html__( 'Vyhľadávanie', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <input 
                    type="text" 
                    id="dashboard-solutions-search" 
                    class="helpdesk-search-input" 
                    placeholder="<?php echo esc_attr__( 'Hľadať v názve alebo popise...', HELPDESK_TEXT_DOMAIN ); ?>"
                    autocomplete="off"
                    style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; font-size: 13px;"
                >
            </div>

            <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                <button id="btn-dashboard-search-solutions" class="button button-primary" style="flex: 1; padding: 8px;">
                    <?php echo esc_html__( '🔍 Hľadať', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
                <button id="btn-dashboard-reset-solutions" class="button" style="flex: 0.8; padding: 8px;">
                    <?php echo esc_html__( 'Vynulovať', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
            </div>

            <div id="dashboard-solutions-results" class="dashboard-results-container" style="display: none;">
                <div class="results-count">
                    <span id="dashboard-solutions-count">0</span> <?php echo esc_html__( 'riešení', HELPDESK_TEXT_DOMAIN ); ?>
                </div>
                <div id="dashboard-solutions-list" style="display: grid; grid-template-columns: 1fr; gap: 10px; max-height: 400px; overflow-y: auto;">
                </div>
            </div>

            <div id="dashboard-no-solutions" class="no-results-message">
                <p><?php echo esc_html__( 'Začnite vyhľadávaním...', HELPDESK_TEXT_DOMAIN ); ?></p>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
        <!-- Main Employee Details Card -->
        <div class="card" id="main-employee-card" style="display: none; grid-column: 1 / -1;">
            <h2 style="margin-bottom: 20px;"><?php echo esc_html__( 'Priradiť pracovníkovi', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div style="padding: 15px; background-color: #f9f9f9; border-radius: 3px;">
                    <h4 style="margin: 0 0 8px 0;"><?php echo esc_html__( 'Meno a priezvisko', HELPDESK_TEXT_DOMAIN ); ?></h4>
                    <p id="main-employee-meno" style="font-size: 16px; font-weight: 600; margin: 0; color: #23282d;"></p>
                </div>
                
                <div style="padding: 15px; background-color: #f9f9f9; border-radius: 3px;">
                    <h4 style="margin: 0 0 8px 0;"><?php echo esc_html__( 'Klapka', HELPDESK_TEXT_DOMAIN ); ?></h4>
                    <p id="main-employee-klapka" style="font-size: 16px; font-weight: 600; margin: 0; color: #23282d;"></p>
                </div>
                
                <div style="padding: 15px; background-color: #f9f9f9; border-radius: 3px;">
                    <h4 style="margin: 0 0 8px 0;"><?php echo esc_html__( 'Telefón', HELPDESK_TEXT_DOMAIN ); ?></h4>
                    <p id="main-employee-telefon" style="font-size: 16px; font-weight: 600; margin: 0; color: #23282d;"></p>
                </div>
                
                <div style="padding: 15px; background-color: #f9f9f9; border-radius: 3px;">
                    <h4 style="margin: 0 0 8px 0;"><?php echo esc_html__( 'Pozícia', HELPDESK_TEXT_DOMAIN ); ?></h4>
                    <p id="main-employee-pozicia" style="font-size: 16px; font-weight: 600; margin: 0; color: #23282d;"></p>
                </div>
                
                <div style="padding: 15px; background-color: #f9f9f9; border-radius: 3px; grid-column: 1 / -1;">
                    <h4 style="margin: 0 0 8px 0;"><?php echo esc_html__( 'Poznámka', HELPDESK_TEXT_DOMAIN ); ?></h4>
                    <p id="main-employee-poznamka" style="font-size: 16px; font-weight: 600; margin: 0; color: #23282d;"></p>
                </div>
            </div>
        </div>

        <!-- Standby Employees Card -->
        <div class="card" id="standby-employees-card" style="display: none; grid-column: 1 / -1;">
            <h2 style="margin-bottom: 20px;"><?php echo esc_html__( 'Pracovníci z pohotovosti v CRM', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <table class="wp-list-table widefat fixed striped" id="standby-employees-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__( 'Meno a priezvisko', HELPDESK_TEXT_DOMAIN ); ?></th>
                        <th><?php echo esc_html__( 'Klapka', HELPDESK_TEXT_DOMAIN ); ?></th>
                        <th><?php echo esc_html__( 'Mobil', HELPDESK_TEXT_DOMAIN ); ?></th>
                        <th><?php echo esc_html__( 'Pozícia', HELPDESK_TEXT_DOMAIN ); ?></th>
                        <th><?php echo esc_html__( 'Poznámka', HELPDESK_TEXT_DOMAIN ); ?></th>
                    </tr>
                </thead>
                <tbody id="standby-employees-tbody">
                </tbody>
            </table>
        </div>
    </div>

    </div>
</div>



<style>
.helpdesk-modal {
    position: fixed !important;
    left: 0 !important;
    top: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
    display: none !important;
    align-items: center !important;
    justify-content: center !important;
    z-index: 99999 !important;
}

.helpdesk-modal.active {
    display: flex !important;
}

.helpdesk-modal-content {
    background-color: #fff !important;
    border-radius: 5px !important;
    width: 90% !important;
    max-width: 500px !important;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3) !important;
}

.helpdesk-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
}

.helpdesk-modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #999;
}

.helpdesk-modal-close:hover {
    color: #000;
}

.helpdesk-dashboard {
    margin-top: 20px;
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

.helpdesk-search-section {
    padding: 0;
}

#dashboard-search-results {
    margin-top: 20px;
    border: 1px solid #999 !important;
    border-radius: 4px !important;
    background-color: #fff !important;
    padding: 0 !important;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04) !important;
}

#dashboard-search-results > div {
    border: none !important;
    box-shadow: none !important;
    padding: 15px !important;
}

#dashboard-search-results table th {
    font-weight: 600;
    background-color: #f5f5f5;
}

#dashboard-search-results table td {
    padding: 10px;
    vertical-align: middle;
}

#dashboard-search-results .dashboard-project-select {
    white-space: nowrap;
}

.center {
    text-align: center;
}
</style>

<script>
(function($) {
    'use strict';

    $(document).ready(function() {
        if (typeof helpdesk === 'undefined') {
            return;
        }

        const nonce = helpdesk.nonce;
        const ajaxurl = helpdesk.ajaxurl;

        // Search Solutions button
        $('#btn-dashboard-search-solutions').on('click', function() {
            searchDashboardSolutions();
        });

        // Reset filters button
        $('#btn-dashboard-reset-solutions').on('click', function() {
            $('#dashboard-solutions-product').val('');
            $('#dashboard-solutions-problem').val('');
            $('#dashboard-solutions-search').val('');
            $('#dashboard-solutions-results').hide();
            $('#dashboard-no-solutions').show();
            $('#dashboard-solutions-list').html('');
        });

        // Search on Enter key
        $('#dashboard-solutions-search').on('keypress', function(e) {
            if (e.which === 13) {
                searchDashboardSolutions();
                return false;
            }
        });

        function searchDashboardSolutions() {
            const filters = {
                search: $('#dashboard-solutions-search').val(),
                produkt: $('#dashboard-solutions-product').val(),
                problem_id: $('#dashboard-solutions-problem').val(),
                kategoria: ''
            };

            $('#dashboard-solutions-results').hide();
            $('#dashboard-no-solutions').hide();
            $('#dashboard-solutions-list').html('<div style="text-align: center; padding: 20px;"><p><?php echo esc_html__( "Načítavam...", HELPDESK_TEXT_DOMAIN ); ?></p></div>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'helpdesk_search_guides_by_filters',
                    _wpnonce: nonce,
                    ...filters
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.guides) {
                        const guides = response.data.guides;

                        if (guides.length === 0) {
                            $('#dashboard-no-solutions').show();
                            $('#dashboard-solutions-results').hide();
                        } else {
                            let html = '';
                            guides.forEach(function(guide) {
                                html += renderSolutionCard(guide);
                            });
                            $('#dashboard-solutions-count').text(guides.length);
                            $('#dashboard-solutions-list').html(html);
                            $('#dashboard-solutions-results').show();
                            $('#dashboard-no-solutions').hide();
                        }
                    } else {
                        $('#dashboard-no-solutions').show();
                        $('#dashboard-solutions-results').hide();
                    }
                },
                error: function() {
                    $('#dashboard-no-solutions').show();
                    $('#dashboard-solutions-results').hide();
                }
            });
        }

        function renderSolutionCard(guide) {
            const tagy = guide.tagy ? (typeof guide.tagy === 'string' ? JSON.parse(guide.tagy) : guide.tagy) : [];
            const tagyHtml = tagy.slice(0, 2).map(t => 
                '<span style="display: inline-block; background: #e8f5e9; color: #2e7d32; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-right: 3px;">#' + escapeHtml(t) + '</span>'
            ).join('');

            return `
                <div style="border: 1px solid #ddd; border-radius: 4px; padding: 12px; background: #f9f9f9; cursor: pointer; transition: all 0.3s ease;">
                    <div style="display: flex; justify-content: space-between; align-items: start; gap: 10px;">
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 5px 0; color: #0073aa; font-size: 14px; font-weight: 600;">
                                ${escapeHtml(guide.nazov)}
                            </h4>
                            <p style="margin: 5px 0; color: #666; font-size: 12px;">
                                ${escapeHtml(guide.popis.substring(0, 80))}...
                            </p>
                            ${tagyHtml ? `<div style="margin-top: 5px;">${tagyHtml}</div>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
})(jQuery);
</script>
