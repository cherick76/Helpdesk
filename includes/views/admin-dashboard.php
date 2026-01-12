<?php
/**
 * Dashboard View - Tab Based
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

<div class="wrap" style="max-width: 1200px; margin: 0 auto;">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 30px; border-radius: 8px; margin-bottom: 30px; margin-top: 20px;">
        <h1 style="margin: 0 0 10px 0; font-size: 36px;">🎯 HelpDesk Dashboard</h1>
        <p style="margin: 0; opacity: 0.9; font-size: 16px;">Spravujte projekty, riešenia a tím na jednom mieste</p>
    </div>

    <!-- Tab Navigation -->
    <div style="display: flex; gap: 0; margin-bottom: 0; border-bottom: 2px solid #ddd; background: white; border-radius: 8px 8px 0 0;">
        <button class="dashboard-tab-btn active" data-tab="projects" style="flex: 1; padding: 15px 20px; border: none; background: none; cursor: pointer; font-size: 14px; font-weight: 600; color: #666; transition: all 0.3s; border-bottom: 3px solid transparent; margin-bottom: -2px;">
            📦 Projekty
        </button>
        <button class="dashboard-tab-btn" data-tab="solutions" style="flex: 1; padding: 15px 20px; border: none; background: none; cursor: pointer; font-size: 14px; font-weight: 600; color: #666; transition: all 0.3s; border-bottom: 3px solid transparent; margin-bottom: -2px;">
            💡 Riešenia
        </button>
        <button class="dashboard-tab-btn" data-tab="employees" style="flex: 1; padding: 15px 20px; border: none; background: none; cursor: pointer; font-size: 14px; font-weight: 600; color: #666; transition: all 0.3s; border-bottom: 3px solid transparent; margin-bottom: -2px;">
            👥 Tím
        </button>
    </div>

    <!-- Tab Content Container -->
    <div style="background: white; border-radius: 0 0 8px 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">

        <!-- TAB 1: PROJECTS -->
        <div class="dashboard-tab-content active" id="projects-tab" style="padding: 30px;">
            <h2 style="margin-top: 0; color: #333;">Vyhľadávanie projektov</h2>
            
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input 
                    type="text" 
                    id="dashboard-project-search" 
                    placeholder="Zadajte názov projektu..."
                    autocomplete="off"
                    style="flex: 1; padding: 12px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                >
                <button id="btn-search-projects" class="button button-primary" style="padding: 12px 25px;">
                    🔍 Hľadať
                </button>
            </div>

            <div id="dashboard-search-results" style="display: none;">
                <div class="results-count" style="margin-bottom: 15px; padding: 10px; background: #f0f0f0; border-radius: 4px;">
                    <span id="dashboard-results-count">0</span> projektov
                </div>
                <div id="dashboard-search-results-tbody" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 15px;">
                </div>
            </div>

            <div id="dashboard-no-results" style="text-align: center; padding: 40px; color: #999;">
                <p style="font-size: 16px;">🔍 Začnite písaním do vyhľadávacieho poľa...</p>
            </div>
        </div>

        <!-- TAB 2: SOLUTIONS -->
        <div class="dashboard-tab-content" id="solutions-tab" style="padding: 30px; display: none;">
            <h2 style="margin-top: 0; color: #333;">Vyhľadávanie riešení</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 8px; color: #333;">Produkt</label>
                    <select id="dashboard-solutions-product" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                        <option value="">-- Všetky produkty --</option>
                        <?php foreach ( $products as $product ) : ?>
                            <option value="<?php echo esc_attr( $product['id'] ); ?>"><?php echo esc_html( $product['nazov'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 8px; color: #333;">Problém</label>
                    <select id="dashboard-solutions-problem" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                        <option value="">-- Všetky problémy --</option>
                        <?php foreach ( $bugs as $bug ) : ?>
                            <option value="<?php echo esc_attr( $bug->id ); ?>"><?php echo esc_html( $bug->nazov ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 8px; color: #333;">Vyhľadávanie</label>
                    <input 
                        type="text" 
                        id="dashboard-solutions-search" 
                        placeholder="Hľadať v názve alebo popise..."
                        autocomplete="off"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;"
                    >
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <button id="btn-dashboard-search-solutions" class="button button-primary" style="padding: 10px 20px;">
                    🔍 Hľadať
                </button>
                <button id="btn-dashboard-reset-solutions" class="button" style="padding: 10px 20px;">
                    Vynulovať
                </button>
            </div>

            <div id="dashboard-solutions-results" style="display: none;">
                <div class="results-count" style="margin-bottom: 15px; padding: 10px; background: #f0f0f0; border-radius: 4px;">
                    <span id="dashboard-solutions-count">0</span> riešení
                </div>
                <div id="dashboard-solutions-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; max-height: 600px; overflow-y: auto;">
                </div>
            </div>

            <div id="dashboard-no-solutions" style="text-align: center; padding: 40px; color: #999;">
                <p style="font-size: 16px;">🔍 Začnite vyhľadávaním...</p>
            </div>
        </div>

        <!-- TAB 3: EMPLOYEES -->
        <div class="dashboard-tab-content" id="employees-tab" style="padding: 30px; display: none;">
            <h2 style="margin-top: 0; color: #333;">Tím a pohotovosť</h2>
            
            <!-- Selected Employee Card -->
            <div id="main-employee-card" style="display: none; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <h3 style="margin-top: 0; margin-bottom: 15px;">Vybraný pracovník</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <p style="margin: 0 0 5px 0; opacity: 0.9; font-size: 12px;">Meno</p>
                        <p id="main-employee-meno" style="margin: 0; font-size: 16px; font-weight: 600;"></p>
                    </div>
                    <div>
                        <p style="margin: 0 0 5px 0; opacity: 0.9; font-size: 12px;">Klapka</p>
                        <p id="main-employee-klapka" style="margin: 0; font-size: 16px; font-weight: 600;"></p>
                    </div>
                    <div>
                        <p style="margin: 0 0 5px 0; opacity: 0.9; font-size: 12px;">Telefón</p>
                        <p id="main-employee-telefon" style="margin: 0; font-size: 16px; font-weight: 600;"></p>
                    </div>
                    <div>
                        <p style="margin: 0 0 5px 0; opacity: 0.9; font-size: 12px;">Pozícia</p>
                        <p id="main-employee-pozicia" style="margin: 0; font-size: 16px; font-weight: 600;"></p>
                    </div>
                </div>
            </div>

            <!-- Standby Employees Table -->
            <div id="standby-employees-card" style="display: none;">
                <h3 style="margin-bottom: 15px;">Pracovníci z pohotovosti</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr style="background: #f5f5f5;">
                            <th style="padding: 12px;">Meno a priezvisko</th>
                            <th style="padding: 12px;">Klapka</th>
                            <th style="padding: 12px;">Mobil</th>
                            <th style="padding: 12px;">Pozícia</th>
                            <th style="padding: 12px;">Poznámka</th>
                        </tr>
                    </thead>
                    <tbody id="standby-employees-tbody">
                    </tbody>
                </table>
            </div>

            <div id="no-employees" style="text-align: center; padding: 40px; color: #999;">
                <p style="font-size: 16px;">👥 Zatiaľ žádní pracovníci</p>
            </div>
        </div>

    </div>
</div>

<style>
    .dashboard-tab-btn {
        position: relative;
    }

    .dashboard-tab-btn:hover {
        background: #f5f5f5 !important;
        color: #0073aa !important;
    }

    .dashboard-tab-btn.active {
        color: #0073aa !important;
        border-bottom-color: #0073aa !important;
    }

    .dashboard-tab-content {
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .project-card {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 15px;
        transition: all 0.3s;
    }

    .project-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }

    .project-card h4 {
        margin: 0 0 10px 0;
        color: #0073aa;
        font-size: 15px;
    }

    .project-card p {
        margin: 5px 0;
        font-size: 13px;
        color: #666;
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

        // Tab switching
        $('.dashboard-tab-btn').on('click', function() {
            const tabName = $(this).data('tab');
            
            $('.dashboard-tab-btn').removeClass('active');
            $('.dashboard-tab-content').removeClass('active').hide();
            
            $(this).addClass('active');
            $('#' + tabName + '-tab').addClass('active').fadeIn();
        });

        // Projects search
        $('#btn-search-projects').on('click', function() {
            searchProjects();
        });

        $('#dashboard-project-search').on('keypress', function(e) {
            if (e.which === 13) {
                searchProjects();
            }
        });

        // Solutions search
        $('#btn-dashboard-search-solutions').on('click', function() {
            searchSolutions();
        });

        $('#btn-dashboard-reset-solutions').on('click', function() {
            $('#dashboard-solutions-product').val('');
            $('#dashboard-solutions-problem').val('');
            $('#dashboard-solutions-search').val('');
            $('#dashboard-solutions-results').hide();
            $('#dashboard-no-solutions').show();
        });

        $('#dashboard-solutions-search').on('keypress', function(e) {
            if (e.which === 13) {
                searchSolutions();
            }
        });

        function searchProjects() {
            const searchTerm = $('#dashboard-project-search').val();
            
            if (!searchTerm) {
                $('#dashboard-no-results').show();
                $('#dashboard-search-results').hide();
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'helpdesk_search_projects',
                    _wpnonce: nonce,
                    search: searchTerm
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.projects) {
                        const projects = response.data.projects;
                        let html = '';
                        
                        projects.forEach(function(project) {
                            html += '<div class="project-card">' +
                                '<h4>' + escapeHtml(project.nazov) + '</h4>' +
                                '<p><strong>Číslo:</strong> ' + escapeHtml(project.zakaznicke_cislo) + '</p>' +
                                '<p><strong>Pracovníci:</strong> ' + (project.pocet_pracovnikov || 0) + '</p>' +
                                '</div>';
                        });
                        
                        $('#dashboard-results-count').text(projects.length);
                        $('#dashboard-search-results-tbody').html(html);
                        $('#dashboard-search-results').show();
                        $('#dashboard-no-results').hide();
                    }
                }
            });
        }

        function searchSolutions() {
            const filters = {
                search: $('#dashboard-solutions-search').val(),
                produkt: $('#dashboard-solutions-product').val(),
                problem_id: $('#dashboard-solutions-problem').val(),
                kategoria: ''
            };

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
                                const tagy = guide.tagy ? (typeof guide.tagy === 'string' ? JSON.parse(guide.tagy) : guide.tagy) : [];
                                const tagyHtml = tagy.slice(0, 2).map(t => 
                                    '<span style="display: inline-block; background: #e8f5e9; color: #2e7d32; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-right: 3px;">#' + escapeHtml(t) + '</span>'
                                ).join('');
                                
                                html += '<div style="border: 1px solid #ddd; border-radius: 6px; padding: 15px; background: #f9f9f9;">' +
                                    '<h4 style="margin: 0 0 8px 0; color: #0073aa; font-size: 14px;">' + escapeHtml(guide.nazov) + '</h4>' +
                                    '<p style="margin: 5px 0; font-size: 12px; color: #666;">' + escapeHtml(guide.popis.substring(0, 80)) + '...</p>' +
                                    (tagyHtml ? '<div style="margin-top: 8px;">' + tagyHtml + '</div>' : '') +
                                    '</div>';
                            });
                            
                            $('#dashboard-solutions-count').text(guides.length);
                            $('#dashboard-solutions-list').html(html);
                            $('#dashboard-solutions-results').show();
                            $('#dashboard-no-solutions').hide();
                        }
                    }
                }
            });
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
