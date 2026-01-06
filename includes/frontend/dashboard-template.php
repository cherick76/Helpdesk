<?php
/**
 * HelpDesk Dashboard Template
 * Displays project search and employees assigned to projects
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="helpdesk-dashboard">
    <style>
        .helpdesk-dashboard {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }

        .dashboard-header {
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .dashboard-header p {
            color: #666;
            font-size: 14px;
        }

        /* Search Section */
        .search-section {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .search-section h2 {
            color: #333;
            font-size: 18px;
            margin: 0 0 15px 0;
        }

        .search-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .search-input-group input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .search-input-group input:focus {
            outline: none;
            border-color: #0073aa;
            box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
        }

        .search-button {
            padding: 12px 30px;
            background: #0073aa;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .search-button:hover {
            background: #005a87;
        }

        .search-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #0073aa;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Results Section */
        .results-section {
            display: none;
        }

        .results-section.active {
            display: block;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .results-header h2 {
            color: #333;
            font-size: 20px;
            margin: 0;
        }

        .results-count {
            color: #666;
            font-size: 14px;
        }

        /* Project Cards */
        .projects-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .project-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .project-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .project-header {
            margin-bottom: 15px;
        }

        .project-title {
            color: #0073aa;
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 5px 0;
        }

        .project-info {
            color: #666;
            font-size: 13px;
            margin: 3px 0;
        }

        .project-info strong {
            color: #333;
        }

        /* Employees Section */
        .employees-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .employees-title {
            color: #333;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .employees-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .employee-item {
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
            font-size: 13px;
        }

        .employee-item:last-child {
            border-bottom: none;
        }

        .employee-name {
            color: #333;
            font-weight: 500;
        }

        .employee-position {
            color: #999;
            font-size: 12px;
            margin-left: 5px;
        }

        .employee-contact {
            color: #666;
            font-size: 12px;
            margin-top: 3px;
        }

        .employee-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 3px;
        }

        .employee-status.main {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .employee-status.standby {
            background: #fff3e0;
            color: #e65100;
        }

        .employee-status.vacation {
            background: #f3e5f5;
            color: #6a1b9a;
        }

        /* Error & Empty States */
        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            border-radius: 4px;
            padding: 15px;
            color: #c33;
            margin-bottom: 20px;
            display: none;
        }

        .error-message.active {
            display: block;
        }

        .empty-state {
            background: #f9f9f9;
            border: 1px dashed #ddd;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            color: #666;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .helpdesk-dashboard {
                padding: 10px;
            }

            .search-input-group {
                flex-direction: column;
            }

            .projects-container {
                grid-template-columns: 1fr;
            }

            .dashboard-header h1 {
                font-size: 24px;
            }
        }
    </style>

    <div class="dashboard-header">
        <h1>🔍 Vyhľadávanie Projektov</h1>
        <p>Nájdite projekt a pozrite si priradených pracovníkov</p>
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <h2>Vyhľadať Projekt</h2>
        <div class="search-input-group">
            <input 
                type="text" 
                id="project-search-input" 
                placeholder="Zadajte názov projektu, číslo projektu alebo pracovníka..." 
                autocomplete="off"
            />
            <button class="search-button" id="search-button" onclick="performProjectSearch()">
                Hľadať
            </button>
        </div>
        <div id="error-message" class="error-message"></div>
    </div>

    <!-- Results Section -->
    <div id="results-section" class="results-section">
        <div class="results-header">
            <h2>Výsledky Vyhľadávania</h2>
            <span class="results-count">Nájdené projekty: <strong id="project-count">0</strong></span>
        </div>
        <div id="projects-container" class="projects-container"></div>
    </div>

    <!-- Empty State -->
    <div id="empty-state" class="empty-state" style="display: none;">
        <div class="empty-state-icon">📦</div>
        <p>Zatiaľ neboli nájdené žiadne projekty. Skúste zmeniť kritériá hľadania.</p>
    </div>
</div>

<script>
(function($) {
    'use strict';

    // Get nonce from page
    const nonce = <?php echo json_encode( wp_create_nonce( 'helpdesk-nonce' ) ); ?>;
    const ajaxurl = <?php echo json_encode( admin_ajax_url ); ?>;

    window.performProjectSearch = function() {
        const searchTerm = $('#project-search-input').val().trim();
        
        if (!searchTerm) {
            showError('Prosím zadajte hľadaný termín');
            return;
        }

        const $button = $('#search-button');
        const originalText = $button.text();
        
        // Disable button and show loading
        $button.prop('disabled', true).html('Hľadám... <span class="loading-spinner"></span>');
        hideError();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'helpdesk_search_projects',
                search: searchTerm,
                _wpnonce: nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayProjectResults(response.data.projects || []);
                } else {
                    showError(response.data.message || 'Chyba pri hľadaní');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error, xhr);
                if (xhr.status === 403) {
                    showError('Nemáte oprávnenie na vykonanie tejto akcie.');
                } else {
                    showError('Chyba pri komunikácii so serverom. Skúste neskôr.');
                }
            },
            complete: function() {
                // Re-enable button
                $button.prop('disabled', false).text(originalText);
            }
        });
    };

    window.displayProjectResults = function(projects) {
        const $resultsSection = $('#results-section');
        const $projectsContainer = $('#projects-container');
        const $projectCount = $('#project-count');
        const $emptyState = $('#empty-state');

        if (!projects || projects.length === 0) {
            $resultsSection.removeClass('active');
            $emptyState.show();
            return;
        }

        $emptyState.hide();
        $projectCount.text(projects.length);
        $projectsContainer.empty();

        projects.forEach(function(project) {
            const projectCard = createProjectCard(project);
            $projectsContainer.append(projectCard);
        });

        $resultsSection.addClass('active');
    };

    window.createProjectCard = function(project) {
        const employees = project.employees || [];
        const mainEmployees = employees.filter(emp => emp.emp_type === 'project' || emp.is_hlavny == 1);
        const standbyEmployees = employees.filter(emp => emp.emp_type === 'standby');

        let employeesHTML = '';
        if (employees.length === 0) {
            employeesHTML = '<div style="color: #999; font-size: 13px; padding: 10px 0;">Žiadni pracovníci zatiaľ niet pridelení</div>';
        } else {
            employeesHTML += '<ul class="employees-list">';
            
            // Main employees
            mainEmployees.forEach(function(emp) {
                employeesHTML += '<li class="employee-item">';
                employeesHTML += '<div class="employee-name">' + escapeHtml(emp.meno_priezvisko) + '</div>';
                
                if (emp.pozicia_nazov) {
                    employeesHTML += '<span class="employee-position">(' + escapeHtml(emp.pozicia_nazov) + ')</span>';
                }
                
                if (emp.klapka || emp.mobil) {
                    employeesHTML += '<div class="employee-contact">';
                    if (emp.klapka) {
                        employeesHTML += '📞 ' + escapeHtml(emp.klapka);
                    }
                    if (emp.mobil) {
                        employeesHTML += (emp.klapka ? ' | ' : '') + '📱 ' + escapeHtml(emp.mobil);
                    }
                    employeesHTML += '</div>';
                }
                
                employeesHTML += '<span class="employee-status main">Hlavný pracovník</span>';
                
                if (emp.nepritomnost_od && emp.nepritomnost_do) {
                    employeesHTML += '<span class="employee-status vacation">Dovolenka: ' + emp.nepritomnost_od + ' až ' + emp.nepritomnost_do + '</span>';
                }
                
                employeesHTML += '</li>';
            });

            // Standby employees
            standbyEmployees.forEach(function(emp) {
                employeesHTML += '<li class="employee-item">';
                employeesHTML += '<div class="employee-name">' + escapeHtml(emp.meno_priezvisko) + '</div>';
                
                if (emp.pozicia_nazov) {
                    employeesHTML += '<span class="employee-position">(' + escapeHtml(emp.pozicia_nazov) + ')</span>';
                }
                
                if (emp.klapka || emp.mobil) {
                    employeesHTML += '<div class="employee-contact">';
                    if (emp.klapka) {
                        employeesHTML += '📞 ' + escapeHtml(emp.klapka);
                    }
                    if (emp.mobil) {
                        employeesHTML += (emp.klapka ? ' | ' : '') + '📱 ' + escapeHtml(emp.mobil);
                    }
                    employeesHTML += '</div>';
                }
                
                employeesHTML += '<span class="employee-status standby">Zástupca</span>';
                
                if (emp.nepritomnost_od && emp.nepritomnost_do) {
                    employeesHTML += '<span class="employee-status vacation">Dovolenka: ' + emp.nepritomnost_od + ' až ' + emp.nepritomnost_do + '</span>';
                }
                
                employeesHTML += '</li>';
            });

            employeesHTML += '</ul>';
        }

        let diacriticsWarning = '';
        if (project.diacritics_warnings && project.diacritics_warnings.length > 0) {
            diacriticsWarning = '<div style="background: #fffbea; border-left: 3px solid #ffa500; padding: 10px; margin-top: 10px; font-size: 12px; color: #666;">';
            diacriticsWarning += '⚠️ Pozor: V zozname pracovníkov sú varianty mien s rozdielnym pravopisom (s/bez diacritiky)';
            diacriticsWarning += '</div>';
        }

        const card = `
            <div class="project-card">
                <div class="project-header">
                    <div class="project-title">${escapeHtml(project.nazov || 'Bez názvu')}</div>
                    <div class="project-info">
                        <strong>Číslo:</strong> ${escapeHtml(project.zakaznicke_cislo || 'N/A')}
                    </div>
                    ${project.poznamka ? '<div class="project-info"><strong>Poznámka:</strong> ' + escapeHtml(project.poznamka) + '</div>' : ''}
                </div>
                <div class="employees-section">
                    <div class="employees-title">
                        👥 Pridelení pracovníci (${employees.length})
                    </div>
                    ${employeesHTML}
                    ${diacriticsWarning}
                </div>
            </div>
        `;

        return card;
    };

    window.showError = function(message) {
        const $error = $('#error-message');
        $error.text(message).addClass('active');
        $('html, body').animate({
            scrollTop: $error.offset().top - 100
        }, 300);
    };

    window.hideError = function() {
        $('#error-message').removeClass('active');
    };

    window.escapeHtml = function(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    };

    // Allow Enter key to trigger search
    $('#project-search-input').on('keypress', function(e) {
        if (e.which === 13) {
            performProjectSearch();
            return false;
        }
    });

})(jQuery);
</script>
