<?php
/**
 * HelpDesk Admin Dashboard
 * Displays project search, employee assignment, and solution search
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check capabilities
if ( ! current_user_can( 'manage_helpdesk' ) ) {
    wp_die( 'Nemáte oprávnenie pristúpiť na túto stránku.' );
}
?>

<div class="wrap helpdesk-admin-dashboard">
    <style>
        .helpdesk-admin-dashboard {
            max-width: 1400px;
            margin-top: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }

        .helpdesk-header {
            margin-bottom: 30px;
        }

        .helpdesk-header h1 {
            color: #333;
            font-size: 32px;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .helpdesk-header p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }

        /* Tabs */
        .nav-tab-wrapper {
            border-bottom: 1px solid #ccc;
            margin: 20px 0;
            background: white;
            padding: 0;
            border-radius: 4px 4px 0 0;
        }

        .nav-tab {
            background: #f5f5f5;
            border: 1px solid #ccc;
            border-bottom: none;
            color: #333;
            padding: 10px 20px;
            text-decoration: none;
            margin-right: 0;
            margin-bottom: 0;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
        }

        .nav-tab:hover {
            background: #fff;
            color: #0073aa;
        }

        .nav-tab.nav-tab-active {
            background: white;
            border-bottom: 3px solid #0073aa;
            color: #0073aa;
            padding-bottom: 7px;
        }

        .tab-content {
            display: none;
            background: white;
            padding: 20px;
            border-radius: 0 4px 4px 4px;
            border: 1px solid #ccc;
            border-top: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Search Section */
        .search-section {
            margin-bottom: 30px;
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

        .search-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .search-filters select,
        .search-filters input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
        }

        .search-button {
            padding: 8px 16px;
            background: #0073aa;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 12px;
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
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #0073aa;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Results */
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
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .results-header h3 {
            color: #333;
            font-size: 16px;
            margin: 0;
        }

        .results-count {
            color: #666;
            font-size: 13px;
        }

        /* Project Cards */
        .results-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 15px;
        }

        .result-card {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s;
        }

        .result-card:hover {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .card-header {
            margin-bottom: 12px;
        }

        .card-title {
            color: #0073aa;
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 5px 0;
        }

        .card-meta {
            color: #666;
            font-size: 12px;
            margin: 3px 0;
        }

        .card-meta strong {
            color: #333;
        }

        /* Employees List */
        .employees-container {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e0e0e0;
        }

        .employees-title {
            color: #333;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .employees-list {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 250px;
            overflow-y: auto;
        }

        .employee-item {
            padding: 6px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 12px;
        }

        .employee-item:last-child {
            border-bottom: none;
        }

        .employee-name {
            color: #333;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .employee-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 2px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-main {
            background: #d4edda;
            color: #155724;
        }

        .badge-standby {
            background: #fff3cd;
            color: #856404;
        }

        .employee-details {
            color: #666;
            font-size: 11px;
            margin-top: 2px;
        }

        /* Bug/Solution Cards */
        .bug-card {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-left: 4px solid #dc3545;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 10px;
        }

        .bug-card.solved {
            border-left-color: #28a745;
            background: #f0f8f4;
        }

        .bug-code {
            color: #dc3545;
            font-weight: 600;
            font-family: monospace;
            font-size: 12px;
        }

        .bug-card.solved .bug-code {
            color: #28a745;
        }

        .bug-description {
            color: #333;
            font-size: 13px;
            margin: 5px 0 3px 0;
            font-weight: 500;
        }

        .bug-meta {
            display: flex;
            gap: 15px;
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }

        .bug-meta strong {
            color: #333;
        }

        /* Error Messages */
        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            border-radius: 4px;
            padding: 12px;
            color: #c33;
            margin-bottom: 15px;
            display: none;
        }

        .error-message.active {
            display: block;
        }

        .success-message {
            background: #efe;
            border: 1px solid #cfc;
            border-radius: 4px;
            padding: 12px;
            color: #3c3;
            margin-bottom: 15px;
            display: none;
        }

        .success-message.active {
            display: block;
        }

        /* Empty State */
        .empty-state {
            background: #f9f9f9;
            border: 1px dashed #ddd;
            border-radius: 4px;
            padding: 30px 20px;
            text-align: center;
            color: #666;
        }

        .empty-state-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .results-container {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="helpdesk-header">
        <h1>📊 HelpDesk Dashboard</h1>
        <p>Centrálne riadenie projektov, pracovníkov a riešení</p>
    </div>

    <!-- Tabs Navigation -->
    <div class="nav-tab-wrapper">
        <a href="#" class="nav-tab nav-tab-active" data-tab="projects">
            🔍 Projektový Vyhľadávač
        </a>
        <a href="#" class="nav-tab" data-tab="solutions">
            🔧 Vyhľadávanie Riešení
        </a>
    </div>

    <!-- PROJECTS TAB -->
    <div id="projects" class="tab-content active">
        <div class="search-section">
            <h2>Vyhľadávanie Projektov</h2>
            
            <div class="search-input-group">
                <input 
                    type="text" 
                    id="project-search-input" 
                    placeholder="Zadajte názov projektu, číslo alebo meno pracovníka..." 
                    autocomplete="off"
                />
                <button class="search-button" id="project-search-button">
                    Hľadať Projekty
                </button>
            </div>

            <div id="project-error" class="error-message"></div>
            <div id="project-success" class="success-message"></div>
        </div>

        <div id="project-results" class="results-section">
            <div class="results-header" style="display: none;">
                <h3>Výsledky Vyhľadávania Projektov</h3>
                <span class="results-count">Nájdené: <strong id="project-count">0</strong></span>
            </div>
            <div id="project-results-container" class="results-container"></div>
        </div>

        <div id="project-empty" class="empty-state">
            <div class="empty-state-icon">📦</div>
            <p>Skúste vyhľadať projekt zadaním hľadaného termínu</p>
        </div>
    </div>

    <!-- SOLUTIONS TAB -->
    <div id="solutions" class="tab-content">
        <div class="search-section">
            <h2>Vyhľadávanie Riešení (Aplikačná Podpora)</h2>
            
            <div class="search-input-group">
                <input 
                    type="text" 
                    id="solution-search-input" 
                    placeholder="Zadajte kód riešenia, popis alebo názov produktu..." 
                    autocomplete="off"
                />
                <button class="search-button" id="solution-search-button" onclick="searchSolutions()">
                    Hľadať Riešenia
                </button>
            </div>

            <div class="search-filters">
                <select id="solution-status" onchange="searchSolutions()">
                    <option value="">Všetky stavy</option>
                    <option value="solved">Vyriešené</option>
                    <option value="unsolved">Nevyriešené</option>
                </select>
                <select id="solution-product" onchange="searchSolutions()">
                    <option value="">Všetky produkty</option>
                </select>
            </div>

            <div id="solution-error" class="error-message"></div>
            <div id="solution-success" class="success-message"></div>
        </div>

        <div id="solution-results" class="results-section">
            <div class="results-header">
                <h3>Výsledky Riešení</h3>
                <span class="results-count">Nájdené: <strong id="solution-count">0</strong></span>
            </div>
            <div id="solution-results-container"></div>
        </div>

        <div id="solution-empty" class="empty-state">
            <div class="empty-state-icon">🔧</div>
            <p>Skúste vyhľadať riešenie zadaním kódu, popisu alebo názvu produktu</p>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    const nonce = <?php echo json_encode( wp_create_nonce( 'helpdesk-nonce' ) ); ?>;
    const ajaxurl = <?php echo json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    
    // Dashboard display settings
    const dashboardDisplay = <?php echo json_encode( get_option( 'helpdesk_dashboard_display', array(
        'nazov_projektu' => true,
        'klapka' => true,
        'mobil' => true,
        'pozicia' => true,
        'poznamka_pracovnika' => true,
        'hd_kontakt' => true,
    ) ) ); ?>;

    // Tab switching
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const tabName = this.dataset.tab;
            
            // Update active tab
            document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('nav-tab-active'));
            this.classList.add('nav-tab-active');
            
            // Update active content
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
        });
    });

    // PROJECTS SEARCH
    window.searchProjects = function() {
        console.log('🔍 searchProjects() called');
        const searchTerm = document.getElementById('project-search-input').value.trim();
        console.log('Search term:', searchTerm);
        
        if (!searchTerm) {
            displayProjects([]);
            return;
        }

        const button = document.getElementById('project-search-button');
        const originalText = button.textContent;
        button.disabled = true;
        button.innerHTML = 'Hľadám... <span class="loading-spinner"></span>';

        console.log('📤 Sending fetch to:', ajaxurl);
        console.log('📦 Nonce:', nonce);
        console.log('🔍 Search term:', searchTerm);
        
        const postData = new URLSearchParams({
            action: 'helpdesk_search_projects',
            search: searchTerm,
            _wpnonce: nonce
        });
        console.log('📋 POST data:', postData.toString());

        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: postData
        })
        .then(response => {
            console.log('📥 Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📊 Projects data:', data);
            if (data.success) {
                displayProjects(data.data.projects || []);
            } else {
                showError('project', data.data.message || 'Chyba pri hľadaní');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('project', 'Chyba pri komunikácii so serverom');
        })
        .finally(() => {
            button.disabled = false;
            button.textContent = originalText;
        });
    };

    window.displayProjects = function(projects) {
        const container = document.getElementById('project-results-container');
        const resultsSection = document.getElementById('project-results');
        const emptyState = document.getElementById('project-empty');
        const count = document.getElementById('project-count');

        if (!projects || projects.length === 0) {
            resultsSection.classList.remove('active');
            emptyState.style.display = 'block';
            return;
        }

        emptyState.style.display = 'none';
        count.textContent = projects.length;
        container.innerHTML = '';

        projects.forEach(project => {
            const card = createProjectCard(project);
            container.innerHTML += card;
        });

        resultsSection.classList.add('active');
    };

    window.createProjectCard = function(project) {
        const employees = project.employees || [];
        const standbyEmployees = project.standby_employees || [];
        
        // Pracovníci z excelu (pridelení)
        const excelEmployees = employees.filter(e => e.emp_type === 'project');
        // Pracovníci z pohotovosti
        const standbyOnly = standbyEmployees.filter(e => e.emp_type === 'standby');

        let excelHtml = '';
        let standbyHtml = '';
        
        // Pracovníci z excelu
        excelEmployees.forEach(emp => {
            const isMain = emp.is_hlavny == 1;
            
            // Check vacation - use string comparison for dates in YYYY-MM-DD format
            let isOnVacation = false;
            if (emp.nepritomnost_od && emp.nepritomnost_do) {
                const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD format
                isOnVacation = today >= emp.nepritomnost_od && today <= emp.nepritomnost_do;
            }
            
            const hasStandby = emp.has_standby == 1;
            
            // Get beacon if has standby
            let beaconEmoji = '';
            let beaconTitle = '';
            if (hasStandby && emp.zdroj) {
                const hasMPorAG = emp.zdroj.includes('MP') || emp.zdroj.includes('AG');
                const hasIS = emp.zdroj.includes('IS');
                
                if (hasMPorAG && hasIS) {
                    // Both sources - split red and blue
                    beaconEmoji = '<span style="display:inline-block;width:12px;height:12px;background:linear-gradient(to right, #f44336 50%, #2196F3 50%);border-radius:50%;margin-right:4px;vertical-align:text-bottom;"></span>';
                    beaconTitle = 'Viaceré zdroje (importované + manuálne/automaticky)';
                } else if (hasMPorAG) {
                    beaconEmoji = '🔵 '; // Blue circle for manually added or auto generated
                    beaconTitle = 'Manuálne pridané alebo automaticky generované';
                } else if (hasIS) {
                    beaconEmoji = '🔴 '; // Red circle for imported
                    beaconTitle = 'Importované zo súboru';
                }
            }
            
            let displayStyle = 'font-size: 12px; padding: 6px 0;';
            let nameDisplay = (isMain ? '⭐ ' : '') + escapeHtml(emp.meno_priezvisko);
            let beaconStyle = '';
            
            if (isOnVacation) {
                displayStyle += ' text-decoration: line-through; color: #999;';
                beaconStyle = ' opacity: 0.5;';
                nameDisplay = '🏖️ ' + nameDisplay;
            }
            
            if (isMain && hasStandby) {
                displayStyle += ' font-weight: bold;';
            }
            
            let contactInfo = '';
            if (emp.klapka) contactInfo += '📞 ' + escapeHtml(emp.klapka);
            if (emp.mobil) contactInfo += ' | 📱 ' + escapeHtml(emp.mobil);
            if (emp.pozicia_nazov) contactInfo += ' | ' + escapeHtml(emp.pozicia_nazov);
            
            excelHtml += `
                <div style="${displayStyle}">
                    <div title="${beaconTitle}" style="${beaconStyle}">${beaconEmoji}${nameDisplay}</div>
                    ${contactInfo ? '<div style="font-size: 11px; color: #666;">' + contactInfo + '</div>' : ''}
                </div>
            `;
        });
        
        // Pracovníci z pohotovosti
        standbyOnly.forEach(emp => {
            // Check vacation - use string comparison for dates in YYYY-MM-DD format
            let isOnVacation = false;
            if (emp.nepritomnost_od && emp.nepritomnost_do) {
                const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD format
                isOnVacation = today >= emp.nepritomnost_od && today <= emp.nepritomnost_do;
            }
            
            // Determine beacon icon based on source (zdroj)
            let beaconEmoji = '🔴'; // Default red
            let beaconTitle = '';
            if (emp.zdroj) {
                const hasMPorAG = emp.zdroj.includes('MP') || emp.zdroj.includes('AG');
                const hasIS = emp.zdroj.includes('IS');
                
                if (hasMPorAG && hasIS) {
                    // Both sources - split red and blue
                    beaconEmoji = '<span style="display:inline-block;width:12px;height:12px;background:linear-gradient(to right, #f44336 50%, #2196F3 50%);border-radius:50%;margin-right:4px;vertical-align:text-bottom;"></span>';
                    beaconTitle = 'Viaceré zdroje (importované + manuálne/automaticky)';
                } else if (hasMPorAG) {
                    beaconEmoji = '🔵'; // Blue circle for manually added or auto generated
                    beaconTitle = 'Manuálne pridané alebo automaticky generované';
                } else if (hasIS) {
                    beaconEmoji = '🔴'; // Red circle for imported
                    beaconTitle = 'Importované zo súboru';
                }
            }
            
            let displayStyle = 'font-size: 12px; padding: 6px 0;';
            let nameDisplay = escapeHtml(emp.meno_priezvisko);
            let beaconStyle = '';
            
            if (isOnVacation) {
                displayStyle += ' text-decoration: line-through; color: #999;';
                beaconStyle = ' opacity: 0.5;';
                nameDisplay = '🏖️ ' + nameDisplay;
            }
            
            let contactInfo = '';
            if (emp.klapka) contactInfo += '📞 ' + escapeHtml(emp.klapka);
            if (emp.mobil) contactInfo += ' | 📱 ' + escapeHtml(emp.mobil);
            if (emp.pozicia_nazov) contactInfo += ' | ' + escapeHtml(emp.pozicia_nazov);
            
            standbyHtml += `
                <div style="${displayStyle}">
                    <div title="${beaconTitle}" style="${beaconStyle}">
                        ${typeof beaconEmoji === 'string' && beaconEmoji.startsWith('<span') ? beaconEmoji : escapeHtml(beaconEmoji)} ${nameDisplay}
                    </div>
                    ${contactInfo ? '<div style="font-size: 11px; color: #666;">' + contactInfo + '</div>' : ''}
                </div>
            `;
        });

        const excelSection = excelHtml ? `
            <div style="flex: 1; padding-right: 10px;">
                <div style="font-weight: 600; margin-bottom: 8px; font-size: 12px;">Z Excelu</div>
                ${excelHtml}
            </div>
        ` : '';
        
        const standbySection = standbyHtml ? `
            <div style="flex: 1;">
                <div style="font-weight: 600; margin-bottom: 8px; font-size: 12px;">Pohotovosť</div>
                ${standbyHtml}
            </div>
        ` : '';

        return `
            <div class="result-card">
                <div class="card-header">
                    ${dashboardDisplay.nazov_projektu ? '<h4 class="card-title">' + escapeHtml(project.zakaznicke_cislo || 'N/A') + ' - ' + escapeHtml(project.nazov || 'Bez názvu') + '</h4>' : '<h4 class="card-title">' + escapeHtml(project.zakaznicke_cislo || 'N/A') + '</h4>'}
                    ${project.hd_kontakt ? '<div class="card-meta"><strong>📞 HD Kontakt:</strong> ' + escapeHtml(project.hd_kontakt) + '</div>' : ''}
                    ${project.poznamka ? '<div class="card-meta"><strong>📝 Poznámka:</strong> ' + escapeHtml(project.poznamka) + '</div>' : ''}
                </div>
                <div class="employees-container" style="display: flex; gap: 20px;">
                    ${excelSection}
                    ${standbySection}
                </div>
            </div>
        `;
    };

    // SOLUTIONS SEARCH
    window.searchSolutions = function() {
        const searchTerm = document.getElementById('solution-search-input').value.trim();
        const status = document.getElementById('solution-status').value;
        
        if (!searchTerm && !status) {
            showError('solution', 'Prosím zadajte hľadaný termín alebo filter');
            return;
        }

        const button = document.getElementById('solution-search-button');
        const originalText = button.textContent;
        button.disabled = true;
        button.innerHTML = 'Hľadám... <span class="loading-spinner"></span>';

        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'helpdesk_search_solutions',
                search: searchTerm,
                status: status,
                _wpnonce: nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySolutions(data.data.solutions || []);
            } else {
                showError('solution', data.data.message || 'Chyba pri hľadaní');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('solution', 'Chyba pri komunikácii so serverom');
        })
        .finally(() => {
            button.disabled = false;
            button.textContent = originalText;
        });
    };

    window.displaySolutions = function(solutions) {
        const container = document.getElementById('solution-results-container');
        const resultsSection = document.getElementById('solution-results');
        const emptyState = document.getElementById('solution-empty');
        const count = document.getElementById('solution-count');

        if (!solutions || solutions.length === 0) {
            resultsSection.classList.remove('active');
            emptyState.style.display = 'block';
            return;
        }

        emptyState.style.display = 'none';
        count.textContent = solutions.length;
        container.innerHTML = '';

        solutions.forEach(solution => {
            const isSolved = solution.je_vyriesen == 1;
            const bugCode = escapeHtml(solution.kod_riesenia || 'N/A');
            const description = escapeHtml(solution.text_riesenia || solution.poznamka || 'Bez popisu');
            const product = escapeHtml(solution.produkt || 'N/A');
            
            const card = `
                <div class="bug-card ${isSolved ? 'solved' : ''}">
                    <div class="bug-code">${bugCode}</div>
                    <div class="bug-description">${description}</div>
                    <div class="bug-meta">
                        <span>📦 <strong>${product}</strong></span>
                        ${isSolved ? '<span style="color: #28a745;">✅ Vyriešené</span>' : '<span style="color: #dc3545;">⏳ Nevyriešené</span>'}
                    </div>
                </div>
            `;
            container.innerHTML += card;
        });

        resultsSection.classList.add('active');
    };

    // Utility functions
    window.showError = function(type, message) {
        const errorEl = document.getElementById(type + '-error');
        errorEl.textContent = message;
        errorEl.classList.add('active');
        
        setTimeout(() => {
            errorEl.classList.remove('active');
        }, 5000);
    };

    window.escapeHtml = function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    // Allow Enter key in search inputs
    document.getElementById('project-search-input').addEventListener('keyup', function(e) {
        // Dynamické vyhľadávanie pri každom keystroke (minimálne 2 znaky)
        const searchTerm = this.value.trim();
        if (searchTerm.length >= 2) {
            searchProjects();
        } else if (searchTerm.length === 0) {
            // Vymaz výsledky keď je pole prázdne
            displayProjects([]);
        }
    });
    
    document.getElementById('project-search-button').addEventListener('click', function(e) {
        e.preventDefault();
        searchProjects();
    });

    document.getElementById('solution-search-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') searchSolutions();
    });
})();
</script>
