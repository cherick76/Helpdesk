/**
 * HelpDesk Admin JavaScript
 * Last updated: 2024-12-18 12:00:00
 */

console.log('admin.js file loaded - v1.0.1');

(function($) {
    'use strict';

    console.log('IIFE started, waiting for document.ready');

    // Global variables accessible to all functions
    let nonce;
    let ajaxurl;
    let dashboardDisplay = {};
    let dashboardFilters = {};
    let employeeSelectedProjects = [];
    let projectState = {
        checkedEmployees: [],
        mainEmployee: null
    };

    // Wait for document ready
    $(document).ready(function() {
        if (typeof helpdesk === 'undefined') {
            console.error('HelpDesk object not found!');
            return;
        }

        nonce = helpdesk.nonce;
        ajaxurl = helpdesk.ajaxurl;
        dashboardDisplay = helpdesk.dashboardDisplay || {
            klapka: true,
            mobil: true,
            pozicia: true,
            poznamka_pracovnika: true,
            hd_kontakt: true,
        };
        dashboardFilters = helpdesk.dashboardFilters || {
            show_nw_projects: false,
        };

        console.log('HelpDesk initialized with nonce:', nonce, 'ajaxurl:', ajaxurl);
        console.log('Dashboard Filters:', dashboardFilters);
        console.log('show_nw_projects value:', dashboardFilters.show_nw_projects);
        console.log('show_nw_projects type:', typeof dashboardFilters.show_nw_projects);
        
        // ===== EMPLOYEES =====
        initEmployees();

        // ===== CONTACTS =====
        initContacts();

        // ===== STANDBY =====
        initStandby();

        // ===== COMMUNICATION METHODS =====
        initCommunicationMethods();

        // ===== VACATIONS =====
        initVacations();

        // ===== PROJECTS =====
        initProjects();

        // ===== SIGNATURES =====
        initSignatures();

        // ===== GENERAL GUIDES =====
        initGeneralGuides();

        // ===== GUIDE CATEGORIES =====
        initGuideCategories();

        // ===== GUIDE LINKS =====
        initGuideLinks();
        
        function renderProjectEmployeesList(employeesList) {
            let employees = employeesList || [];
            
            // If not provided, try to get from JSON
            if (employeesList === undefined) {
                const employeesJson = $('#project-employees-json').val();
                console.log('renderProjectEmployeesList called without employees list');
                console.log('employeesJson raw value:', employeesJson);
                
                if (employeesJson) {
                    try {
                        employees = JSON.parse(employeesJson);
                        console.log('Parsed employees from JSON:', employees);
                    } catch(e) {
                        console.error('Error parsing employees JSON:', e);
                    }
                } else {
                    console.log('employeesJson is empty!');
                }
            } else {
                console.log('renderProjectEmployeesList called with', employees.length, 'employees');
            }
            
            // Get existing HTML to check for changes
            const $list = $('#project-employees-list');
            const existingIds = [];
            $list.find('.employee-item').each(function() {
                existingIds.push(parseInt($(this).data('emp-id')));
            });
            
            // If all employees already exist, only add new ones
            let allExist = true;
            const newIds = employees.map(emp => emp.id);
            
            for (let id of newIds) {
                if (!existingIds.includes(id)) {
                    allExist = false;
                    break;
                }
            }
            
            // If adding new employees (not just re-rendering), add them to existing list
            if (!allExist && existingIds.length > 0) {
                console.log('Adding new employees to existing list');
                employees.forEach(emp => {
                    const empId = emp.id;
                    // Check if this employee already exists in the list
                    if (!existingIds.includes(empId)) {
                        // Display position if available
                        let displayText = emp.meno_priezvisko + ' (' + emp.klapka + ')';
                        if (emp.profesia) {
                            displayText += ' - ' + emp.profesia;
                        }
                        if (emp.priorita) {
                            displayText += ' [' + emp.priorita + ']';
                        }
                        // Add new employee
                        let html = '<div class="employee-item" data-emp-id="' + empId + '" style="display:none;">';
                        html += '<div class="employee-checkbox" data-emp-name="' + emp.meno_priezvisko.toLowerCase() + '" data-emp-id="' + empId + '">';
                        html += '<input type="checkbox" name="employees[]" value="' + empId + '" id="emp-' + empId + '" class="emp-checkbox">';
                        html += '<label for="emp-' + empId + '">' + displayText + '</label>';
                        html += '<input type="radio" name="main_employee" value="' + empId + '" id="main-' + empId + '">';
                        html += '<label for="main-' + empId + '" style="margin-left: 20px;">Hlavný</label>';
                        html += '</div>';
                        html += '</div>';
                        $list.append(html);
                    }
                });
                return;
            }
            
            // Full re-render (initial load or complete change)
            let html = '';
            employees.forEach(emp => {
                const empId = emp.id;
                const isChecked = projectState.checkedEmployees.includes(empId);
                const isMain = projectState.mainEmployee === empId;
                
                // If projectState is empty (initial load), show all employees
                const shouldShow = projectState.checkedEmployees.length === 0 || isChecked;
                
                // Display position if available
                let displayText = emp.meno_priezvisko + ' (' + emp.klapka + ')';
                if (emp.profesia) {
                    displayText += ' - ' + emp.profesia;
                }
                if (emp.priorita) {
                    displayText += ' [' + emp.priorita + ']';
                }
                
                html += '<div class="employee-item" data-emp-id="' + empId + '" ' + (shouldShow ? '' : 'style="display:none;"') + '>';
                html += '<div class="employee-checkbox" data-emp-name="' + emp.meno_priezvisko.toLowerCase() + '" data-emp-id="' + empId + '">';
                html += '<input type="checkbox" name="employees[]" value="' + empId + '" id="emp-' + empId + '" class="emp-checkbox" ' + (isChecked ? 'checked' : '') + '>';
                html += '<label for="emp-' + empId + '">' + displayText + '</label>';
                html += '<select class="employee-status-select" data-emp-id="' + empId + '" style="margin-left: 20px;">';
                html += '<option value="0">◯ Člen</option>';
                html += '<option value="1" ' + (isMain ? 'selected' : '') + '>⭐ Hlavný</option>';
                html += '</select>';
                html += '</div>';
                html += '</div>';
            });
            $list.html(html);
        }
        
        function hideUncheckedEmployees() {
            $('#project-employees-list .employee-item').each(function() {
                const checkbox = $(this).find('.emp-checkbox');
                if (!checkbox.is(':checked')) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        }

        // ===== BUGS =====
        initBugs();

        // ===== DASHBOARD PROJECT SEARCH =====
        console.log('=== Dashboard Search Init Start ===');
        console.log('AJAX Config - ajaxurl:', ajaxurl, 'nonce:', nonce);
        
        var $searchInput = $('#dashboard-project-search');
        var $searchResults = $('#dashboard-search-results');
        var $searchResultsTbody = $('#dashboard-search-results-tbody');
        var $noResults = $('#dashboard-no-results');
        var $resultsCount = $('#dashboard-results-count');
        
        console.log('Search input found:', $searchInput.length > 0);
        console.log('Search results div found:', $searchResults.length > 0);
        
        if ($searchInput.length > 0) {
            console.log('✓ Dashboard project search initialized');
            
            $searchInput.on('keyup', function(e) {
                const val = $(this).val().trim();
                console.log('⌨️ Keyup event - value:', val, 'length:', val.length);
                
                if (val.length < 1) {
                    $searchResults.hide();
                    $noResults.show();
                    return;
                }
                
                if (val.length === 1) {
                    $searchResults.hide();
                    $noResults.show();
                    return;
                }
                
                if (val.length >= 2) {
                    console.log('🔍 Sending AJAX search for:', val);
                    console.log('AJAX data:', {
                        action: 'helpdesk_search_projects',
                        _wpnonce: nonce,
                        search: val
                    });
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'helpdesk_search_projects',
                            _wpnonce: nonce,
                            search: val
                        },
                        dataType: 'json',
                        success: function(response) {
                            console.log('✓ AJAX success:', response);
                            
                            if (response.success && response.data && response.data.projects) {
                                console.log('Projects array:', response.data.projects);
                                console.log('Projects count:', response.data.projects.length);
                                
                                if (response.data.projects.length > 0) {
                                    console.log('Rendering projects...');
                                    let html = '';
                                    
                                    // Projects are already filtered on server side
                                    let filteredProjects = response.data.projects;
                                    
                                    filteredProjects.forEach(function(project, idx) {
                                        console.log('Rendering project ' + idx + ':', project);
                                        
                                        // Oddeliť pracovníkov na priamych a pohotovostných
                                        let directEmployees = [];
                                        let standbyEmployees = [];
                                        
                                        if (project.employees && project.employees.length > 0) {
                                            project.employees.forEach(function(emp) {
                                                if (emp.emp_type === 'standby') {
                                                    standbyEmployees.push(emp);
                                                } else {
                                                    directEmployees.push(emp);
                                                }
                                            });
                                        }
                                        
                                        // Projekt card s pracovníkmi
                                        html += '<div class="project-item" data-project-id="' + project.id + '">';
                                        html += '<div class="project-name">' + (project.zakaznicke_cislo || '') + ' - ' + (project.nazov || '') + '</div>';
                                        
                                        // HD Kontakt - Spôsob komunikácie
                                        if (project.hd_kontakt) {
                                            html += '<div style="font-size: 11px; color: #666; margin-top: 4px; font-style: italic;">📞 HD Kontakt: ' + (project.hd_kontakt || '') + '</div>';
                                        }
                                        
                                        // PRIAMI PRACOVNÍCI
                                        if (directEmployees.length > 0) {
                                            html += '<div class="project-employees" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">';
                                            html += '<div style="font-size: 12px; font-weight: bold; color: #333; margin-bottom: 5px;">👥 Priradení pracovníci (' + directEmployees.length + '):</div>';
                                            
                                            directEmployees.forEach(function(emp) {
                                                const today = new Date().toISOString().split('T')[0];
                                                
                                                // Kontrola dovolenky
                                                const isOnVacation = emp.nepritomnost_od && emp.nepritomnost_do && 
                                                    today >= emp.nepritomnost_od && today <= emp.nepritomnost_do;
                                                
                                                // Kontrola pohotovosti
                                                const hasStandbyToday = emp.has_standby === 1 || emp.has_standby === true || emp.has_standby === '1';
                                                
                                                let empDisplay = emp.meno_priezvisko || 'N/A';
                                                if (emp.klapka) {
                                                    empDisplay += ' (' + emp.klapka + ')';
                                                }
                                                if (emp.mobil) {
                                                    empDisplay += ' | ' + emp.mobil;
                                                }
                                                
                                                // Pridaj ikonky
                                                let prefix = '';
                                                if (hasStandbyToday) {
                                                    prefix += '\u{1F6A8} ';
                                                }
                                                if (parseInt(emp.is_hlavny) === 1) {
                                                    prefix += '⭐ ';
                                                }
                                                
                                                // Prečiarknutie ak je na dovolenke
                                                let displayStyle = 'font-size: 12px; padding: 3px 0; color: #333;';
                                                if (isOnVacation) {
                                                    displayStyle += ' text-decoration: line-through; color: #999;';
                                                    empDisplay = prefix + '🏖️ ' + empDisplay;
                                                } else {
                                                    empDisplay = prefix + empDisplay;
                                                }
                                                
                                                html += '<div style="' + displayStyle + '">' + empDisplay + '</div>';
                                            });
                                            
                                            html += '</div>';
                                        }
                                        
                                        // POHOTOVOSTNÍ PRACOVNÍCI
                                        if (standbyEmployees.length > 0) {
                                            html += '<div class="project-standby" style="margin-top: 10px; padding: 8px; background-color: #fff3cd; border-left: 3px solid #ff9800; border-radius: 3px;">';
                                            html += '<div style="font-size: 12px; font-weight: bold; color: #ff6f00; margin-bottom: 5px;">🚨 Pohotovosť (' + standbyEmployees.length + '):</div>';
                                            
                                            standbyEmployees.forEach(function(emp) {
                                                const today = new Date().toISOString().split('T')[0];
                                                
                                                // Kontrola dovolenky
                                                const isOnVacation = emp.nepritomnost_od && emp.nepritomnost_do && 
                                                    today >= emp.nepritomnost_od && today <= emp.nepritomnost_do;
                                                
                                                let empDisplay = emp.meno_priezvisko || 'N/A';
                                                if (emp.klapka) {
                                                    empDisplay += ' (' + emp.klapka + ')';
                                                }
                                                if (emp.mobil) {
                                                    empDisplay += ' | ' + emp.mobil;
                                                }
                                                
                                                // Prečiarknutie ak je na dovolenke
                                                let spanStyle = '';
                                                if (isOnVacation) {
                                                    spanStyle = ' style="text-decoration: line-through; color: #999;"';
                                                    empDisplay = prefix + '🏖️ ' + empDisplay;
                                                } else {
                                                    empDisplay = prefix + empDisplay;
                                                }
                                                
                                                html += '<div style="font-size: 12px; padding: 3px 0; color: #333; display: flex; justify-content: space-between; align-items: center;">';
                                                html += '<span' + spanStyle + '>' + empDisplay + '</span>';
                                                html += '<button class="button button-small helpdesk-add-standby-btn" data-project-id="' + project.id + '" data-employee-id="' + emp.id + '" style="height: 24px; padding: 0 8px; font-size: 11px;">Pridať</button>';
                                                html += '</div>';
                                            });
                                            
                                            html += '</div>';
                                        }
                                        
                                        if (directEmployees.length === 0 && standbyEmployees.length === 0) {
                                            html += '<div style="font-size: 12px; color: #999; margin-top: 5px;">Žiadni pracovníci</div>';
                                        }
                                        
                                        html += '</div>';
                                    });
                                    
                                    console.log('HTML ready:', html);
                                    
                                    // Check if we have filtered projects
                                    if (filteredProjects.length === 0 && response.data.projects.length > 0) {
                                        // All projects were filtered out
                                        $searchResultsTbody.html('<div style="padding: 20px; text-align: center; color: #999;">Všetky nájdené projekty boli filtrované (nastavenia - projekty s "-nw").</div>');
                                        $searchResults.hide();
                                        $noResults.show();
                                    } else if (filteredProjects.length === 0) {
                                        $noResults.show();
                                        $searchResults.hide();
                                    } else {
                                        $searchResultsTbody.html(html);
                                        $resultsCount.text(filteredProjects.length);
                                        $searchResults.show();
                                        $noResults.hide();
                                    }
                                    console.log('✓ Results displayed');
                                } else {
                                    console.log('No projects found');
                                    $noResults.show();
                                    $searchResults.hide();
                                }
                            } else {
                                console.log('❌ Response error - no success or no projects');
                                console.log('Response data:', response.data);
                                $noResults.html('<p style="color: #d32f2f;">Chyba pri vyhľadávaní. Response: ' + JSON.stringify(response.data) + '</p>');
                                $noResults.show();
                                $searchResults.hide();
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('❌ AJAX error:', error, 'Status:', status);
                            console.error('Response text:', xhr.responseText);
                            $noResults.html('<p style="color: #d32f2f;">AJAX chyba: ' + error + '</p>');
                            $noResults.show();
                            $searchResults.hide();
                        }
                    });
                }
            });
            
            // Event handler na synchronizáciu mien
            $(document).on('click', '.sync-name-variant', function(e) {
                e.preventDefault();
                const projectId = $(this).data('project-id');
                const correctName = $(this).data('correct-name');
                
                console.log('Synchronizácia mien - projekt: ' + projectId + ', meno: ' + correctName);
                
                if (!confirm('Synchronizovať všetkých pracovníkov s rovnakým menom bez diacritiky na: ' + correctName + '?')) {
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'helpdesk_sync_employee_names',
                        _nonce: nonce,
                        project_id: projectId,
                        correct_name: correctName
                    },
                    success: function(response) {
                        console.log('Synchronizácia odpoveď:', response);
                        if (response.success) {
                            alert('✅ Mená boli synchronizované: ' + response.data.updated_count + ' záznamov aktualizovaných.');
                            // Refresh search results
                            $searchInput.val($searchInput.val()).keyup();
                        } else {
                            alert('❌ Chyba: ' + (response.data.message || 'Neznáma chyba'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Synchronizácia chyba:', error);
                        alert('❌ AJAX chyba: ' + error);
                    }
                });
            });
            
            // Event handler na pridanie pracovníka z pohotovosti na projekt
            $(document).on('click', '.helpdesk-add-standby-btn', function(e) {
                e.preventDefault();
                const projectId = $(this).data('project-id');
                const employeeId = $(this).data('employee-id');
                const btn = $(this);
                
                console.log('Pridávanie pracovníka z pohotovosti: projekt=' + projectId + ', pracovník=' + employeeId);
                
                btn.prop('disabled', true);
                btn.text('Pridávam...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'helpdesk_add_employee_to_project',
                        _wpnonce: nonce,
                        project_id: projectId,
                        employee_id: employeeId,
                        is_main: false,
                        from_standby: true  // Dodatočný parameter - vzať z pohotovosti
                    },
                    success: function(response) {
                        console.log('Pridávanie odpoveď:', response);
                        if (response.success) {
                            console.log('✅ Pracovník bol pridaný');
                            alert('✅ Pracovník bol pridaný na projekt');
                            // Refresh search results
                            $searchInput.val($searchInput.val()).keyup();
                        } else {
                            console.error('❌ Chyba:', response.data.message);
                            alert('❌ Chyba: ' + (response.data.message || 'Neznáma chyba'));
                            btn.prop('disabled', false);
                            btn.text('Pridať');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Pridávanie chyba:', error);
                        alert('❌ AJAX chyba: ' + error);
                        btn.prop('disabled', false);
                        btn.text('Pridať');
                    }
                });
            });
            
            // Event handler na otvorenie dialógu synchronizácie mien
            $(document).on('click', '.open-sync-names-dialog', function(e) {
                e.preventDefault();
                const projectId = $(this).data('project-id');
                const employeeType = $(this).data('employees'); // 'excel' alebo 'standby'
                
                console.log('Otvorenie dialógu - projekt: ' + projectId + ', typ: ' + employeeType);
                
                // Zober zamestnancov z aktuálneho projektu
                const projectEl = $(this).closest('[style*="padding: 15px"]');
                const allNames = [];
                
                projectEl.find('[style*="font-size: 12px"]').eq(employeeType === 'excel' ? 0 : 1).find('div').each(function() {
                    const text = $(this).text();
                    if (text && text.trim()) {
                        allNames.push(text);
                    }
                });
                
                if (allNames.length === 0) {
                    alert('Žiadni pracovníci na synchronizáciu');
                    return;
                }
                
                const uniqueNames = [...new Set(allNames)];
                
                let dialogHtml = '<div style="padding: 15px;">';
                dialogHtml += '<p style="margin-bottom: 15px;">Vyber meno, na ktoré chceš synchronizovať všetkých zamestnancov s rovnakým menom bez diacritiky:</p>';
                dialogHtml += '<div style="display: flex; flex-direction: column; gap: 10px;">';
                
                uniqueNames.forEach(name => {
                    dialogHtml += '<button type="button" class="sync-specific-name" data-project-id="' + projectId + '" data-name="' + name + '" style="padding: 10px; background-color: #0073aa; color: white; border: none; border-radius: 3px; cursor: pointer; font-weight: bold;" onmouseover="this.style.backgroundColor=\'#005a87\';" onmouseout="this.style.backgroundColor=\'#0073aa\';">' + name + '</button>';
                });
                
                dialogHtml += '</div></div>';
                
                // Vytvor jQuery dialog
                var $dialog = $('<div id="sync-names-dialog"></div>').html(dialogHtml);
                $dialog.dialog({
                    title: 'Synchronizácia mien',
                    modal: true,
                    width: 400,
                    buttons: {
                        'Zrušiť': function() {
                            $(this).dialog('close');
                            $(this).remove();
                        }
                    }
                });
            });
            
            // Event handler na klik synchronizácie konkrétneho mena
            $(document).on('click', '.sync-specific-name', function(e) {
                e.preventDefault();
                const projectId = $(this).data('project-id');
                const correctName = $(this).data('name');
                
                console.log('Synchronizácia na meno: ' + correctName);
                
                if (!confirm('Synchronizovať všetkých pracovníkov s rovnakým menom bez diacritiky na: ' + correctName + '?')) {
                    return;
                }
                
                $('#sync-names-dialog').dialog('close').remove();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'helpdesk_sync_employee_names',
                        _nonce: nonce,
                        project_id: projectId,
                        correct_name: correctName
                    },
                    success: function(response) {
                        console.log('Synchronizácia odpoveď:', response);
                        if (response.success) {
                            alert('✅ Mená boli synchronizované: ' + response.data.updated_count + ' záznamov aktualizovaných.');
                            // Refresh search results
                            $searchInput.val($searchInput.val()).keyup();
                        } else {
                            alert('❌ Chyba: ' + (response.data.message || 'Neznáma chyba'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Synchronizácia chyba:', error);
                        alert('❌ AJAX chyba: ' + error);
                    }
                });
            });
        } else {
            console.log('ERROR: Could not find project search input!');
        }
        
        console.log('=== Dashboard Bug Search Init Start ===');
        console.log('AJAX Config - ajaxurl:', ajaxurl, 'nonce:', nonce);
        
        // Dashboard Bug Search
        var $dashboardBugSearchInput = $('#dashboard-bug-search');
        var $dashboardBugSearchResults = $('#dashboard-bug-search-results');
        var $dashboardBugSearchResultsTbody = $('#dashboard-bug-search-results-tbody');
        var $dashboardNoBugs = $('#dashboard-no-bugs');
        var $dashboardBugCount = $('#dashboard-bug-results-count');
        
        console.log('✓ Bug search input found:', $dashboardBugSearchInput.length > 0);
        console.log('✓ Bug search results found:', $dashboardBugSearchResults.length > 0);
        
        if ($dashboardBugSearchInput.length > 0) {
            console.log('✓ Dashboard bug search initialized');
            
            $dashboardBugSearchInput.on('keyup', function(e) {
                const val = $(this).val().trim();
                console.log('⌨️ Bug keyup event - value:', val, 'length:', val.length);
                
                if (val.length < 1) {
                    $dashboardBugSearchResults.hide();
                    $dashboardNoBugs.show();
                    return;
                }
                
                if (val.length === 1) {
                    $dashboardBugSearchResults.hide();
                    $dashboardNoBugs.show();
                    return;
                }
                
                if (val.length >= 2) {
                    console.log('🔍 Sending bug AJAX search for:', val);
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'helpdesk_search_bugs',
                            _wpnonce: nonce,
                            search: val
                        },
                        dataType: 'json',
                        success: function(response) {
                            console.log('✓ Bug AJAX success:', response);
                            
                            if (response.success && response.data && response.data.bugs) {
                                console.log('Dashboard bugs array:', response.data.bugs);
                                
                                if (response.data.bugs.length > 0) {
                                    console.log('Rendering dashboard bugs...');
                                    let html = '';
                                    response.data.bugs.forEach(function(bug, idx) {
                                        html += '<tr data-bug-id="' + bug.id + '">';
                                        html += '<td>' + (bug.nazov || '') + '</td>';
                                        html += '<td>' + (bug.product_name || '') + '</td>';
                                        
                                        // Render tags
                                        html += '<td>';
                                        if (bug.tagy) {
                                            try {
                                                const tagy = JSON.parse(bug.tagy);
                                                if (Array.isArray(tagy) && tagy.length > 0) {
                                                    html += '<div style="display: flex; flex-wrap: wrap; gap: 4px;">';
                                                    tagy.forEach(tag => {
                                                        html += '<span style="background-color: #e7f3ff; color: #0073aa; padding: 2px 6px; border-radius: 3px; font-size: 12px; white-space: nowrap;">' + tag + '</span>';
                                                    });
                                                    html += '</div>';
                                                }
                                            } catch(e) {
                                                // Ignore parsing errors
                                            }
                                        }
                                        html += '</td>';
                                        
                                        html += '<td>';
                                        html += '<button class="button button-small dashboard-bug-detail" data-bug-id="' + bug.id + '" style="margin-right: 5px;">📋 Detail</button>';
                                        html += '</td>';
                                        html += '</tr>';
                                    });
                                    console.log('✓ Bug HTML ready, rendering ' + response.data.bugs.length + ' results');
                                    $dashboardBugSearchResultsTbody.html(html);
                                    $dashboardBugCount.text(response.data.bugs.length);
                                    $dashboardBugSearchResults.show();
                                    $dashboardNoBugs.hide();
                                } else {
                                    console.log('No bugs found');
                                    $dashboardBugSearchResults.hide();
                                    $dashboardNoBugs.show();
                                }
                            } else {
                                console.log('❌ Bug response error:', response);
                                $dashboardNoBugs.html('<p style="color: #d32f2f;">Chyba: ' + (response.data ? JSON.stringify(response.data) : 'Unknown error') + '</p>');
                                $dashboardBugSearchResults.hide();
                                $dashboardNoBugs.show();
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('❌ Bug AJAX error:', error, 'Status:', status);
                            console.error('Response:', xhr.responseText);
                            $dashboardNoBugs.html('<p style="color: #d32f2f;">AJAX chyba: ' + error + '</p>');
                            $dashboardBugSearchResults.hide();
                            $dashboardNoBugs.show();
                        }
                    });
                }
            });
        } else {
            console.error('❌ ERROR: Could not find dashboard bug search input!');
        }
        
        console.log('=== Dashboard Bug Search Init End ===');
        
        // ===== BUG SEARCH INITIALIZATION =====
        console.log('=== Bug Search Init Start ===');
        var $bugSearchInput = $('#helpdesk-bugs-search');
        var $bugSearchResults = $('#helpdesk-bug-search-results');
        var $bugSearchResultsTbody = $('#helpdesk-bug-search-results-tbody');
        
        console.log('Bug search input:', $bugSearchInput.length);
        console.log('Bug search results div:', $bugSearchResults.length);
        console.log('Bug search results tbody:', $bugSearchResultsTbody.length);
        
        if ($bugSearchInput.length > 0 && $bugSearchResults.length > 0) {
            console.log('Bug search initialized');
            
            $bugSearchInput.on('keyup', function(e) {
                const val = $(this).val();
                
                if (val.length < 2) {
                    console.log('Bug search input too short, hiding results');
                    $bugSearchResults.hide();
                    return;
                }
                
                if (val.length >= 2) {
                    console.log('Bug search value is long enough, sending AJAX');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'helpdesk_search_bugs',
                            _nonce: nonce,
                            search: val
                        },
                        dataType: 'json',
                        success: function(response) {
                            console.log('Bug search AJAX success:', response);
                            console.log('Bug search response data:', response.data);
                            
                            if (response.success && response.data && response.data.bugs) {
                                console.log('Bugs array:', response.data.bugs);
                                console.log('Bugs count:', response.data.bugs.length);
                                
                                if (response.data.bugs.length > 0) {
                                    console.log('Rendering bugs...');
                                    let html = '';
                                    response.data.bugs.forEach(function(bug, idx) {
                                        console.log('Rendering bug ' + idx + ':', bug);
                                        html += '<tr data-bug-id="' + bug.id + '">';
                                        html += '<td>' + (bug.nazov || '') + '</td>';
                                        html += '<td>' + (bug.product_name || '') + '</td>';
                                        
                                        // Render tags
                                        html += '<td>';
                                        if (bug.tagy) {
                                            try {
                                                const tagy = JSON.parse(bug.tagy);
                                                if (Array.isArray(tagy) && tagy.length > 0) {
                                                    html += '<div style="display: flex; flex-wrap: wrap; gap: 4px;">';
                                                    tagy.forEach(tag => {
                                                        html += '<span style="background-color: #e7f3ff; color: #0073aa; padding: 2px 6px; border-radius: 3px; font-size: 12px; white-space: nowrap;">' + tag + '</span>';
                                                    });
                                                    html += '</div>';
                                                }
                                            } catch(e) {
                                                // Ignore parsing errors
                                            }
                                        }
                                        html += '</td>';
                                        
                                        html += '<td>';
                                        html += '<button class="button button-small dashboard-bug-detail" data-bug-id="' + bug.id + '" style="margin-right: 5px;">📋 Detail</button>';
                                        html += '</td>';
                                        html += '</tr>';
                                    });
                                    console.log('Bug HTML ready:', html);
                                    $bugSearchResultsTbody.html(html);
                                    $bugSearchResults.show();
                                    console.log('Bug results displayed');
                                } else {
                                    console.log('No bugs found - showing empty message');
                                    $bugSearchResultsTbody.html('<tr><td colspan="4">Žiadne chyby neboli nájdené.</td></tr>');
                                    $bugSearchResults.show();
                                }
                            } else {
                                console.log('Bug response error - no success or no bugs');
                                $bugSearchResultsTbody.html('<tr><td colspan="4">Chyba pri vyhľadávaní.</td></tr>');
                                $bugSearchResults.show();
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Bug search AJAX error:', error);
                            $bugSearchResultsTbody.html('<tr><td colspan="5">AJAX chyba: ' + error + '</td></tr>');
                            $bugSearchResults.show();
                        }
                    });
                }
            });
        } else {
            console.log('ERROR: Could not find bug search elements! Bug search disabled.');
        }
        
        console.log('=== Bug Search Init End ===');
        
        // Function to load and display bug details
        function loadBugDetails(bugId) {
            console.log('Loading bug details for ID:', bugId);
            
            // Load full bug details
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_get_bug',
                    _ajax_nonce: nonce,
                    id: bugId
                },
                dataType: 'json',
                success: function(detailResponse) {
                    console.log('Bug detail response:', detailResponse);
                    
                    if (detailResponse.success) {
                        const bugData = detailResponse.data.bug;
                        console.log('Bug details loaded:', bugData);
                        console.log('Signature data:', bugData.signature);
                        
                        // Create modal for bug details with 2-column layout
                        let html = '<div style="padding: 20px; background: white; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">';
                        html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
                        html += '<h3 style="margin: 0;">📋 Detail riešenia</h3>';
                        html += '<button type="button" class="button-link" style="color: #666; font-size: 24px; cursor: pointer; border: none; background: none;" onclick="this.closest(\'.helpdesk-bug-modal\').remove(); document.getElementById(\'bug-modal-backdrop\').remove();">✕</button>';
                        html += '</div>';
                        
                        html += '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';
                        html += '<tr><td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #ddd;">Názov:</td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' + (bugData.nazov || '-') + '</td></tr>';
                        html += '<tr><td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #ddd;">Produkt:</td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' + (bugData.product_name || '-') + '</td></tr>';
                        html += '<tr><td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #ddd; vertical-align: top;">Popis:</td><td style="padding: 8px; border-bottom: 1px solid #ddd; word-break: break-word;">' + (bugData.popis || '-') + '</td></tr>';
                        html += '</table>';
                        
                        // Build solution text with signature if exists
                        let solutionDisplay = bugData.riesenie || '-';
                        console.log('Checking signature - signature object:', bugData.signature);
                        console.log('Signature type:', typeof bugData.signature);
                        console.log('Signature has text_podpisu:', bugData.signature && bugData.signature.text_podpisu);
                        
                        if (bugData.signature && bugData.signature.text_podpisu && bugData.signature.text_podpisu.trim()) {
                            solutionDisplay = solutionDisplay + '\n\n---\n' + bugData.signature.text_podpisu;
                            console.log('Added signature to display');
                        } else {
                            console.log('No signature text found');
                        }
                        
                        // Determine grid columns based on solution 2
                        let gridColumns = (bugData.riesenie_2 && bugData.riesenie_2.trim()) ? '1fr 1fr' : '1fr';
                        
                        // 2-column layout for solutions (or full width if no solution 2)
                        html += '<div style="display: grid; grid-template-columns: ' + gridColumns + '; gap: 20px;">';
                        
                        // Column 1: Solution step 1
                        html += '<div>';
                        html += '<h4 style="margin-top: 0; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">Riešenie - 1. krok</h4>';
                        html += '<div style="word-break: break-word; white-space: pre-wrap; max-height: 300px; overflow-y: auto; background: #f5f5f5; padding: 10px; border-radius: 3px;">' + solutionDisplay + '</div>';
                        
                        // Add copy button if solution exists
                        if (bugData.riesenie && bugData.riesenie.trim()) {
                            html += '<div style="margin-top: 10px;">';
                            html += '<button type="button" class="button copy-solution-btn" data-solution="' + bugData.riesenie.replace(/"/g, '&quot;') + '" data-signature="' + (bugData.signature && bugData.signature.text_podpisu ? bugData.signature.text_podpisu.replace(/"/g, '&quot;') : '') + '" style="cursor: pointer; width: 100%;">Kopírovať do clipboardu</button>';
                            html += '</div>';
                        }
                        html += '</div>';
                        
                        // Column 2: Solution step 2 - only show if not empty
                        if (bugData.riesenie_2 && bugData.riesenie_2.trim()) {
                            let solution2Display = bugData.riesenie_2;
                            if (bugData.signature && bugData.signature.text_podpisu && bugData.signature.text_podpisu.trim()) {
                                solution2Display = solution2Display + '\n\n---\n' + bugData.signature.text_podpisu;
                                console.log('Added signature to solution 2 display');
                            } else {
                                console.log('No signature text found for solution 2');
                            }
                            
                            html += '<div>';
                            html += '<h4 style="margin-top: 0; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">Riešenie - 2. krok</h4>';
                            html += '<div style="word-break: break-word; white-space: pre-wrap; max-height: 300px; overflow-y: auto; background: #f5f5f5; padding: 10px; border-radius: 3px;">' + solution2Display + '</div>';
                            
                            // Add copy button
                            html += '<div style="margin-top: 10px;">';
                            html += '<button type="button" class="button copy-solution-2-btn" data-solution="' + bugData.riesenie_2.replace(/"/g, '&quot;') + '" data-signature="' + (bugData.signature && bugData.signature.text_podpisu ? bugData.signature.text_podpisu.replace(/"/g, '&quot;') : '') + '" style="cursor: pointer; width: 100%;">Kopírovať do clipboardu</button>';
                            html += '</div>';
                            html += '</div>';
                        }
                        
                        html += '</div>';
                        
                        html += '</div>';
                        
                        // Pridaj modal do DOM-u
                        const modalId = 'bug-modal-' + bugId;
                        let existing = document.getElementById(modalId);
                        if (existing) {
                            existing.remove();
                        }
                        
                        const modal = document.createElement('div');
                        modal.id = modalId;
                        modal.className = 'helpdesk-bug-modal';
                        modal.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10000; max-width: 1000px; max-height: 85vh; overflow-y: auto; background: white; border: 2px solid #0073aa; border-radius: 5px; padding: 0; box-shadow: 0 5px 30px rgba(0,0,0,0.3);';
                        modal.innerHTML = html;
                        document.body.appendChild(modal);
                        
                        // Pridaj backdrop
                        if (!document.getElementById('bug-modal-backdrop')) {
                            const backdrop = document.createElement('div');
                            backdrop.id = 'bug-modal-backdrop';
                            backdrop.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999;';
                            backdrop.onclick = function() {
                                const allBugModals = document.querySelectorAll('.helpdesk-bug-modal');
                                allBugModals.forEach(m => m.remove());
                                backdrop.remove();
                            };
                            document.body.appendChild(backdrop);
                        }
                        
                        console.log('Bug detail modal displayed');
                    } else {
                        console.error('Response not successful:', detailResponse);
                        alert('Chyba pri načítaní detailov: ' + (detailResponse.data.message || ''));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error, 'Status:', status, 'Response:', xhr.responseText);
                    alert('AJAX chyba pri načítaní detailov: ' + error);
                }
            });
        }
        
        // Copy solution to clipboard from detail modal
        $(document).on('click', '.copy-solution-btn', function(e) {
            e.stopPropagation();
            let textToCopy = $(this).data('solution');
            const signatureText = $(this).data('signature');
            
            // Append signature if exists
            if (signatureText && signatureText.trim()) {
                textToCopy = textToCopy + '\n\n' + signatureText;
            }
            
            if (textToCopy && textToCopy.trim()) {
                navigator.clipboard.writeText(textToCopy).then(function() {
                    alert('Riešenie skopírované do clipboardu');
                }).catch(function(err) {
                    // Fallback for older browsers
                    const textarea = document.createElement('textarea');
                    textarea.value = textToCopy;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    alert('Riešenie skopírované do clipboardu');
                });
            }
        });

        // Handler for copy solution step 2 button
        $(document).on('click', '.copy-solution-2-btn', function(e) {
            e.stopPropagation();
            let textToCopy = $(this).data('solution');
            const signatureText = $(this).data('signature');
            
            // Append signature if exists
            if (signatureText && signatureText.trim()) {
                textToCopy = textToCopy + '\n\n' + signatureText;
            }
            
            if (textToCopy && textToCopy.trim()) {
                navigator.clipboard.writeText(textToCopy).then(function() {
                    alert('Riešenie - 2. krok skopírované do clipboardu');
                }).catch(function(err) {
                    // Fallback for older browsers
                    const textarea = document.createElement('textarea');
                    textarea.value = textToCopy;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    alert('Riešenie - 2. krok skopírované do clipboardu');
                });
            }
        });
        
        // Handler for bug detail button
        $(document).on('click', '.dashboard-bug-detail', function(e) {
            e.stopPropagation();
            const bugId = $(this).data('bug-id');
            console.log('Detail button clicked for bug:', bugId);
            loadBugDetails(bugId);
        });



        // Handler pre Pracovníci tlačidlo
        $(document).on('click', '.dashboard-project-employees', function(e) {
            e.stopPropagation();
            const projectId = $(this).data('project-id');
            console.log('Employees button clicked for project:', projectId);
            loadProjectEmployeesDetail(projectId);
        });

        // Click handler for select button in multi-project results
        $(document).on('click', '#dashboard-search-results .dashboard-project-select', function(e) {
            e.stopPropagation();
            console.log('=== Project select button clicked ===');
            
            const projectId = $(this).data('project-id');
            console.log('Project ID from button:', projectId);
            
            if (!projectId) {
                console.error('No project ID found!');
                alert('Chyba: Nepodarilo sa identifikovať projekt');
                return;
            }
            
            loadProjectDetails(projectId);
        });

        // Function to load and display project details
        function loadProjectDetails(projectId) {
            console.log('Loading project details for ID:', projectId);
            
            // Load full project details
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_get_project',
                    _ajax_nonce: nonce,
                    id: projectId
                },
                dataType: 'json',
                success: function(detailResponse) {
                    console.log('Project detail response:', detailResponse);
                    
                    if (detailResponse.success) {
                        const projectData = detailResponse.data.project;
                        const projectEmployees = detailResponse.data.employees || [];

                        console.log('Project details loaded:', projectData);
                        console.log('Project employees:', projectEmployees);

                        // Find and display main employee details in card
                        // Prioritize: 1) standby (should_be_main) UNLESS nemenit is checked, 2) is_hlavny from DB
                        let mainEmployee = null;
                        
                        // First, check if any employee should be main from standby
                        const standbyMainCandidate = projectEmployees.find(emp => emp.should_be_main);
                        if (standbyMainCandidate) {
                            // Check if this employee has nemenit flag
                            if (!standbyMainCandidate.nemenit) {
                                // nemenit is not checked, so use standby assignment
                                mainEmployee = standbyMainCandidate;
                            }
                        }
                        
                        // If no standby main or nemenit is checked, use DB is_hlavny
                        if (!mainEmployee) {
                            mainEmployee = projectEmployees.find(emp => emp.is_hlavny == 1);
                        }
                        
                        if (mainEmployee) {
                            $('#main-employee-meno').text(mainEmployee.meno_priezvisko || '-');
                            $('#main-employee-klapka').text(mainEmployee.klapka || '-');
                            $('#main-employee-telefon').text(mainEmployee.mobil || '-');
                            $('#main-employee-pozicia').text(mainEmployee.profesia || '-');
                            $('#main-employee-poznamka').text(mainEmployee.poznamka || '-');
                            $('#main-employee-card').show();
                        } else {
                            $('#main-employee-card').hide();
                        }
                        
                        console.log('Main employee card displayed:', mainEmployee);
                    } else {
                        console.error('Response not successful:', detailResponse);
                        alert('Chyba pri načítaní detailov: ' + (detailResponse.data.message || ''));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error, 'Status:', status, 'Response:', xhr.responseText);
                    alert('AJAX chyba pri načítaní detailov: ' + error);
                }
            });
        }

        // Funkcia na zobrazenie detailov pracovníkov projektu (v modále)
        function loadProjectEmployeesDetail(projectId) {
            console.log('Loading employees detail for project:', projectId);
            
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_get_project',
                    _ajax_nonce: nonce,
                    id: projectId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.project) {
                        const project = response.data.project;
                        const employees = response.data.employees || [];
                        const todayDate = new Date().toISOString().split('T')[0]; // YYYY-MM-DD format
                        
                        // Rozdelenie pracovníkov na dve skupiny
                        // Excel employees - tí s nemenit=1 a is_hlavny=1 (bez filtra pohotovosti)
                        const excelEmployees = employees.filter(emp => emp.nemenit && emp.is_hlavny == 1);
                        
                        // Get excel employee IDs to exclude them from standby list
                        const excelEmpIds = excelEmployees.map(e => e.id);
                        
                        // Standby employees - len tí s AKTÍVNOU pohotovosťou DNES a nie sú v exceli
                        const standbyEmployees = employees.filter(emp => {
                            if (!emp.pohotovost_od || !emp.pohotovost_do) return false;
                            if (excelEmpIds.includes(emp.id)) return false; // vynechaj tých, čo sú už v exceli
                            // Filter only current/active standby periods
                            return emp.pohotovost_od <= todayDate && todayDate <= emp.pohotovost_do;
                        });
                        
                        console.log('Excel employees:', excelEmployees);
                        console.log('Standby employees:', standbyEmployees);
                        
                        // Vytvor modal/box s detailom
                        let html = '<div style="padding: 20px; background: white; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">';
                        html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
                        html += '<h3 style="margin: 0;">👥 Pracovníci projektu: ' + (project.nazov || '') + '</h3>';
                        html += '<button type="button" class="button-link" style="color: #666; font-size: 24px; cursor: pointer; border: none; background: none;" onclick="this.closest(\'.helpdesk-employees-modal\').remove(); document.getElementById(\'employees-modal-backdrop\').remove();">✕</button>';
                        html += '</div>';
                        
                        // SEKCIA 1: Pracovníci z excelu
                        if (excelEmployees.length > 0) {
                            html += '<h4 style="margin: 20px 0 10px 0; color: #333;">📋 Pracovníci z excelu</h4>';
                            html += '<table class="wp-list-table widefat" style="margin: 0 0 20px 0;">';
                            html += '<thead><tr>';
                            html += '<th>Status</th>';
                            html += '<th>Meno a Priezvisko</th>';
                            html += '<th>Klapka</th>';
                            html += '<th>Mobil</th>';
                            html += '<th>Poznámka</th>';
                            html += '</tr></thead>';
                            html += '<tbody>';
                            
                            excelEmployees.forEach(emp => {
                                const isMain = emp.is_hlavny == 1;
                                const rowStyle = isMain ? 'background-color: #fffacd; font-weight: bold;' : '';
                                html += '<tr style="' + rowStyle + '" data-emp-id="' + emp.id + '" data-proj-id="' + project.id + '">';
                                html += '<td>';
                                html += '<select class="employee-status-select" data-emp-id="' + emp.id + '" data-proj-id="' + project.id + '" style="width: 100%; padding: 4px;">';
                                html += '<option value="0"' + (isMain ? '' : ' selected') + '>Člen</option>';
                                html += '<option value="1"' + (isMain ? ' selected' : '') + '>⭐ Hlavný</option>';
                                html += '</select>';
                                html += '</td>';
                                html += '<td>' + (emp.meno_priezvisko || '-') + '</td>';
                                html += '<td>' + (emp.klapka || '-') + '</td>';
                                html += '<td>' + (emp.mobil || '-') + '</td>';
                                html += '<td style="max-width: 200px; word-break: break-word;">' + (emp.poznamka || '-') + '</td>';
                                html += '</tr>';
                            });
                            
                            html += '</tbody></table>';
                        }
                        
                        // SEKCIA 2: Pracovníci z pohotovosti
                        if (standbyEmployees.length > 0) {
                            html += '<h4 style="margin: 20px 0 10px 0; color: #333;">📅 Pracovníci z pohotovosti v CRM</h4>';
                            html += '<table class="wp-list-table widefat" style="margin: 0 0 20px 0;">';
                            html += '<thead><tr>';
                            html += '<th>Meno a Priezvisko</th>';
                            html += '<th>Klapka</th>';
                            html += '<th>Mobil</th>';
                            html += '<th>Poznámka</th>';
                            html += '</tr></thead>';
                            html += '<tbody>';
                            
                            standbyEmployees.forEach(emp => {
                                html += '<tr data-emp-id="' + emp.id + '" data-proj-id="' + project.id + '">';
                                html += '<td>' + (emp.meno_priezvisko || '-') + '</td>';
                                html += '<td>' + (emp.klapka || '-') + '</td>';
                                html += '<td>' + (emp.mobil || '-') + '</td>';
                                html += '<td style="max-width: 200px; word-break: break-word;">' + (emp.poznamka || '-') + '</td>';
                                html += '</tr>';
                            });
                            
                            html += '</tbody></table>';
                        }
                        
                        if (excelEmployees.length === 0 && standbyEmployees.length === 0) {
                            html += '<p style="color: #999;">Na projekte nie sú priradení žiadni pracovníci.</p>';
                        }
                        
                        html += '</div>';
                        
                        // Pridaj modal do DOM-u
                        const modalId = 'employees-modal-' + projectId;
                        let existing = document.getElementById(modalId);
                        if (existing) {
                            existing.remove();
                        }
                        
                        const modal = document.createElement('div');
                        modal.id = modalId;
                        modal.className = 'helpdesk-employees-modal';
                        modal.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10000; max-width: 700px; max-height: 80vh; overflow-y: auto; background: white; border: 2px solid #999; border-radius: 5px; padding: 0; box-shadow: 0 5px 30px rgba(0,0,0,0.3);';
                        modal.innerHTML = html;
                        document.body.appendChild(modal);
                        
                        // Pridaj backdrop
                        let backdrop = document.getElementById('employees-modal-backdrop');
                        if (!backdrop) {
                            backdrop = document.createElement('div');
                            backdrop.id = 'employees-modal-backdrop';
                            backdrop.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999;';
                            backdrop.onclick = function() {
                                modal.remove();
                                backdrop.remove();
                            };
                            document.body.appendChild(backdrop);
                        }
                        
                        console.log('Employees modal displayed');
                    } else {
                        alert('Chyba pri načítaní pracovníkov');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    alert('Chyba pri načítaní: ' + error);
                }
            });
        }

        // Click handler for search results - load project details (for single project or row click)
        // Už nič - pracovníci sa zobrazujú priamo v search results bez modalu

        console.log('Search result click handler attached');

        // Modal close on ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.helpdesk-modal').hide();
            }
        });

        // Close modal when clicking outside of it
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('helpdesk-modal')) {
                $(e.target).hide();
            }
        });

        function initEmployees() {
            // Load positions
            function loadPositions() {
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_get_positions',
                        _ajax_nonce: nonce
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const positions = response.data.positions || [];
                            let html = '<option value="">-- Vyberte pozíciu --</option>';
                            positions.forEach(pos => {
                                html += '<option value="' + pos.id + '">' + pos.profesia + '</option>';
                            });
                            $('#employee-pozicia').html(html);
                        }
                    }
                });
            }

            // Open new employee modal
            $(document).on('click', '.helpdesk-btn-new-employee', function() {
                console.log('New employee button clicked');
                $('#employee-id').val('');
                $('#helpdesk-employee-form')[0].reset();
                $('#employee-modal-title').text('Pridať pracovníka');
                loadProjectsForEmployee();
                loadPositions();
                $('#helpdesk-employee-modal').show();
                $('.error-message').text('');
            });

            // Load projects for employee form
            function loadProjectsForEmployee() {
                const employeeId = $('#employee-id').val();
                console.log('loadProjectsForEmployee called, employeeId:', employeeId);
                
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_get_projects',
                        _ajax_nonce: nonce
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Projects loaded:', response);
                        if (response.success) {
                            const projects = response.data.projects || [];
                            console.log('Projects count:', projects.length);
                            let html = '';
                            projects.forEach(proj => {
                                html += '<div style="margin-bottom: 8px;">';
                                html += '<label style="display: flex; align-items: center; gap: 8px; margin: 0; cursor: pointer;">';
                                html += '<input type="checkbox" name="employee-projects" value="' + proj.id + '" class="employee-project-checkbox">';
                                html += '<span>' + proj.zakaznicke_cislo + '</span>';
                                html += '</label></div>';
                            });
                            $('#employee-projects-list').html(html);
                            console.log('HTML inserted into #employee-projects-list');

                            // Ak sa upravuje existujúci pracovník, načítaj jeho projekty
                            if (employeeId) {
                                console.log('Loading existing employee projects');
                                $.ajax({
                                    type: 'POST',
                                    url: ajaxurl,
                                    data: {
                                        action: 'helpdesk_get_employee',
                                        _ajax_nonce: nonce,
                                        id: employeeId
                                    },
                                    dataType: 'json',
                                    success: function(empResponse) {
                                        console.log('Employee response:', empResponse);
                                        if (empResponse.success && empResponse.data.employee_projects) {
                                            const employeeProjects = empResponse.data.employee_projects;
                                            console.log('Employee projects:', employeeProjects);
                                            // Store globally for standby selection
                                            employeeSelectedProjects = employeeProjects;
                                            employeeProjects.forEach(projId => {
                                                $('[name="employee-projects"][value="' + projId + '"]').prop('checked', true);
                                            });
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('Error loading employee:', error);
                                    }
                                });
                            } else {
                                // New employee - reset selected projects
                                console.log('New employee, resetting projects');
                                employeeSelectedProjects = [];
                            }
                        } else {
                            console.error('Response not successful:', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading projects:', error);
                        $('#employee-projects-list').html('<p style="color: red;">Chyba pri načítavaní projektov</p>');
                    },
                    complete: function() {
                        // After projects are loaded, also load standby projects
                        console.log('Projects loading complete, calling loadStandbyProjectsFromData');
                        loadStandbyProjectsFromData();
                    }
                });
            }

            // Make loadProjectsForEmployee available globally for edit
            window.loadProjectsForEmployee = loadProjectsForEmployee;

            // Search/Filter projects for employee
            $(document).on('keyup', '#employee-projects-search', function() {
                const searchText = ($(this).val() || '').toLowerCase().trim();
                $('#employee-projects-list > div').each(function() {
                    const projectText = $(this).text().toLowerCase();
                    if (searchText === '' || projectText.includes(searchText)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Edit employee
            $(document).on('click', '#helpdesk-employees-table .helpdesk-btn-edit', function() {
                const id = $(this).data('id');
                console.log('Edit employee:', id);
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_get_employee',
                        _ajax_nonce: nonce,
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const employee = response.data.employee;
                            const standbyPeriods = response.data.standby_periods || [];
                            $('#employee-id').val(employee.id);
                            $('#employee-meno_priezvisko').val(employee.meno_priezvisko);
                            $('#employee-klapka').val(employee.klapka);
                            $('#employee-mobil').val(employee.mobil);
                            $('#employee-poznamka').val(employee.poznamka);
                            $('#employee-modal-title').text('Upraviť pracovníka');
                            
                            // Load projects and positions first, then set values
                            loadProjectsForEmployee();
                            
                            // Load positions and set value after loading
                            $.ajax({
                                type: 'POST',
                                url: ajaxurl,
                                data: {
                                    action: 'helpdesk_get_positions',
                                    _ajax_nonce: nonce
                                },
                                dataType: 'json',
                                success: function(pos_response) {
                                    if (pos_response.success) {
                                        const positions = pos_response.data.positions || [];
                                        let html = '<option value="">-- Vyberte pozíciu --</option>';
                                        positions.forEach(pos => {
                                            html += '<option value="' + pos.id + '">' + pos.profesia + '</option>';
                                        });
                                        $('#employee-pozicia').html(html);
                                        // Now set the position value
                                        $('#employee-pozicia').val(employee.pozicia_id || '');
                                    }
                                }
                            });
                            
                            displayStandbyPeriodsOnEdit(standbyPeriods);
                            $('#helpdesk-employee-modal').show();
                            $('.error-message').text('');
                        } else {
                            alert(response.data.message || 'Chyba pri načítaní');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                        alert('AJAX chyba pri načítaní: ' + error);
                    }
                });
            });

            // Close modal
            $(document).on('click', '#helpdesk-employee-modal .helpdesk-modal-close, #helpdesk-employee-modal .helpdesk-modal-close-btn', function() {
                $('#helpdesk-employee-modal').hide();
            });

            // Close detail modal
            $(document).on('click', '#helpdesk-employee-detail-modal .helpdesk-modal-close, #helpdesk-employee-detail-modal .helpdesk-modal-close-btn', function() {
                console.log('Closing detail modal');
                $('#helpdesk-employee-detail-modal').removeClass('active');
            });

            // Submit form
            $(document).on('submit', '#helpdesk-employee-form', function(e) {
                e.preventDefault();
                console.log('Employee form submitted');
                const id = $('#employee-id').val();

                // Zbieranie vybraných projektov
                const selectedProjects = [];
                $('[name="employee-projects"]:checked').each(function() {
                    selectedProjects.push($(this).val());
                });

                const formData = {
                    meno_priezvisko: $('#employee-meno_priezvisko').val(),
                    klapka: $('#employee-klapka').val(),
                    mobil: $('#employee-mobil').val(),
                    pozicia_id: $('#employee-pozicia').val(),
                    poznamka: $('#employee-poznamka').val(),
                    projects: selectedProjects
                };
                console.log('Form data:', formData);

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_save_employee',
                        _ajax_nonce: nonce,
                        id: id,
                        ...formData
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Response:', response);
                        console.log('Response.data:', response.data);
                        $('.error-message').text('');
                        if (response.success) {
                            // Check if standby mode is selected and set to auto
                            const standbyMode = $('input[name="standby-mode"]:checked').val();
                            const isStandbyEnabled = $('input[name="standby-mode"]').length > 0;
                            
                            if (isStandbyEnabled && standbyMode === 'auto' && id) {
                                console.log('Auto mode selected, triggering auto-generation');
                                triggerAutoGenerateStandby(id);
                            } else {
                                alert(response.data.message);
                                location.reload();
                            }
                        } else {
                            console.log('Errors object:', response.data.errors);
                            console.log('Full response data:', JSON.stringify(response.data));
                            if (response.data && response.data.errors) {
                                $.each(response.data.errors, function(key, msg) {
                                    console.log('Setting error for ' + key + ':', msg);
                                    $('#error-' + key).text(msg);
                                });
                            } else if (response.data && response.data.message) {
                                alert('Chyba: ' + response.data.message);
                            } else {
                                alert('Neznáma chyba: ' + JSON.stringify(response.data));
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error, 'Status:', status, 'Response:', xhr.responseText);
                        alert('AJAX chyba pri uložení: ' + error + '\n' + xhr.responseText);
                    }
                });
            });

            // Auto-generate standby periods
            function triggerAutoGenerateStandby(employeeId) {
                console.log('=== Auto-generating standby periods (BATCH MODE) ===');
                console.log('Employee ID:', employeeId);
                
                const startDate = $('#standby-start-date').val();
                const intervalType = $('#standby-interval-type').val();
                const intervalCount = parseInt($('#standby-interval-count').val());
                const numPeriods = parseInt($('#standby-num-periods').val());
                
                // Get selected projects from checkboxes
                const projectIds = [];
                $('.standby-project-checkbox:checked').each(function() {
                    projectIds.push($(this).val());
                });
                
                // Use current employee and selected rotation employees
                const rotationEmployees = $('#standby-rotation-employees').val() || [];
                
                console.log('Auto-generation params:', {
                    startDate, 
                    intervalType, 
                    intervalCount, 
                    projectIds: projectIds, 
                    projectIdsLength: projectIds.length,
                    rotationEmployees: rotationEmployees, 
                    rotationEmployeesLength: rotationEmployees.length,
                    numPeriods
                });
                
                if (!startDate) {
                    alert('Pohotovosť: Vyplňte dátum začiatku striedania');
                    return;
                }
                if (projectIds.length < 1) {
                    alert('Pohotovosť: Vyberte aspoň jeden projekt');
                    return;
                }
                if (rotationEmployees.length < 1) {
                    alert('Pohotovosť: Vyberte aspoň jedného pracovníka na rotáciu');
                    return;
                }
                if (numPeriods < 1) {
                    alert('Pohotovosť: Zadajte počet periód');
                    return;
                }
                
                // Build all periods in a batch array
                const allPeriods = [];
                
                projectIds.forEach(projectId => {
                    const rotationPeriods = generateRotationPeriods(
                        startDate,
                        intervalType,
                        intervalCount,
                        rotationEmployees,
                        numPeriods
                    );
                    
                    console.log('Generated periods for project ' + projectId + ':', rotationPeriods);
                    
                    rotationPeriods.forEach((entry, idx) => {
                        allPeriods.push({
                            employee_id: parseInt(entry.employee_id),
                            project_id: parseInt(projectId),
                            od: entry.od,
                            do: entry.do
                        });
                    });
                });
                
                console.log('Total periods to save:', allPeriods.length);
                console.log('All periods:', allPeriods);
                
                if (allPeriods.length === 0) {
                    alert('Pohotovosť: Žiadne periody na generovanie');
                    location.reload();
                    return;
                }
                
                // Send all periods in a single AJAX request
                console.log('Sending batch request with ' + allPeriods.length + ' periods');
                
                // Show loading indicator
                const $modal = $('#helpdesk-employee-modal');
                const originalContent = $modal.html();
                $modal.find('.helpdesk-modal-content').html('<div style="text-align: center; padding: 40px;"><p>⏳ Generujem pohotovosť...</p><p style="font-size: 12px; color: #666;">Čakajte, prosím...</p></div>');
                
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_save_standby_batch',
                        _ajax_nonce: nonce,
                        periods: JSON.stringify(allPeriods)
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Batch AJAX success response:', response);
                        if (response.success) {
                            const savedCount = response.data.saved_count || allPeriods.length;
                            alert('✓ Vytvoreného ' + savedCount + ' periód pohotovosti. Pracovník bol uložený.');
                            // Close modal before reload
                            $modal.removeClass('active');
                            setTimeout(function() {
                                location.reload();
                            }, 800);
                        } else {
                            alert('✗ Chyba pri generovaní: ' + (response.data.message || 'Neznáma chyba'));
                            // Restore modal content and close
                            $modal.html(originalContent);
                            $modal.removeClass('active');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('✗ Batch generation AJAX error:', error, 'Status:', status, 'Response:', xhr.responseText);
                        alert('✗ AJAX chyba pri generovaní pohotovosti: ' + error);
                        // Restore modal content and close
                        $modal.html(originalContent);
                        $modal.removeClass('active');
                    }
                });
            }

            // Delete employee
            $(document).on('click', '#helpdesk-employees-table .helpdesk-btn-delete', function() {
                if (confirm('Naozaj chcete zmazať tohto pracovníka?')) {
                    const id = $(this).data('id');
                    $.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: {
                            action: 'helpdesk_delete_employee',
                            _ajax_nonce: nonce,
                            id: id
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                alert(response.data.message);
                                location.reload();
                            } else {
                                alert(response.data.message || 'Chyba pri mazaní');
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('AJAX chyba pri mazaní: ' + error);
                        }
                    });
                }
            });

            // Bulk select all employees
            $(document).on('change', '#helpdesk-select-all-employees', function() {
                const isChecked = $(this).is(':checked');
                $('#helpdesk-employees-table .helpdesk-employee-checkbox').prop('checked', isChecked);
                updateBulkButtonVisibility();
            });

            // Individual employee checkbox
            $(document).on('change', '.helpdesk-employee-checkbox', function() {
                updateBulkButtonVisibility();
            });

            // Show/hide bulk button based on selection
            function updateBulkButtonVisibility() {
                const selectedCount = $('#helpdesk-employees-table .helpdesk-employee-checkbox:checked').length;
                if (selectedCount > 0) {
                    $('#helpdesk-bulk-assign-projects').show();
                } else {
                    $('#helpdesk-bulk-assign-projects').hide();
                }
            }

            // Bulk assign projects button
            $(document).on('click', '#helpdesk-bulk-assign-projects', function() {
                // Get list of all projects
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_get_projects',
                        _ajax_nonce: nonce
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const projects = response.data.projects || [];
                            let html = '';
                            projects.forEach(proj => {
                                html += '<div style="margin-bottom: 10px;">';
                                html += '<label style="display: flex; align-items: center; gap: 8px; margin: 0; cursor: pointer;">';
                                html += '<input type="checkbox" name="bulk-projects" value="' + proj.id + '" class="bulk-project-checkbox">';
                                html += '<span>' + proj.zakaznicke_cislo + '</span>';
                                html += '</label></div>';
                            });
                            $('#bulk-projects-list').html(html);
                            $('#helpdesk-bulk-projects-modal').show();
                        }
                    }
                });
            });

            // Close bulk projects modal
            $(document).on('click', '#helpdesk-bulk-projects-modal .helpdesk-modal-close, #helpdesk-bulk-projects-modal .helpdesk-modal-close-btn', function() {
                $('#helpdesk-bulk-projects-modal').hide();
            });

            // Submit bulk projects form
            $(document).on('submit', '#helpdesk-bulk-projects-form', function(e) {
                e.preventDefault();
                
                const selectedEmployees = [];
                $('#helpdesk-employees-table .helpdesk-employee-checkbox:checked').each(function() {
                    selectedEmployees.push($(this).val());
                });

                const selectedProjects = [];
                $('.bulk-project-checkbox:checked').each(function() {
                    selectedProjects.push($(this).val());
                });

                if (selectedEmployees.length === 0 || selectedProjects.length === 0) {
                    alert('Prosím vyberte aspoň jedného pracovníka a jeden projekt');
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_bulk_assign_projects',
                        _ajax_nonce: nonce,
                        employees: selectedEmployees,
                        projects: selectedProjects
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message || 'Projekty boli priradené');
                            location.reload();
                        } else {
                            alert(response.data.message || 'Chyba pri priraďovaní');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('AJAX chyba: ' + error);
                    }
                });
            });

            // Standby mode toggle (manual vs auto)
            $(document).on('change', '.standby-mode-radio', function() {
                console.log('=== STANDBY MODE CHANGED ===');
                const mode = $(this).val();
                console.log('Selected mode:', mode);
                
                if (mode === 'manual') {
                    console.log('Showing manual pohotovost section');
                    $('#employee-pohotovost-section').show();
                    $('#employee-auto-pohotovost-section').hide();
                    loadStandbyProjectsFromData();
                } else if (mode === 'auto') {
                    console.log('Showing auto pohotovost section');
                    $('#employee-pohotovost-section').hide();
                    $('#employee-auto-pohotovost-section').show();
                    loadStandbyProjectsFromData();
                }
            });

            // Pohotovosť checkbox toggle
            $(document).on('change', '#employee-pohotovost-checkbox', function() {
                console.log('=== POHOTOVOST CHECKBOX CHANGED ===');
                console.log('Is checked:', $(this).is(':checked'));
                
                if ($(this).is(':checked')) {
                    console.log('Showing pohotovost sections and loading projects');
                    $('#employee-pohotovost-section').show();
                    $('#employee-auto-pohotovost-section').show();
                    loadStandbyProjectsFromData();
                } else {
                    console.log('Hiding pohotovost sections');
                    $('#employee-pohotovost-section').hide();
                    $('#employee-auto-pohotovost-section').hide();
                }
            });

            // Load projects for standby select - directly from employee data
            function loadStandbyProjectsFromData() {
                console.log('=== loadStandbyProjectsFromData STARTED ===');
                
                const employeeId = $('#employee-id').val();
                console.log('Employee ID:', employeeId);
                
                if (!employeeId) {
                    console.error('NO EMPLOYEE ID!');
                    $('#standby-project-select').html('<option disabled>Pracovník nie je uložený</option>');
                    $('#standby-projects-list').html('<p style="color: red;">Pracovník nie je uložený</p>');
                    return;
                }
                
                console.log('Loading employee data for ID:', employeeId);

                // Load employee to get their projects
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_get_employee',
                        _ajax_nonce: nonce,
                        id: employeeId
                    },
                    dataType: 'json',
                    success: function(empResponse) {
                        console.log('Employee response received:', empResponse);
                        
                        if (empResponse.success && empResponse.data.employee_projects) {
                            const employeeProjectIds = empResponse.data.employee_projects;
                            console.log('Employee has', employeeProjectIds.length, 'projects:', employeeProjectIds);
                            
                            // Now fetch all projects to get their names
                            $.ajax({
                                type: 'POST',
                                url: ajaxurl,
                                data: {
                                    action: 'helpdesk_get_projects',
                                    _ajax_nonce: nonce
                                },
                                dataType: 'json',
                                success: function(response) {
                                    console.log('All projects response received:', response);
                                    
                                    if (response.success && response.data.projects) {
                                        const allProjects = response.data.projects;
                                        console.log('Total projects in system:', allProjects.length);
                                        
                                        let html = '<option value="">-- Vyberte projekt --</option>';
                                        let genHtml = '';
                                        let addedCount = 0;
                                        
                                        allProjects.forEach(function(proj) {
                                            // Check if project ID is in employee's projects
                                            const isEmployeeProject = employeeProjectIds.includes(parseInt(proj.id)) || employeeProjectIds.includes(proj.id);
                                            
                                            if (isEmployeeProject) {
                                                console.log('Adding project:', proj.id, proj.zakaznicke_cislo);
                                                html += '<option value="' + proj.id + '">' + proj.zakaznicke_cislo + '</option>';
                                                genHtml += '<div style="margin-bottom: 8px;">';
                                                genHtml += '<label style="display: flex; align-items: center; gap: 8px; margin: 0; cursor: pointer;">';
                                                genHtml += '<input type="checkbox" name="standby-projects" value="' + proj.id + '" class="standby-project-checkbox">';
                                                genHtml += '<span>' + proj.zakaznicke_cislo + '</span>';
                                                genHtml += '</label></div>';
                                                addedCount++;
                                            }
                                        });
                                        
                                        console.log('Total matching projects:', addedCount);
                                        
                                        if (addedCount === 0) {
                                            console.warn('No matching projects found!');
                                            html += '<option disabled>Pracovník nemá priradené projekty</option>';
                                            genHtml = '<p style="color: red;">Pracovník nemá priradené projekty</p>';
                                        } else if (addedCount === 1) {
                                            console.log('Auto-checking single project checkbox');
                                            genHtml = genHtml.replace('type="checkbox"', 'type="checkbox" checked="checked"');
                                        }
                                        
                                        console.log('Setting HTML to #standby-project-select');
                                        $('#standby-project-select').html(html);
                                        console.log('Setting HTML to #standby-projects-list');
                                        $('#standby-projects-list').html(genHtml);
                                        
                                        console.log('Final HTML for select:', html.substring(0, 100));
                                    } else {
                                        console.error('Failed to load projects response:', response);
                                        $('#standby-project-select').html('<option disabled>Chyba pri načítaní projektov</option>');
                                        $('#standby-projects-list').html('<p style="color: red;">Chyba pri načítaní projektov</p>');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Error loading projects AJAX:', error, status, xhr.responseText);
                                    $('#standby-project-select').html('<option disabled>AJAX Chyba</option>');
                                    $('#standby-projects-list').html('<p style="color: red;">AJAX Chyba</p>');
                                }
                            });
                        } else {
                            console.error('Failed to load employee data:', empResponse);
                            $('#standby-project-select').html('<option disabled>Pracovník nemá projekty</option>');
                            $('#standby-projects-list').html('<p style="color: red;">Pracovník nemá projekty</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading employee AJAX:', error, status, xhr.responseText);
                        $('#standby-project-select').html('<option disabled>AJAX Chyba</option>');
                        $('#standby-projects-list').html('<p style="color: red;">AJAX Chyba</p>');
                    }
                });
            }

            // When project checkbox changes in generator section, load employees for that project
            $(document).on('change', '.standby-project-checkbox', function() {
                const projectIds = [];
                $('.standby-project-checkbox:checked').each(function() {
                    projectIds.push($(this).val());
                });
                
                if (projectIds.length === 0) {
                    $('#standby-rotation-employees').html('<option disabled>Najprv vyberte projekt</option>');
                    return;
                }

                console.log('Loading employees for projects:', projectIds);

                // Load employees for first selected project (to show who's involved)
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_get_project',
                        _ajax_nonce: nonce,
                        id: projectIds[0]
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data.employees) {
                            const employees = response.data.employees;
                            let html = '';
                            employees.forEach(emp => {
                                html += '<option value="' + emp.id + '">' + emp.meno_priezvisko + ' (' + emp.klapka + ')</option>';
                            });
                            
                            // Auto-select single employee
                            if (employees.length === 1) {
                                html = '<option value="' + employees[0].id + '" selected="selected">' + employees[0].meno_priezvisko + ' (' + employees[0].klapka + ')</option>';
                                console.log('Auto-selected single employee:', employees[0].meno_priezvisko);
                            }
                            
                            $('#standby-rotation-employees').html(html);
                            console.log('Loaded', employees.length, 'employees for project');
                        } else {
                            $('#standby-rotation-employees').html('<option disabled>Nepodarilo sa načítať pracovníkov</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading project employees:', error);
                        $('#standby-rotation-employees').html('<option disabled>Chyba pri načítaní</option>');
                    }
                });
            });

            // Add standby period
            $(document).on('click', '#btn-add-standby', function() {
                const employeeId = $('#employee-id').val();
                const projectId = $('#standby-project-select').val();
                const od = $('#standby-od').val();
                const $do = $('#standby-do').val();

                if (!employeeId || !projectId || !od || !$do) {
                    alert('Prosím vyplňte všetky polia');
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_save_standby',
                        _ajax_nonce: nonce,
                        employee_id: employeeId,
                        project_id: projectId,
                        date_from: od,
                        date_to: $do
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#standby-project-select').val('');
                            $('#standby-od').val('');
                            $('#standby-do').val('');
                            displayStandbyPeriods(response.data.standby_periods);
                        } else {
                            alert(response.data.message || 'Chyba pri uložení');
                        }
                    }
                });
            });

            // Display standby periods
            function displayStandbyPeriods(periods) {
                let html = '';
                console.log('displayStandbyPeriods called with periods:', periods);
                if (periods && periods.length > 0) {
                    html = '<table style="width: 100%; border-collapse: collapse;">';
                    
                    periods.forEach(function(period, idx) {
                        const projectName = period.zakaznicke_cislo ? period.zakaznicke_cislo : 'Neznámy projekt';
                        console.log('Processing period:', period, 'od:', period.pohotovost_od, 'do:', period.pohotovost_do);
                        
                        // Parse dates - handle string format YYYY-MM-DD
                        let odDate, doDate;
                        if (typeof period.pohotovost_od === 'string') {
                            const [year, month, day] = period.pohotovost_od.split('-');
                            odDate = new Date(year, month - 1, day);
                            console.log('Parsed od date:', odDate);
                        } else {
                            odDate = new Date(period.pohotovost_od);
                        }
                        
                        if (typeof period.pohotovost_do === 'string') {
                            const [year, month, day] = period.pohotovost_do.split('-');
                            doDate = new Date(year, month - 1, day);
                        } else {
                            doDate = new Date(period.pohotovost_do);
                        }
                        
                        // Calculate duration
                        const diffTime = Math.abs(doDate - odDate);
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        const diffWeeks = Math.floor(diffDays / 7);
                        const diffMonths = Math.floor(diffDays / 30.44);
                        
                        // Determine period label
                        let periodLabel = '';
                        if (diffWeeks >= 1 && diffWeeks < 5) {
                            periodLabel = diffWeeks + ' týž.';
                        } else if (diffMonths >= 1) {
                            periodLabel = diffMonths + ' mes.';
                        } else {
                            periodLabel = diffDays + ' dní';
                        }
                        
                        // Format dates for display
                        const odFormatted = formatDateForDisplay(odDate);
                        const doFormatted = formatDateForDisplay(doDate);
                        console.log('Formatted dates - od:', odFormatted, 'do:', doFormatted);
                        
                        html += '<tr style="border-bottom: 1px solid #ddd;">';
                        html += '<td style="padding: 8px;"><strong>' + projectName + '</strong></td>';
                        html += '<td style="padding: 8px; color: #666;">';
                        html += '<small>';
                        html += '<span style="font-weight: 600;">' + periodLabel + '</span><br>';
                        html += odFormatted + ' → ' + doFormatted;
                        html += '</small>';
                        html += '</td>';
                        html += '<td style="padding: 8px; text-align: right;">';
                        html += '<button type="button" class="button button-small button-link-delete btn-delete-standby" data-standby-id="' + period.id + '" style="color: #dc3545;">Zmazať</button>';
                        html += '</td></tr>';
                    });
                    html += '</table>';
                }
                $('#standby-periods-list').html(html);
            }
            
            // Format date for display (DD.MM.YYYY)
            function formatDateForDisplay(date) {
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return day + '.' + month + '.' + year;
            }

            // Delete standby period
            $(document).on('click', '.btn-delete-standby', function() {
                if (confirm('Naozaj chcete zmazať túto pohotovosť?')) {
                    const employeeId = $('#employee-id').val();
                    const standbyId = $(this).data('standby-id');

                    $.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: {
                            action: 'helpdesk_delete_standby',
                            _ajax_nonce: nonce,
                            employee_id: employeeId,
                            standby_id: standbyId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                displayStandbyPeriods(response.data.standby_periods);
                            } else {
                                alert(response.data.message || 'Chyba pri mazaní');
                            }
                        }
                    });
                }
            });

            // Refresh standby periods when opening employee for edit
            // Generate standby periods automatically
            $(document).on('click', '#btn-generate-standby', function() {
                const currentEmployeeId = $('#employee-id').val();
                if (!currentEmployeeId) {
                    alert('Pracovník musí byť uložený pred generovaním');
                    return;
                }

                const startDate = $('#standby-start-date').val();
                const intervalType = $('#standby-interval-type').val();
                const intervalCount = parseInt($('#standby-interval-count').val());
                const rotationEmployees = $('#standby-rotation-employees').val() || [];
                
                // Get selected projects from checkboxes
                const projectIds = [];
                $('.standby-project-checkbox:checked').each(function() {
                    projectIds.push($(this).val());
                });
                
                const numPeriods = parseInt($('#standby-num-periods').val());

                if (!startDate || projectIds.length < 1 || rotationEmployees.length < 1 || numPeriods < 1) {
                    alert('Vyplňte všetky polia a vyberte aspoň jeden projekt a jedného pracovníka na rotáciu');
                    return;
                }

                console.log('Generating standby rotation:', {
                    startDate, intervalType, intervalCount, rotationEmployees, projectIds, numPeriods
                });

                // Generate periods for each selected project
                let addedCount = 0;
                
                projectIds.forEach(projectId => {
                    const rotationPeriods = generateRotationPeriods(
                        startDate,
                        intervalType,
                        intervalCount,
                        rotationEmployees,
                        numPeriods
                    );

                    console.log('Generated rotation periods for project ' + projectId + ':', rotationPeriods);

                    // Add all generated periods for all employees
                    rotationPeriods.forEach(entry => {
                        const ajaxData = {
                            action: 'helpdesk_save_standby',
                            _ajax_nonce: nonce,
                            employee_id: parseInt(entry.employee_id),
                            project_id: parseInt(projectId),
                            date_from: entry.od,
                            date_to: entry.do
                        };
                        console.log('Sending AJAX data:', ajaxData);
                        $.ajax({
                            type: 'POST',
                            url: ajaxurl,
                            data: ajaxData,
                            dataType: 'json',
                            async: false, // Sequential to ensure order
                            success: function(response) {
                                if (response.success) {
                                    addedCount++;
                                    console.log('✓ Added standby for employee', entry.employee_id, 'project', projectId, ':', entry.od, '-', entry.do);
                                } else {
                                    console.error('✗ Failed to add period:', entry, response);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('✗ AJAX error adding period:', error, 'Response:', xhr.responseText);
                            }
                        });
                    });
                });

                alert('Vytvoreného ' + addedCount + ' periód pohotovosti pre ' + rotationEmployees.length + ' pracovníkov a ' + projectIds.length + ' projektov');
                
                // Reload standby periods for current employee
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_get_employee',
                        _ajax_nonce: nonce,
                        id: currentEmployeeId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data.standby_periods) {
                            displayStandbyPeriodsOnEdit(response.data.standby_periods);
                        }
                    }
                });
            });

            // Function to generate standby periods
            function generateRotationPeriods(startDateStr, intervalType, intervalCount, rotationEmployees, numPeriods) {
                const rotationEntries = [];
                let currentDate = new Date(startDateStr);
                let employeeIndex = 0;

                for (let i = 0; i < numPeriods; i++) {
                    const od = new Date(currentDate);
                    const do_date = new Date(currentDate);

                    // Calculate end date based on interval
                    if (intervalType === 'week') {
                        do_date.setDate(do_date.getDate() + 7 - 1);
                    } else if (intervalType === 'weeks') {
                        do_date.setDate(do_date.getDate() + (intervalCount * 7) - 1);
                    } else if (intervalType === 'month') {
                        do_date.setMonth(do_date.getMonth() + 1);
                        do_date.setDate(do_date.getDate() - 1);
                    } else if (intervalType === 'months') {
                        do_date.setMonth(do_date.getMonth() + intervalCount);
                        do_date.setDate(do_date.getDate() - 1);
                    }

                    // Add entry for current employee in rotation
                    const currentEmployeeId = rotationEmployees[employeeIndex];
                    rotationEntries.push({
                        employee_id: currentEmployeeId,
                        od: formatDate(od),
                        do: formatDate(do_date)
                    });

                    // Move to next employee in rotation
                    employeeIndex = (employeeIndex + 1) % rotationEmployees.length;

                    // Move to next period
                    if (intervalType === 'week') {
                        currentDate.setDate(currentDate.getDate() + 7);
                    } else if (intervalType === 'weeks') {
                        currentDate.setDate(currentDate.getDate() + (intervalCount * 7));
                    } else if (intervalType === 'month') {
                        currentDate.setMonth(currentDate.getMonth() + 1);
                    } else if (intervalType === 'months') {
                        currentDate.setMonth(currentDate.getMonth() + intervalCount);
                    }
                }

                return rotationEntries;
            }

            // Keep old function for backward compatibility
            function generateStandbyPeriods(startDateStr, intervalType, intervalCount, employeeCount, numPeriods) {
                const periods = [];
                let currentDate = new Date(startDateStr);

                for (let i = 0; i < numPeriods; i++) {
                    const od = new Date(currentDate);
                    const do_date = new Date(currentDate);

                    // Calculate end date based on interval
                    if (intervalType === 'week') {
                        do_date.setDate(do_date.getDate() + 7 - 1);
                    } else if (intervalType === 'weeks') {
                        do_date.setDate(do_date.getDate() + (intervalCount * 7) - 1);
                    } else if (intervalType === 'month') {
                        do_date.setMonth(do_date.getMonth() + 1);
                        do_date.setDate(do_date.getDate() - 1);
                    } else if (intervalType === 'months') {
                        do_date.setMonth(do_date.getMonth() + intervalCount);
                        do_date.setDate(do_date.getDate() - 1);
                    }

                    periods.push({
                        od: formatDate(od),
                        do: formatDate(do_date)
                    });

                    // Move to next period
                    if (intervalType === 'week') {
                        currentDate.setDate(currentDate.getDate() + 7);
                    } else if (intervalType === 'weeks') {
                        currentDate.setDate(currentDate.getDate() + (intervalCount * 7));
                    } else if (intervalType === 'month') {
                        currentDate.setMonth(currentDate.getMonth() + 1);
                    } else if (intervalType === 'months') {
                        currentDate.setMonth(currentDate.getMonth() + intervalCount);
                    }
                }

                return periods;
            }

            // Helper function to format date as YYYY-MM-DD
            function formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return year + '-' + month + '-' + day;
            }

            window.displayStandbyPeriodsOnEdit = function(periods) {
                if (periods && periods.length > 0) {
                    $('#employee-pohotovost-checkbox').prop('checked', true);
                    $('#employee-pohotovost-section').show();
                    displayStandbyPeriods(periods);
                } else {
                    $('#employee-pohotovost-checkbox').prop('checked', false);
                    $('#employee-pohotovost-section').hide();
                }
            };

            // Open standby modal
            $(document).on('click', '.helpdesk-btn-standby', function() {
                const employeeId = $(this).data('id');
                console.log('Standby button clicked for employee:', employeeId);
                
                // Load employee name
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_get_employee',
                        _ajax_nonce: nonce,
                        id: employeeId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const employee = response.data.employee;
                            $('#standby-employee-name').text(employee.meno_priezvisko);
                            $('#employee-id').val(employeeId);
                            loadStandbyProjectsFromData();
                            updateStandbyNumPeriodsLabel();
                            $('#helpdesk-standby-modal').show();
                        }
                    }
                });
            });

            // Update label for number of periods based on interval type
            function updateStandbyNumPeriodsLabel() {
                const intervalType = $('#standby-interval-type').val();
                const labelEl = $('#standby-num-periods-label');
                
                if (intervalType === 'week' || intervalType === 'weeks') {
                    labelEl.text('Počet týždňov na generovanie');
                } else if (intervalType === 'month' || intervalType === 'months') {
                    labelEl.text('Počet mesiacov na generovanie');
                } else {
                    labelEl.text('Počet periód');
                }
                console.log('Label updated for interval type:', intervalType);
            }

            // Close standby modal
            $(document).on('click', '#helpdesk-standby-modal .helpdesk-modal-close, #helpdesk-standby-modal .helpdesk-modal-close-btn', function() {
                console.log('Closing standby modal');
                $('#helpdesk-standby-modal').hide();
            });

            // Update label when interval type changes
            $(document).on('change', '#standby-interval-type', function() {
                updateStandbyNumPeriodsLabel();
            });

            // Open vacation modal
            $(document).on('click', '.helpdesk-btn-vacation', function() {
                const employeeId = $(this).data('id');
                console.log('Vacation button clicked for employee:', employeeId);
                
                // Load employee vacation data
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_get_employee_vacation',
                        _ajax_nonce: nonce,
                        id: employeeId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const vacation = response.data;
                            $('#vacation-employee-id').val(employeeId);
                            
                            if (vacation.nepritomnost_od && vacation.nepritomnost_do) {
                                $('#vacation-od').val(vacation.nepritomnost_od);
                                $('#vacation-do').val(vacation.nepritomnost_do);
                                $('.helpdesk-btn-remove-vacation').show();
                            } else {
                                $('#vacation-od').val('');
                                $('#vacation-do').val('');
                                $('.helpdesk-btn-remove-vacation').hide();
                            }
                            
                            $('#helpdesk-vacation-modal').show();
                        }
                    },
                    error: function() {
                        $('#vacation-employee-id').val(employeeId);
                        $('#vacation-od').val('');
                        $('#vacation-do').val('');
                        $('.helpdesk-btn-remove-vacation').hide();
                        $('#helpdesk-vacation-modal').show();
                    }
                });
            });

            // Save vacation
            $(document).on('submit', '#helpdesk-vacation-form', function(e) {
                e.preventDefault();
                
                const employeeId = $('#vacation-employee-id').val();
                const vacationOd = $('#vacation-od').val();
                const vacationDo = $('#vacation-do').val();
                
                if (!vacationOd || !vacationDo) {
                    alert('Vyplňte obe dátumy');
                    return;
                }
                
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_save_employee_vacation',
                        _ajax_nonce: nonce,
                        id: employeeId,
                        nepritomnost_od: vacationOd,
                        nepritomnost_do: vacationDo
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message || 'Nepritomnosť bola uložená');
                            $('#helpdesk-vacation-modal').hide();
                            location.reload();
                        } else {
                            alert(response.data.message || 'Chyba');
                        }
                    },
                    error: function() {
                        alert('Chyba pri ukladaní nepritomnosti');
                    }
                });
            });

            // Remove vacation
            $(document).on('click', '.helpdesk-btn-remove-vacation', function() {
                if (!confirm('Naozaj chcete vymazať dovolenku?')) {
                    return;
                }
                
                const employeeId = $('#vacation-employee-id').val();
                
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_remove_employee_vacation',
                        _ajax_nonce: nonce,
                        id: employeeId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message || 'Nepritomnosť bola vymazaná');
                            $('#helpdesk-vacation-modal').hide();
                            location.reload();
                        } else {
                            alert(response.data.message || 'Chyba');
                        }
                    },
                    error: function() {
                        alert('Chyba pri mazaní nepritomnosti');
                    }
                });
            });

            // Close vacation modal
            $(document).on('click', '#helpdesk-vacation-modal .helpdesk-modal-close, #helpdesk-vacation-modal .helpdesk-modal-close-btn', function() {
                $('#helpdesk-vacation-modal').hide();
            });
        }

        let currentEmployees = []; // Module-level variable for project edit modal

        function initProjects() {
            // Načítaj a zobraz pracovníkov v tabuľke
            function loadProjectEmployeesDisplay() {
                // Načítaj všetky projekty s pracovníkmi v jednom AJAX requeste
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_get_all_projects_with_employees',
                        _ajax_nonce: nonce
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Projects with employees response:', response);
                        if (response.success && response.data.projects) {
                            response.data.projects.forEach(project => {
                                // Hľadaj span s class "project-employees-display" a data-project-id
                                const $span = $('.project-employees-display[data-project-id="' + project.id + '"]');
                                let empText = 'žiadni';
                                
                                if (project.employees && project.employees.length > 0) {
                                    const today = new Date().toISOString().split('T')[0];
                                    console.log('Processing project ' + project.id + ', employees:', project.employees);
                                    empText = project.employees.map(e => {
                                        console.log('Employee ' + e.meno_priezvisko + ' - nepritomnost_od:', e.nepritomnost_od, 'nepritomnost_do:', e.nepritomnost_do, 'has_standby:', e.has_standby);
                                        let name = '';
                                        if (e.is_hlavny == 1 || e.is_hlavny === '1' || e.is_hlavny === true) {
                                            name += '🟢 ';
                                        }
                                        name += e.meno_priezvisko;
                                        if (e.skratka) {
                                            name += ' (' + e.skratka + ')';
                                        }
                                        
                                        // Check if employee has standby TODAY
                                        const hasStandbyToday = e.has_standby === 1 || e.has_standby === true || e.has_standby === '1';
                                        
                                        // Check if employee is on absence (nepritomny)
                                        const isAbsent = e.nepritomnost_od && e.nepritomnost_do && 
                                            today >= e.nepritomnost_od && today <= e.nepritomnost_do;
                                        
                                        console.log('Employee ' + e.meno_priezvisko + ' - today: ' + today + ', hasStandbyToday:', hasStandbyToday + ', isAbsent:', isAbsent);
                                        
                                        // Apply standby indicator if has standby today
                                        if (hasStandbyToday) {
                                            name = '📱 ' + name;
                                        }
                                        
                                        if (isAbsent) {
                                            name = '🏖️ <del style="color: #999;">' + name + '</del>';
                                        }
                                        
                                        return name;
                                    }).join(', ');
                                }
                                
                                $span.html(empText);
                                
                                // Ak má projekt nemenit flag, zafarbí na červeno
                                if (project.nemenit == 1 || project.nemenit === true) {
                                    $span.css('color', '#d32f2f');
                                    $span.css('font-weight', 'bold');
                                }
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Error loading projects with employees:', error);
                        // Fallback: Just display "žiadni" for all (data je v PHP render)
                        $('.project-employees-display').text('žiadni');
                    }
                });
            }
            
            loadProjectEmployeesDisplay();
            
            // Refresh projects list on init to show all projects with employees
            refreshProjectsList();

            // Helper function to apply projects filter
            function applyProjectsFilter(searchText) {
                const searchLower = searchText.toLowerCase();
                const columnIndices = [0, 1, 2, 3, 4, 5]; // All columns
                
                $('#helpdesk-projects-table tbody tr').each(function() {
                    const $row = $(this);
                    const cells = $row.find('td');
                    let found = false;
                    
                    columnIndices.forEach(index => {
                        if (cells.eq(index).text().toLowerCase().includes(searchLower)) {
                            found = true;
                        }
                    });
                    
                    $row.toggle(found);
                });
            }

            // Function to sort projects table by zakaznicke_cislo
            function refreshProjectsList() {
                // Reload all projects from server and refresh the table
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_get_all_projects_with_employees',
                        _ajax_nonce: nonce
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data.projects) {
                            const $tbody = $('#helpdesk-projects-table tbody');
                            $tbody.empty();
                            
                            if (response.data.projects.length === 0) {
                                $tbody.html('<tr><td colspan="4" class="center">Žádné projekty nebyly nalezeny.</td></tr>');
                                return;
                            }
                            
                            response.data.projects.forEach(project => {
                                let empText = 'žiadni';
                                if (project.employees && project.employees.length > 0) {
                                    empText = project.employees.map(e => {
                                        let name = '';
                                        if (e.is_hlavny == 1 || e.is_hlavny === '1' || e.is_hlavny === true) {
                                            name += '🟢 ';
                                        }
                                        name += e.meno_priezvisko;
                                        if (e.skratka) {
                                            name += ' (' + e.skratka + ')';
                                        }
                                        return name;
                                    }).join(', ');
                                }
                                
                                // Apply red color if nemenit is set
                                const empColor = (project.nemenit == 1 || project.nemenit === true) ? 'color: #d32f2f; font-weight: bold;' : '';
                                
                                const $row = $('<tr>').attr('data-project-id', project.id);
                                
                                // Use jQuery text() for safe HTML encoding
                                const $zakaznickeCell = $('<td>').addClass('column-zakaznicke-cislo').text(project.zakaznicke_cislo);
                                const $komunikacieCell = $('<td>').addClass('column-spobsob-komunikacie').text(project.hd_kontakt || '');
                                const $pmCell = $('<td>').addClass('column-pm').text(project.pm_name || '');
                                const $slaCell = $('<td>').addClass('column-sla').text(project.sla_name || '');
                                const $pracovniciCell = $('<td>').addClass('column-pracovnici');
                                $pracovniciCell.html(`<span class="project-employees-display" data-project-id="${project.id}" style="${empColor}"></span>`);
                                $pracovniciCell.find('.project-employees-display').text(empText);
                                
                                const $actionsCell = $('<td>').addClass('column-actions').attr('style', 'text-align: center; font-size: 18px;');
                                const $editBtn = $('<button>').addClass('button button-small helpdesk-btn-edit')
                                    .attr({
                                        'data-id': project.id,
                                        'title': 'Upraviť',
                                        'style': 'border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);'
                                    })
                                    .text('✏️');
                                const $deleteBtn = $('<button>').addClass('button button-small button-link-delete helpdesk-btn-delete')
                                    .attr({
                                        'data-id': project.id,
                                        'title': 'Zmazať',
                                        'style': 'border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);'
                                    })
                                    .text('🗑️');
                                
                                $actionsCell.append($editBtn, $deleteBtn);
                                $row.append($zakaznickeCell, $komunikacieCell, $pmCell, $slaCell, $pracovniciCell, $actionsCell);
                                $tbody.append($row);
                            });
                            
                            // Bind event handlers to new elements
                            // (handlers already bound using $(document).on() in refreshProjectsList)
                            
                            // Re-apply current filter after refresh
                            const currentSearchValue = $('#helpdesk-projects-search').val() || '';
                            if (currentSearchValue.trim() !== '') {
                                // Manually apply filter to the new rows
                                applyProjectsFilter(currentSearchValue);
                            }
                        }
                    },
                    error: function() {
                        // Fallback: just sort existing rows
                        sortProjectsByCustomerNumber();
                    }
                });
            }
            
            function bindProjectTableEvents() {
                // Event handlers are already bound using $(document).on() in initProjects
                // This function is kept for compatibility
            }

            function sortProjectsByCustomerNumber() {
                const $table = $('#helpdesk-projects-table tbody');
                const rows = $table.find('tr').toArray();
                
                // Sort rows by zakaznicke_cislo (first column)
                rows.sort((a, b) => {
                    const textA = $(a).find('.column-zakaznicke-cislo').text().trim().toLowerCase();
                    const textB = $(b).find('.column-zakaznicke-cislo').text().trim().toLowerCase();
                    
                    // Try to parse as numbers if they look like numbers
                    const numA = parseFloat(textA);
                    const numB = parseFloat(textB);
                    
                    if (!isNaN(numA) && !isNaN(numB)) {
                        return numA - numB;
                    }
                    
                    // Otherwise, sort alphabetically using Slovak collation
                    return textA.localeCompare(textB, 'sk');
                });
                
                // Re-append sorted rows to the table
                rows.forEach(row => {
                    $table.append(row);
                });
            }

            $(document).on('click', '.helpdesk-btn-new-project', function() {
                $('#project-id').val('');
                $('#project-employees-selected').val('');
                $('#helpdesk-project-form')[0].reset();
                $('#project-modal-title').text('Pridať projekt');
                $('#project-employees-display-modal').html('<span style="color: #999; font-size: 13px;">Bude prázdny</span>');
                $('#helpdesk-project-modal').show();
                $('.error-message').text('');
            });

            $(document).on('click', '#helpdesk-project-modal .helpdesk-modal-close, #helpdesk-project-modal .helpdesk-modal-close-btn', function() {
                $('#helpdesk-project-modal').hide();
            });

            $(document).on('click', '#btn-reset-projects-filter', function() {
                $('#helpdesk-projects-search').val('');
                $('#helpdesk-projects-table tbody tr').show();
            });

            $(document).on('click', '#project-edit-employees-btn', function() {
                const projectId = $('#project-id').val();
                const projectEmployeesJson = $('#project-employees-json').val();
                let allEmployees = [];
                
                if (projectEmployeesJson) {
                    try {
                        allEmployees = JSON.parse(projectEmployeesJson);
                    } catch(e) {
                        console.error('Failed to parse employees JSON:', e);
                    }
                }
                
                // Load current employees for this project
                let currentEmployeesData = []; // Store full employee objects with is_hlavny
                if (projectId) {
                    $.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: {
                            action: 'helpdesk_get_project',
                            _ajax_nonce: nonce,
                            id: projectId
                        },
                        dataType: 'json',
                        async: false,
                        success: function(response) {
                            if (response.success && response.data.employees) {
                                currentEmployeesData = response.data.employees; // Store full array
                            }
                        }
                    });
                }
                
                // Render employees list in modal - assigned first, then unassigned sorted alphabetically
                let html = '';
                
                // Get array of assigned employee IDs
                const assignedIds = currentEmployeesData.map(e => e.id);
                
                // Separate assigned and unassigned employees
                const assignedEmps = allEmployees.filter(emp => assignedIds.includes(emp.id));
                const unassignedEmps = allEmployees.filter(emp => !assignedIds.includes(emp.id));
                
                // Sort unassigned employees alphabetically by name
                unassignedEmps.sort((a, b) => {
                    const nameA = (a.meno_priezvisko || '').toLowerCase();
                    const nameB = (b.meno_priezvisko || '').toLowerCase();
                    return nameA.localeCompare(nameB, 'sk');
                });
                
                // Render assigned employees first
                if (assignedEmps.length > 0) {
                    html += '<div style="padding: 8px 6px; background: #f9f9f9; font-weight: bold; color: #666; font-size: 12px; margin-bottom: 4px;">✓ PRIRADENÍ PRACOVNÍCI (' + assignedEmps.length + ')</div>';
                    assignedEmps.forEach(emp => {
                        const empData = currentEmployeesData.find(e => e.id == emp.id);
                        const isHlavny = empData && (empData.is_hlavny == 1 || empData.is_hlavny === '1' || empData.is_hlavny === true);
                        console.log('Employee:', emp.meno_priezvisko, 'isHlavny from DB:', empData.is_hlavny, 'Interpreted as:', isHlavny);
                        html += '<div style="display: flex; align-items: center; gap: 8px; padding: 6px; border-radius: 3px; background: #fffacd;">';
                        html += '<input type="checkbox" class="emp-checkbox" value="' + emp.id + '" checked>';
                        html += '<label style="margin: 0; cursor: pointer; flex: 1; display: flex; align-items: center; gap: 6px;">';
                        html += '<span style="color: green; font-size: 14px; ' + (isHlavny ? '' : 'display: none;') + '" class="hlavny-badge" title="Hlavný">🟢</span>';
                        html += emp.meno_priezvisko + ' (' + (emp.klapka || '') + ')';
                        html += '</label>';
                        html += '<input type="checkbox" class="emp-hlavny" data-emp-id="' + emp.id + '" ' + (isHlavny ? 'checked' : '') + ' title="Je hlavný">';
                        html += '</div>';
                    });
                }
                
                // Render unassigned employees
                if (unassignedEmps.length > 0) {
                    if (assignedEmps.length > 0) {
                        html += '<div style="padding: 8px 6px; background: #f9f9f9; font-weight: bold; color: #666; font-size: 12px; margin: 8px 0 4px 0;">OSTATNÍ PRACOVNÍCI (' + unassignedEmps.length + ')</div>';
                    }
                    unassignedEmps.forEach(emp => {
                        html += '<div style="display: flex; align-items: center; gap: 8px; padding: 6px; border-radius: 3px;">';
                        html += '<input type="checkbox" class="emp-checkbox" value="' + emp.id + '">';
                        html += '<label style="margin: 0; cursor: pointer; flex: 1; display: flex; align-items: center; gap: 6px;">';
                        html += '<span style="color: green; font-size: 14px; display: none;" class="hlavny-badge" title="Hlavný">🟢</span>';
                        html += emp.meno_priezvisko + ' (' + (emp.klapka || '') + ')';
                        html += '</label>';
                        html += '<input type="checkbox" class="emp-hlavny" data-emp-id="' + emp.id + '" title="Je hlavný">';
                        html += '</div>';
                    });
                }
                
                $('#project-employees-list').html(html);
                $('#helpdesk-project-employees-modal').show();
            });

            $(document).on('change', '#helpdesk-project-employees-modal .emp-hlavny', function() {
                const $badge = $(this).closest('div').find('.hlavny-badge');
                if ($(this).prop('checked')) {
                    $badge.show();
                } else {
                    $badge.hide();
                }
            });

            $(document).on('click', '#helpdesk-project-employees-modal .helpdesk-modal-close, #helpdesk-project-employees-modal .helpdesk-modal-close-btn', function() {
                $('#helpdesk-project-employees-modal').hide();
            });

            $(document).on('keyup', '#project-employees-search', function() {
                const searchText = $(this).val().toLowerCase();
                $('#project-employees-list > div').each(function() {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(searchText === '' || text.includes(searchText));
                });
            });

            $(document).on('click', '#project-employees-toggle', function() {
                const allChecked = $('#project-employees-list .emp-checkbox').length === $('#project-employees-list .emp-checkbox:checked').length;
                $('#project-employees-list .emp-checkbox').prop('checked', !allChecked);
            });

            $(document).on('click', '#project-save-employees-btn', function() {
                // Zbierz vybrané checkboxy so stavom is_hlavny
                let selected = [];
                $('#project-employees-list .emp-checkbox:checked').each(function() {
                    const empId = $(this).val();
                    const $empHlavnyCheckbox = $(this).closest('div').find('.emp-hlavny');
                    const isHlavny = $empHlavnyCheckbox.prop('checked') ? 1 : 0;
                    console.log('Emp ID:', empId, 'Is hlavny:', isHlavny, 'Checkbox checked:', $empHlavnyCheckbox.prop('checked'));
                    selected.push({
                        id: empId,
                        is_hlavny: isHlavny
                    });
                });
                
                console.log('Final selected array:', JSON.stringify(selected));
                
                // Ulož ich do skrytého poľa
                $('#project-employees-selected').val(JSON.stringify(selected));
                
                // Aktualizuj zobrazenie v main modáli
                const projectEmployeesJson = $('#project-employees-json').val();
                let allEmployees = [];
                try {
                    allEmployees = JSON.parse(projectEmployeesJson);
                } catch(e) {}
                
                let displayText = 'žiadni';
                if (selected.length > 0) {
                    const selectedNames = allEmployees
                        .filter(e => selected.find(s => String(s.id) === String(e.id)))
                        .map(e => {
                            let name = e.meno_priezvisko;
                            const emp = selected.find(s => String(s.id) === String(e.id));
                            if (emp && emp.is_hlavny) {
                                name += ' 🟢';
                            }
                            if (e.skratka) {
                                name += ' (' + e.skratka + ')';
                            }
                            return name;
                        });
                    displayText = selectedNames.join(', ');
                }
                
                $('#project-employees-display-modal').html(displayText);
                
                // Ulož vybrané do skrytého poľa (aby sa poslali s formulárom)
                // V skutočnosti ich odošleme v AJAX kóde
                $('#helpdesk-project-employees-modal').hide();
            });

            $(document).on('submit', '#helpdesk-project-form', function(e) {
                e.preventDefault();
                const id = $('#project-id').val();
                
                // Zbierz vybrané pracovníkov z skrytého poľa alebo priamo z checkboxov
                let employees = [];
                const selectedJson = $('#project-employees-selected').val();
                
                if (selectedJson) {
                    try {
                        employees = JSON.parse(selectedJson);
                    } catch(e) {
                        console.error('Failed to parse selected employees:', e);
                    }
                }
                
                // Ak je pole prázdne, skús zbierz z checkboxov (fallback)
                if (employees.length === 0) {
                    $('#project-employees-list .emp-checkbox:checked').each(function() {
                        const empId = $(this).val();
                        const $empHlavnyCheckbox = $(this).closest('div').find('.emp-hlavny');
                        const isHlavny = $empHlavnyCheckbox.prop('checked') ? 1 : 0;
                        employees.push({
                            id: empId,
                            is_hlavny: isHlavny
                        });
                    });
                }
                
                const projectData = {
                    zakaznicke_cislo: $('#project-zakaznicke_cislo').val(),
                    hd_kontakt: $('#project-hd_kontakt').val(),
                    pm_manazer_id: $('#project-pm_manazer_id').val() ? parseInt($('#project-pm_manazer_id').val()) : null,
                    sla_manazer_id: $('#project-sla_manazer_id').val() ? parseInt($('#project-sla_manazer_id').val()) : null,
                    poznamka: $('#project-poznamka').val(),
                    nemenit: $('#project-nemenit').prop('checked') ? 1 : 0
                };
                
                const ajaxData = {
                    action: 'helpdesk_save_project',
                    _ajax_nonce: nonce,
                    id: id,
                    project_data: JSON.stringify(projectData),
                    employees: JSON.stringify(employees)
                };
                
                console.log('AJAX Data to be sent:', ajaxData);
                console.log('Employees array:', employees);

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: ajaxData,
                    dataType: 'json',
                    success: function(response) {
                        $('.error-message').text('');
                        if (response.success) {
                            alert(response.data.message);
                            // Refresh the entire projects list and close modal
                            refreshProjectsList();
                            $('#helpdesk-project-modal').hide();
                        } else {
                            if (response.data.errors) {
                                $.each(response.data.errors, function(key, msg) {
                                    $('#error-' + key).text(msg);
                                });
                                alert('Validačné chyby - pozri vyplnené polia');
                            } else {
                                alert(response.data.message || 'Neznáma chyba pri uložení');
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('AJAX chyba pri uložení projektu: ' + error);
                    }
                });
            });

            $(document).on('click', '#helpdesk-projects-table .helpdesk-btn-delete', function() {
                if (confirm('Naozaj chcete zmazať tento projekt?')) {
                    const id = $(this).data('id');
                    $.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: {
                            action: 'helpdesk_delete_project',
                            _ajax_nonce: nonce,
                            id: id
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                alert(response.data.message);
                                location.reload();
                            } else {
                                alert(response.data.message || 'Chyba pri mazaní');
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('AJAX chyba pri mazaní: ' + error);
                        }
                    });
                }
            });

            // Delete delete button handler (not edit - that's in bindProjectTableEvents)

            // Search in employees - DYNAMIC (keyup + input for better compatibility)
            $(document).on('keyup input', '#project-employees-search', function() {
                const query = ($(this).val() || '').toLowerCase().trim();
                console.log('Search query:', query);
                console.log('Total employee items found:', $('#project-employees-list .employee-item').length);
                
                if (query === '') {
                    // No search - show only checked employees
                    hideUncheckedEmployees();
                    return;
                }
                
                // Searching - show all matching
                let foundCount = 0;
                const items = $('#project-employees-list .employee-item');
                console.log('Searching in', items.length, 'items');
                
                items.each(function(idx) {
                    const $item = $(this);
                    const empName = $item.find('.employee-checkbox').data('emp-name') || '';
                    const empLabel = $item.find('.employee-checkbox label').first().text() || '';
                    
                    console.log('Item ' + idx + ':', empName, 'label:', empLabel);
                    
                    // Check both data-emp-name and text content
                    const matchesName = empName && empName.includes(query);
                    const matchesLabel = empLabel && empLabel.toLowerCase().includes(query);
                    
                    if (matchesName || matchesLabel) {
                        $item.show();
                        foundCount++;
                        console.log('✓ Match found');
                    } else {
                        $item.hide();
                    }
                });
                console.log('Total found:', foundCount);
            });
            
            // Toggle all/checked button
            $(document).on('click', '#project-employees-toggle', function() {
                if ($(this).hasClass('showing-all')) {
                    // Showing all, go back to checked only
                    hideUncheckedEmployees();
                    $(this).removeClass('showing-all').text('Všetci');
                } else {
                    // Showing checked only, show all items
                    $('#project-employees-list .employee-item').show();
                    $(this).addClass('showing-all').text('Iba vybraní');
                }
                $('#project-employees-search').val('');
            });
        }

        function loadBugCodesAndProducts() {
            // Load bug codes
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_get_bug_codes',
                    _ajax_nonce: nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.codes) {
                        let html = '<option value="">-- Vyberte kód --</option>';
                        response.data.codes.forEach(function(code) {
                            html += '<option value="' + code.kod + '" data-popis="' + (code.popis || '') + '" data-uplny_popis="' + (code.uplny_popis || '') + '" data-riesenie="' + (code.uplny_popis || '') + '">' + code.kod + '</option>';
                        });
                        $('#bug-kod_chyby').html(html);
                    }
                }
            });

            // Load products
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_get_products',
                    _ajax_nonce: nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.products) {
                        let html = '<option value="">-- Vyberte produkt --</option>';
                        response.data.products.forEach(function(product) {
                            html += '<option value="' + product.id + '">' + product.nazov + (product.popis ? ' - ' + product.popis : '') + '</option>';
                        });
                        $('#bug-produkt').html(html);
                    }
                }
            });

            // Edit project button - delegated event handler for dynamically created rows
            $(document).on('click', '#helpdesk-projects-table .helpdesk-btn-edit', function() {
                const id = $(this).data('id');
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_get_project',
                        _ajax_nonce: nonce,
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const project = response.data.project || response.data;
                            const employees = response.data.employees || []; // Get employees from response
                            
                            $('#project-id').val(project.id);
                            $('#project-zakaznicke_cislo').val(project.zakaznicke_cislo || '');
                            $('#project-hd_kontakt').val(project.hd_kontakt || '');
                            $('#project-pm_manazer_id').val(project.pm_manazer_id || '');
                            $('#project-sla_manazer_id').val(project.sla_manazer_id || '');
                            $('#project-poznamka').val(project.poznamka || '');
                            $('#project-nemenit').prop('checked', project.nemenit == 1);
                            $('#project-modal-title').text('Upraviť projekt');
                            
                            // Store selected employees with is_hlavny info in hidden field
                            let empData = [];
                            let empDisplay = 'žádný pracovník';
                            if (employees && employees.length > 0) {
                                empData = employees.map(e => ({
                                    id: e.id,
                                    is_hlavny: e.is_hlavny || 0
                                }));
                                empDisplay = employees.map(e => {
                                    let name = '';
                                    if (e.is_hlavny == 1 || e.is_hlavny === '1' || e.is_hlavny === true) {
                                        name += '🟢 ';
                                    }
                                    name += e.meno_priezvisko;
                                    if (e.skratka) {
                                        name += ' (' + e.skratka + ')';
                                    }
                                    
                                    // Check if employee is on absence (nepritomny)
                                    const today = new Date().toISOString().split('T')[0];
                                    const isAbsent = e.nepritomnost_od && e.nepritomnost_do && 
                                        today >= e.nepritomnost_od && today <= e.nepritomnost_do;
                                    
                                    if (isAbsent) {
                                        name = '<span style="text-decoration: line-through; color: #999;">🏖️ ' + name + '</span>';
                                    }
                                    
                                    return name;
                                }).join(', ');
                            }
                            $('#project-employees-selected').val(JSON.stringify(empData));
                            $('#project-employees-display-modal').html(empDisplay);
                            
                            currentEmployees = employees;
                            $('#helpdesk-project-modal').show();
                            $('.error-message').text('');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('AJAX chyba pri načítaní projektu: ' + error);
                    }
                });
            });
        }

        function initBugs() {
            // Load bug codes and products
            loadBugCodesAndProducts();

            // Filter functionality
            function filterBugsTable() {
                const searchText = $('#filter-bugs-search').val().toLowerCase();
                const selectedOS = $('#filter-bugs-os').val();
                const selectedProduct = $('#filter-bugs-product').val();

                $('#helpdesk-bugs-table tbody tr').each(function() {
                    const $row = $(this);
                    const kod = $row.find('.column-kod').text().toLowerCase();
                    const nazov = $row.find('.column-name').text().toLowerCase();
                    const os = $row.data('os');
                    const product = $row.data('product');

                    let show = true;

                    // Search filter
                    if (searchText && !kod.includes(searchText) && !nazov.includes(searchText)) {
                        show = false;
                    }

                    // OS filter
                    if (selectedOS && os != selectedOS) {
                        show = false;
                    }

                    // Product filter
                    if (selectedProduct && product != selectedProduct) {
                        show = false;
                    }

                    $row.toggle(show);
                });
            }

            // Bind filter events
            $(document).on('input change', '#filter-bugs-search, #filter-bugs-os, #filter-bugs-product', function() {
                filterBugsTable();
            });

            // Reset filters
            $(document).on('click', '#btn-reset-bugs-filters', function() {
                $('#filter-bugs-search').val('');
                $('#filter-bugs-os').val('');
                $('#filter-bugs-product').val('');
                filterBugsTable();
            });

            $(document).on('click', '.helpdesk-btn-new-bug', function() {
                $('#bug-id').val('');
                $('#helpdesk-bug-form')[0].reset();
                $('#bug-modal-title').text('Pridať riešenie');
                $('#helpdesk-bug-modal').show();
                $('.error-message').text('');
            });

            $(document).on('click', '#helpdesk-bug-modal .helpdesk-modal-close, #helpdesk-bug-modal .helpdesk-modal-close-btn', function() {
                $('#helpdesk-bug-modal').hide();
            });

            // Auto-fill nazov and popis when kod_chyby is selected
            $(document).on('change', '#bug-kod_chyby', function() {
                const selectedOption = $(this).find('option:selected');
                const popis = selectedOption.data('popis');
                const uplny_popis = selectedOption.data('uplny_popis');

                // Store uplny_popis in hidden input for later use
                $('#bug-uplny_popis').val(uplny_popis || '');
                console.log('kod_chyby changed, uplny_popis:', uplny_popis);

                // Auto-fill fields if they are empty
                if (popis && $('#bug-nazov').val() === '') {
                    $('#bug-nazov').val($(this).val() + ' - ' + popis);
                }
                // Auto-fill Popis with uplny_popis if empty
                if (uplny_popis && $('#bug-popis').val() === '') {
                    $('#bug-popis').val(uplny_popis);
                }
            });

            // Auto-preset signature when product is selected
            $(document).on('change', '#bug-produkt', function() {
                const produktId = $(this).val();
                
                if (!produktId) {
                    // Clear signature if no product selected
                    $('#bug-podpis').val('');
                    return;
                }

                // Get APHD worker from settings via AJAX
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_get_ap_hd_worker',
                        _ajax_nonce: nonce
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('AP HD Worker response:', response);
                        
                        if (response.success && response.data.pracovnik_id) {
                            const pracovnikId = response.data.pracovnik_id;
                            
                            // Get signature by employee and product
                            $.ajax({
                                type: 'POST',
                                url: ajaxurl,
                                data: {
                                    action: 'helpdesk_get_signature_by_employee_product',
                                    _ajax_nonce: nonce,
                                    pracovnik_id: pracovnikId,
                                    produkt_id: produktId
                                },
                                dataType: 'json',
                                success: function(sigResponse) {
                                    console.log('Signature response:', sigResponse);
                                    
                                    if (sigResponse.success && sigResponse.data && sigResponse.data.id) {
                                        // Auto-select the signature
                                        $('#bug-podpis').val(sigResponse.data.id);
                                        console.log('Signature auto-preset to:', sigResponse.data.id);
                                    } else {
                                        console.log('No signature found for employee and product');
                                        $('#bug-podpis').val('');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Error getting signature:', error);
                                    $('#bug-podpis').val('');
                                }
                            });
                        } else {
                            console.log('No APHD worker configured');
                            $('#bug-podpis').val('');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error getting AP HD worker:', error);
                    }
                });
            });

            // Copy solution description to clipboard with optional signature
            $(document).on('click', '#btn-copy-solution', function() {
                let textToCopy = $('#bug-riesenie').val();
                if (!textToCopy.trim()) {
                    alert('Popis riešenia je prázdny');
                    return;
                }
                
                // Check if a signature is selected
                const selectedSignatureId = $('#bug-podpis').val();
                if (selectedSignatureId) {
                    // Get all signatures data from the select element
                    const signaturesData = $('#bug-podpis').data('signatures');
                    if (signaturesData && Array.isArray(signaturesData)) {
                        const selectedSignature = signaturesData.find(sig => sig.id === parseInt(selectedSignatureId));
                        if (selectedSignature && selectedSignature.text_podpisu) {
                            // Append signature text to clipboard
                            textToCopy = textToCopy + '\n\n' + selectedSignature.text_podpisu;
                        }
                    }
                }
                
                // Copy to clipboard
                navigator.clipboard.writeText(textToCopy).then(function() {
                    alert('Skopírované do clipboardu');
                }).catch(function(err) {
                    // Fallback for older browsers
                    const textarea = document.createElement('textarea');
                    textarea.value = textToCopy;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    alert('Skopírované do clipboardu');
                });
            });

            // Copy solution description step 2 to clipboard with optional signature
            $(document).on('click', '#btn-copy-solution-2', function() {
                let textToCopy = $('#bug-riesenie-2').val();
                if (!textToCopy.trim()) {
                    alert('Popis riešenia - 2. krok je prázdny');
                    return;
                }
                
                // Check if a signature is selected
                const selectedSignatureId = $('#bug-podpis').val();
                if (selectedSignatureId) {
                    // Get all signatures data from the select element
                    const signaturesData = $('#bug-podpis').data('signatures');
                    if (signaturesData && Array.isArray(signaturesData)) {
                        const selectedSignature = signaturesData.find(sig => sig.id === parseInt(selectedSignatureId));
                        if (selectedSignature && selectedSignature.text_podpisu) {
                            // Append signature text to clipboard
                            textToCopy = textToCopy + '\n\n' + selectedSignature.text_podpisu;
                        }
                    }
                }
                
                // Copy to clipboard
                navigator.clipboard.writeText(textToCopy).then(function() {
                    alert('Skopírované do clipboardu');
                }).catch(function(err) {
                    // Fallback for older browsers
                    const textarea = document.createElement('textarea');
                    textarea.value = textToCopy;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    alert('Skopírované do clipboardu');
                });
            });

            $(document).on('submit', '#helpdesk-bug-form', function(e) {
                e.preventDefault();
                const id = $('#bug-id').val();

                console.log('Bug form submitted');

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_save_bug',
                        _ajax_nonce: nonce,
                        id: id,
                        nazov: $('#bug-nazov').val(),
                        popis: $('#bug-popis').val(),
                        kod_chyby: $('#bug-kod_chyby').val(),
                        produkt: $('#bug-produkt').val(),
                        riesenie: $('#bug-riesenie').val(),
                        riesenie_2: $('#bug-riesenie-2').val(),
                        podpis_id: $('#bug-podpis').val(),
                        tagy: $('#bug-tagy').val()
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Response:', response);
                        $('.error-message').text('');
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            if (response.data.errors) {
                                $.each(response.data.errors, function(key, msg) {
                                    $('#error-' + key).text(msg);
                                });
                            } else {
                                alert(response.data.message || 'Chyba pri uložení');
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error, 'Status:', status, 'Response:', xhr.responseText);
                        alert('AJAX chyba pri uložení chyby: ' + error + '\n' + xhr.responseText);
                    }
                });
            });

            $(document).on('click', '#helpdesk-bugs-table .helpdesk-btn-delete-bug', function() {
                if (confirm('Naozaj chcete zmazať túto chybu?')) {
                    const id = $(this).data('id');
                    $.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: {
                            action: 'helpdesk_delete_bug',
                            _ajax_nonce: nonce,
                            id: id
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                alert(response.data.message);
                                location.reload();
                            } else {
                                alert(response.data.message || 'Chyba pri mazaní');
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('AJAX chyba pri mazaní: ' + error);
                        }
                    });
                }
            });

            $(document).on('click', '#helpdesk-bugs-table .helpdesk-btn-edit-bug', function() {
                const id = $(this).data('id');
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_get_bug',
                        _ajax_nonce: nonce,
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const bug = response.data.bug;
                            $('#bug-id').val(bug.id);
                            $('#bug-nazov').val(bug.nazov);
                            $('#bug-popis').val(bug.popis);
                            $('#bug-kod_chyby').val(bug.kod_chyby);
                            $('#bug-produkt').val(bug.produkt);
                            $('#bug-riesenie').val(bug.riesenie);
                            $('#bug-riesenie-2').val(bug.riesenie_2 || '');
                            $('#bug-podpis').val(bug.podpis_id || '');
                            
                            // Load full description from bug code if available
                            if (bug.kod_chyby) {
                                // Trigger change event to populate uplny_popis
                                $('#bug-kod_chyby').trigger('change');
                            }
                            
                            // Handle tags - convert JSON array to comma-separated string
                            if (bug.tagy) {
                                try {
                                    const tagy = JSON.parse(bug.tagy);
                                    if (Array.isArray(tagy)) {
                                        $('#bug-tagy').val(tagy.join(', '));
                                    }
                                } catch(e) {
                                    $('#bug-tagy').val(bug.tagy);
                                }
                            } else {
                                $('#bug-tagy').val('');
                            }
                            
                            $('#bug-modal-title').text('Upravit riešenie');
                            $('#helpdesk-bug-modal').show();
                            $('.error-message').text('');
                        } else {
                            alert(response.data.message || 'Chyba pri načítaní');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('AJAX chyba: ' + error);
                    }
                });
            });
        }

        // ===== CSV IMPORT/EXPORT =====
        // Export Employees
        $(document).on('click', '.helpdesk-btn-export-employees', function() {
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_export_employees',
                    _ajax_nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        const blob = new Blob([response.data.content], { type: 'text/csv;charset=utf-8;' });
                        const link = document.createElement('a');
                        const url = URL.createObjectURL(blob);
                        link.setAttribute('href', url);
                        link.setAttribute('download', response.data.filename);
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        alert('Chyba pri exporte: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX chyba: ' + error);
                }
            });
        });

        // Import Employees
        $(document).on('click', '.helpdesk-btn-import-employees', function() {
            $('#helpdesk-employees-csv-input').click();
        });

        $(document).on('change', '#helpdesk-employees-csv-input', function() {
            const file = this.files[0];
            if (!file) return;

            console.log('Preview CSV import file:', file.name);

            const formData = new FormData();
            formData.append('action', 'helpdesk_preview_import_employees');
            formData.append('_ajax_nonce', nonce);
            formData.append('csv_file', file);

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Preview response:', response);
                    if (response.success) {
                        showImportSelectionDialog(response.data.employees, file);
                    } else {
                        alert('Chyba pri čítaní CSV: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX chyba: ' + error);
                }
            });

            // Reset input
            this.value = '';
        });

        function showImportSelectionDialog(employees, csvFile) {
            // Create modal
            const modalId = 'import-selection-modal-' + Date.now();
            
            let html = '<div class="import-selection-modal" id="' + modalId + '" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10001; background: white; border: 2px solid #0073aa; border-radius: 5px; padding: 0; box-shadow: 0 5px 30px rgba(0,0,0,0.3); max-width: 700px; max-height: 80vh; overflow-y: auto;">';
            
            html += '<div style="padding: 20px; background-color: #f9f9f9; border-bottom: 1px solid #ddd;">';
            html += '<h2 style="margin: 0 0 10px 0; color: #0073aa;">Výber pracovníkov na import</h2>';
            html += '<p style="margin: 0; color: #666;">Celkom: ' + employees.length + ' pracovníkov. Zaškrtnite ktorých chcete importovať.</p>';
            html += '</div>';
            
            html += '<div style="padding: 20px;">';
            
            // Controls
            html += '<div style="margin-bottom: 15px; display: flex; gap: 10px;">';
            html += '<button class="button button-secondary select-all-employees" style="cursor: pointer;">✓ Vybrať všetkých</button>';
            html += '<button class="button button-secondary deselect-all-employees" style="cursor: pointer;">✗ Zrušiť všetko</button>';
            html += '</div>';
            
            // Employee list
            html += '<div style="border: 1px solid #ddd; border-radius: 3px; max-height: 400px; overflow-y: auto;">';
            
            employees.forEach(function(emp, idx) {
                const statusClass = emp.exists ? 'existing' : 'new';
                const statusText = emp.exists ? '📝 Existujúci (aktualizácia)' : '✨ Nový pracovník';
                const statusColor = emp.exists ? '#ffc107' : '#28a745';
                
                let details = '';
                if (emp.exists && emp.existing_phone) {
                    details = ' (Klapka: ' + emp.existing_phone + ')';
                }
                
                html += '<div style="padding: 12px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px;">';
                html += '<input type="checkbox" class="employee-import-checkbox" data-name="' + emp.name.replace(/"/g, '&quot;') + '" checked style="width: 18px; height: 18px; cursor: pointer;">';
                html += '<div style="flex: 1;">';
                html += '<strong>' + emp.name + '</strong>';
                html += '<div style="font-size: 12px; color: #666; margin-top: 3px;">';
                html += 'Klapka: ' + (emp.phone || '-') + ' | Mobil: ' + (emp.mobile || '-') + '<br/>';
                html += '<span style="background-color: ' + statusColor + '; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; display: inline-block; margin-top: 3px;">' + statusText + details + '</span>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            });
            
            html += '</div>';
            
            html += '</div>';
            
            // Footer
            html += '<div style="padding: 15px 20px; background-color: #f9f9f9; border-top: 1px solid #ddd; text-align: right; display: flex; gap: 10px; justify-content: flex-end;">';
            html += '<button class="button" id="cancel-import-btn" style="cursor: pointer;">Zrušiť</button>';
            html += '<button class="button button-primary" id="confirm-import-btn" style="cursor: pointer;">Importovať vybraných</button>';
            html += '</div>';
            
            html += '</div>';
            
            // Add backdrop
            const backdropId = 'import-backdrop-' + Date.now();
            let backdrop = '<div id="' + backdropId + '" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000;"></div>';
            
            // Insert into DOM
            $('body').append(backdrop + html);
            
            // Event handlers
            $(document).on('click', '.select-all-employees', function() {
                $('#' + modalId + ' .employee-import-checkbox').prop('checked', true);
            });
            
            $(document).on('click', '.deselect-all-employees', function() {
                $('#' + modalId + ' .employee-import-checkbox').prop('checked', false);
            });
            
            $(document).on('click', '#cancel-import-btn', function() {
                $('#' + modalId).remove();
                $('#' + backdropId).remove();
            });
            
            $(document).on('click', '#confirm-import-btn', function() {
                // Get selected employees
                const selectedEmployees = [];
                $('#' + modalId + ' .employee-import-checkbox:checked').each(function() {
                    selectedEmployees.push({
                        'name': $(this).data('name')
                    });
                });
                
                if (selectedEmployees.length === 0) {
                    alert('Musíte vybrať aspoň jedného pracovníka!');
                    return;
                }
                
                console.log('Selected employees:', selectedEmployees);
                
                // Perform import with selected employees
                performImportWithSelection(csvFile, selectedEmployees);
                
                // Close modal
                $('#' + modalId).remove();
                $('#' + backdropId).remove();
            });
        }

        function performImportWithSelection(csvFile, selectedEmployees) {
            console.log('Performing import with ' + selectedEmployees.length + ' selected employees');
            
            const formData = new FormData();
            formData.append('action', 'helpdesk_import_employees');
            formData.append('_ajax_nonce', nonce);
            formData.append('csv_file', csvFile);
            formData.append('selected_employees', JSON.stringify(selectedEmployees));

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Import response:', response);
                    if (response.success) {
                        showImportResultWindow(response.data);
                    } else {
                        alert('Chyba pri importe: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Import error:', error);
                    alert('AJAX chyba: ' + error);
                }
            });
        }

        function showImportResultWindow(data) {
            const resultWindow = window.open('', 'import_results', 'width=700,height=600');
            resultWindow.document.write('<html><head><title>Výsledky importu pracovníkov</title>');
            resultWindow.document.write('<style>');
            resultWindow.document.write('body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }');
            resultWindow.document.write('h2 { color: #0073aa; }');
            resultWindow.document.write('.summary-box { background-color: #f0f8ff; padding: 15px; border-left: 4px solid #0073aa; margin: 10px 0; }');
            resultWindow.document.write('.conflicts-box { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ff9800; margin: 10px 0; }');
            resultWindow.document.write('.errors-box { background-color: #f8d7da; padding: 15px; border-left: 4px solid #d32f2f; margin: 10px 0; }');
            resultWindow.document.write('.success-box { background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0; }');
            resultWindow.document.write('pre { background-color: #f5f5f5; padding: 10px; overflow-x: auto; font-size: 12px; }');
            resultWindow.document.write('</style>');
            resultWindow.document.write('</head><body>');
            resultWindow.document.write('<h1>Výsledky importu pracovníkov</h1>');

            // Main message
            resultWindow.document.write('<div class="summary-box">');
            resultWindow.document.write('<strong>📊 ' + data.message + '</strong>');
            resultWindow.document.write('</div>');

            // Conflicts
            if (data.conflicts && data.conflicts.length > 0) {
                resultWindow.document.write('<div class="conflicts-box">');
                resultWindow.document.write('<h3>⚠️ Konflikty (rozdielne klapky)</h3>');
                resultWindow.document.write('<p>Títo pracovníci majú v CSV iné klapky ako v systéme. Pre bezpečnosť neboli importovaní.</p>');
                data.conflicts.forEach(function(conflict) {
                    resultWindow.document.write('<p><strong>' + conflict.employee + '</strong> (riadok ' + conflict.line + '):<br/>');
                    resultWindow.document.write('Existujúca klapka: <strong>' + conflict.existing_klapka + '</strong><br/>');
                    resultWindow.document.write('Klapka v CSV: <strong>' + conflict.new_klapka + '</strong></p>');
                });
                resultWindow.document.write('</div>');
            }

            // Summary
            if (data.summary && data.summary.length > 0) {
                resultWindow.document.write('<div class="success-box">');
                resultWindow.document.write('<h3>✅ Súhrn zmien a doplnení</h3>');
                data.summary.forEach(function(item) {
                    resultWindow.document.write('<p>• ' + item + '</p>');
                });
                resultWindow.document.write('</div>');
            }

            // Errors
            if (data.errors && data.errors.length > 0) {
                resultWindow.document.write('<div class="errors-box">');
                resultWindow.document.write('<h3>❌ Chyby</h3>');
                data.errors.forEach(function(error) {
                    resultWindow.document.write('<p>• ' + error + '</p>');
                });
                resultWindow.document.write('</div>');
            }

            resultWindow.document.write('<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">');
            resultWindow.document.write('<button onclick="location.reload()" style="background-color: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer;">Obnoviť stránku a zatvoriť</button>');
            resultWindow.document.write('</div>');
            resultWindow.document.write('</body></html>');
            resultWindow.document.close();
        }

        // Export Projects
        $(document).on('click', '.helpdesk-btn-export-projects', function() {
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_export_projects',
                    _ajax_nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        const blob = new Blob([response.data.content], { type: 'text/csv;charset=utf-8;' });
                        const link = document.createElement('a');
                        const url = URL.createObjectURL(blob);
                        link.setAttribute('href', url);
                        link.setAttribute('download', response.data.filename);
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        alert('Chyba pri exporte: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX chyba: ' + error);
                }
            });
        });

        // Import Projects
        $(document).on('click', '.helpdesk-btn-import-projects', function() {
            $('#helpdesk-projects-csv-input').click();
        });

        $(document).on('change', '#helpdesk-projects-csv-input', function() {
            const file = this.files[0];
            if (!file) return;

            console.log('Importing projects file:', file.name, file.size, file.type);

            const formData = new FormData();
            formData.append('action', 'helpdesk_import_projects');
            formData.append('_ajax_nonce', nonce);
            formData.append('csv_file', file);

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Import response:', response);
                    if (response.success) {
                        alert('Importovanie úspešné: ' + response.data.message);
                        if (response.data.warnings && response.data.warnings.length > 0) {
                            console.warn('Upozornenia:', response.data.warnings);
                        }
                        location.reload();
                    } else {
                        alert('Chyba pri importe: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Import error:', xhr, status, error);
                    alert('AJAX chyba: ' + error);
                },
                complete: function() {
                    // Reset file input
                    $('#helpdesk-projects-csv-input').val('');
                }
            });

            // Reset input
            this.value = '';
        });

        // Export Bugs
        $(document).on('click', '.helpdesk-btn-export-bugs', function() {
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_export_bugs',
                    _ajax_nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        const blob = new Blob([response.data.content], { type: 'text/csv;charset=utf-8;' });
                        const link = document.createElement('a');
                        const url = URL.createObjectURL(blob);
                        link.setAttribute('href', url);
                        link.setAttribute('download', response.data.filename);
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        alert('Chyba pri exporte: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX chyba: ' + error);
                }
            });
        });

        // Import Bugs
        $(document).on('click', '.helpdesk-btn-import-bugs', function() {
            $('#helpdesk-bugs-csv-input').click();
        });

        $(document).on('change', '#helpdesk-bugs-csv-input', function() {
            const file = this.files[0];
            if (!file) return;

            console.log('Importing bugs file:', file.name, file.size, file.type);

            const formData = new FormData();
            formData.append('action', 'helpdesk_import_bugs');
            formData.append('_ajax_nonce', nonce);
            formData.append('csv_file', file);

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Import response:', response);
                    if (response.success) {
                        alert('Importovanie úspešné: ' + response.data.message);
                        if (response.data.warnings && response.data.warnings.length > 0) {
                            console.warn('Upozornenia:', response.data.warnings);
                        }
                        location.reload();
                    } else {
                        alert('Chyba pri importe: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Import error:', xhr, status, error);
                    alert('AJAX chyba: ' + error);
                }
            });

            // Reset input
            this.value = '';
        });

        // CSV Import - Reset input after import
        $('#helpdesk-employees-csv-input, #helpdesk-projects-csv-input, #helpdesk-bugs-csv-input').on('change', function() {
            if (this.files && this.files[0]) {
                // File selected, will be processed by existing handlers
            } else {
                // Reset input
                this.value = '';
            }
        });

        // ===== SEARCH FUNCTIONALITY =====
        console.log('Setting up search handlers');

        // Helper function to filter table by specific columns
        function filterTable(searchInputId, tableId, columnIndices) {
            const $searchInput = $('#' + searchInputId);
            const $table = $('#' + tableId);
            
            // Default: search all columns if not specified
            if (!columnIndices) {
                columnIndices = null;
            }
            
            if ($searchInput.length === 0) {
                console.warn('Search input not found: ' + searchInputId);
                return;
            }
            if ($table.length === 0) {
                console.warn('Table not found: ' + tableId);
                return;
            }

            $(document).on('input', '#' + searchInputId, function() {
                const searchText = $(this).val().toLowerCase();
                $table.find('tbody tr').each(function() {
                    const row = $(this);
                    const cells = row.find('td');
                    let searchMatch = false;
                    
                    if (searchText === '') {
                        searchMatch = true;
                    } else if (columnIndices) {
                        // Search only in specified columns
                        columnIndices.forEach(index => {
                            if (cells.eq(index).text().toLowerCase().includes(searchText)) {
                                searchMatch = true;
                            }
                        });
                    } else {
                        // Search all columns
                        if (row.text().toLowerCase().includes(searchText)) {
                            searchMatch = true;
                        }
                    }
                    
                    if (searchMatch) {
                        row.show();
                    } else {
                        row.hide();
                    }
                });
            });
        }

        // ===== EMPLOYEES FILTERS =====
        function filterEmployeesTable() {
            const positionFilter = $('#filter-employees-position').val();
            const standbyFilter = $('#filter-employees-standby').val();

            $('#helpdesk-employees-table tbody tr').each(function() {
                const $row = $(this);
                const position = $row.data('position');
                const standby = $row.data('standby');

                let show = true;

                // Position filter (numeric position ID or 0 for no position)
                if (positionFilter) {
                    if (position != positionFilter) {
                        show = false;
                    }
                }

                // Standby filter
                if (standbyFilter) {
                    if (standbyFilter === 'has' && standby !== 'yes') {
                        show = false;
                    } else if (standbyFilter === 'no' && standby !== 'no') {
                        show = false;
                    }
                }

                $row.toggle(show);
            });
        }

        // Bind employee filter events
        $(document).on('change', '#filter-employees-position, #filter-employees-standby', function() {
            filterEmployeesTable();
        });

        // Reset employee filters
        $(document).on('click', '#btn-reset-employees-filters', function() {
            $('#filter-employees-position').val('');
            $('#filter-employees-standby').val('');
            filterEmployeesTable();
        });

        // Initialize search for all modules after delay to ensure elements exist
        setTimeout(function() {
            filterTable('helpdesk-employees-search', 'helpdesk-employees-table');
            // Projects: search in columns 0-4 (Zákaznícke Číslo, Projektové Číslo, Názov, Podnázov, Pracovníci)
            filterTable('helpdesk-projects-search', 'helpdesk-projects-table', [0, 1, 2, 3, 4, 5]);
            // Bugs: search in columns 0-4 (Kód, Názov, Produkt, Tagy, Dátum)
            filterTable('helpdesk-bugs-search', 'helpdesk-bugs-table', [0, 1, 2, 3, 4]);
            
            // Initialize sortable headers for employees table
            initEmployeesSortable();
        }, 500);

        // ===== POSITIONS =====
        initPositions();

        // ===== BUG CODES =====
        initBugCodes();

        // ===== OPERATING SYSTEMS =====
        initOperatingSystems();

        // ===== PRODUCTS =====
        initProducts();
    });

    function initEmployeesSortable() {
        const employeesTableSortState = {
            currentSortField: null,
            currentSortAsc: true
        };

        $(document).on('click', '#helpdesk-employees-table .sortable', function() {
            const sortField = $(this).data('sort-field');
            const table = $('#helpdesk-employees-table');
            const tbody = table.find('tbody');
            const rows = tbody.find('tr').get();

            // Toggle sort direction if same field clicked
            if (employeesTableSortState.currentSortField === sortField) {
                employeesTableSortState.currentSortAsc = !employeesTableSortState.currentSortAsc;
            } else {
                employeesTableSortState.currentSortField = sortField;
                employeesTableSortState.currentSortAsc = true;
            }

            // Update indicators
            $('#helpdesk-employees-table .sort-indicator').text('');
            $(this).find('.sort-indicator').text(employeesTableSortState.currentSortAsc ? ' ↑' : ' ↓');

            // Sort rows
            rows.sort(function(a, b) {
                let aVal, bVal;

                if (sortField === 'meno_priezvisko') {
                    aVal = $(a).find('td:eq(1)').text().trim().toLowerCase();
                    bVal = $(b).find('td:eq(1)').text().trim().toLowerCase();
                    if (aVal < bVal) return employeesTableSortState.currentSortAsc ? -1 : 1;
                    if (aVal > bVal) return employeesTableSortState.currentSortAsc ? 1 : -1;
                } else if (sortField === 'klapka') {
                    aVal = $(a).find('td.column-klapka').text().trim().toLowerCase();
                    bVal = $(b).find('td.column-klapka').text().trim().toLowerCase();
                    if (aVal < bVal) return employeesTableSortState.currentSortAsc ? -1 : 1;
                    if (aVal > bVal) return employeesTableSortState.currentSortAsc ? 1 : -1;
                } else if (sortField === 'mobil') {
                    aVal = $(a).find('td.column-mobil').text().trim().toLowerCase();
                    bVal = $(b).find('td.column-mobil').text().trim().toLowerCase();
                    if (aVal < bVal) return employeesTableSortState.currentSortAsc ? -1 : 1;
                    if (aVal > bVal) return employeesTableSortState.currentSortAsc ? 1 : -1;
                } else if (sortField === 'pozicia_id') {
                    aVal = $(a).find('td.column-pozicia').text().trim().toLowerCase();
                    bVal = $(b).find('td.column-pozicia').text().trim().toLowerCase();
                    if (aVal < bVal) return employeesTableSortState.currentSortAsc ? -1 : 1;
                    if (aVal > bVal) return employeesTableSortState.currentSortAsc ? 1 : -1;
                }

                return 0;
            });

            // Re-append sorted rows
            $.each(rows, function(index, row) {
                tbody.append(row);
            });
        });
    }

    function initBugCodes() {
        $(document).on('click', '.helpdesk-btn-new-code', function() {
            $('#code-id').val('');
            $('#helpdesk-code-form')[0].reset();
            $('#code-modal-title').text('Pridať problém');
            $('#helpdesk-code-modal').show();
            $('.error-message').text('');
        });

        // Auto-fill popis and uplny_popis when kod changes
        $(document).on('change blur', '#code-kod', function() {
            const kod = $(this).val();
            
            if (!kod || kod.trim() === '') {
                return;
            }

            // Only auto-fill if we're adding new code (not editing)
            if ($('#code-id').val() === '') {
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_get_bug_code_by_kod',
                        _ajax_nonce: nonce,
                        kod: kod
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data.code) {
                            const code = response.data.code;
                            // Auto-fill popis and uplny_popis
                            if (code.popis) {
                                $('#code-popis').val(code.popis);
                            }
                            if (code.uplny_popis) {
                                $('#code-uplny_popis').val(code.uplny_popis);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Code lookup not found or error');
                        // Toto je OK - kód nemusí existovať pri vytváraní nového
                    }
                });
            }
        });

        $(document).on('click', '#helpdesk-code-modal .helpdesk-modal-close, #helpdesk-code-modal .helpdesk-modal-close-btn', function() {
            $('#helpdesk-code-modal').hide();
        });

        $(document).on('submit', '#helpdesk-code-form', function(e) {
            e.preventDefault();
            const id = $('#code-id').val();
            const kod = $('#code-kod').val();
            const popis = $('#code-popis').val();
            const uplny_popis = $('#code-uplny_popis').val();
            const operacny_system = $('#code-operacny_system').val();
            const produkt = $('#code-produkt').val();
            const aktivny = $('#code-aktivny').is(':checked') ? 1 : 0;

            console.log('Saving bug code:', { id, kod, popis, uplny_popis, operacny_system, produkt, aktivny });

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_save_bug_code',
                    _ajax_nonce: nonce,
                    id: id,
                    kod: kod,
                    popis: popis,
                    uplny_popis: uplny_popis,
                    operacny_system: operacny_system,
                    produkt: produkt,
                    aktivny: aktivny
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Bug code save response:', response);
                    $('.error-message').text('');
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || 'Chyba pri uložení');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Bug code save error:', error, 'Status:', status, 'Response:', xhr.responseText);
                    alert('AJAX chyba: ' + error + '\n\n' + xhr.responseText);
                }
            });
        });

        $(document).on('click', '#helpdesk-codes-table .helpdesk-btn-edit-code', function() {
            const id = $(this).data('id');
            
            // Load code data from server
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_get_bug_code',
                    _ajax_nonce: nonce,
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const code = response.data.code;
                        $('#code-id').val(code.id);
                        $('#code-kod').val(code.kod);
                        $('#code-popis').val(code.popis);
                        $('#code-uplny_popis').val(code.uplny_popis);
                        $('#code-operacny_system').val(code.operacny_system);
                        $('#code-produkt').val(code.produkt);
                        $('#code-aktivny').prop('checked', code.aktivny == 1);
                        $('#code-modal-title').text('Upraviť problém');
                        $('#helpdesk-code-modal').show();
                    } else {
                        alert(response.data.message || 'Chyba pri načítaní');
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX chyba: ' + error);
                }
            });
        });

        $(document).on('click', '#helpdesk-codes-table .helpdesk-btn-delete-code', function() {
            if (confirm('Naozaj chcete zmazať tento kód?')) {
                const id = $(this).data('id');
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_delete_bug_code',
                        _ajax_nonce: nonce,
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert(response.data.message || 'Chyba pri mazaní');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('AJAX chyba: ' + error);
                    }
                });
            }
        });

        // Filter codes
        $(document).on('input change', '#filter-search, #filter-os, #filter-product, #filter-status', function() {
            const searchText = $('#filter-search').val().toLowerCase();
            const selectedOS = $('#filter-os').val();
            const selectedProduct = $('#filter-product').val();
            const selectedStatus = $('#filter-status').val();

            $('#helpdesk-codes-table tbody tr').each(function() {
                const $row = $(this);
                const kod = $row.find('.column-kod').text().toLowerCase();
                const popis = $row.find('.column-popis').text().toLowerCase();
                const os = $row.data('os');
                const product = $row.data('product');
                const status = $row.data('status');

                let show = true;

                // Search filter
                if (searchText && !kod.includes(searchText) && !popis.includes(searchText)) {
                    show = false;
                }

                // OS filter
                if (selectedOS && os != selectedOS) {
                    show = false;
                }

                // Product filter
                if (selectedProduct && product != selectedProduct) {
                    show = false;
                }

                // Status filter
                if (selectedStatus !== '' && parseInt(status) != parseInt(selectedStatus)) {
                    show = false;
                }

                $row.toggle(show);
            });
        });
    }

    function initOperatingSystems() {
        $(document).on('click', '.helpdesk-btn-new-os', function() {
            $('#os-id').val('');
            $('#helpdesk-os-form')[0].reset();
            $('#os-modal-title').text('Pridať OS');
            $('#helpdesk-os-modal').show();
            $('.error-message').text('');
        });

        $(document).on('click', '#helpdesk-os-modal .helpdesk-modal-close, #helpdesk-os-modal .helpdesk-modal-close-btn', function() {
            $('#helpdesk-os-modal').hide();
        });

        $(document).on('submit', '#helpdesk-os-form', function(e) {
            e.preventDefault();
            const id = $('#os-id').val();
            const nazov = $('#os-nazov').val();
            const zkratka = $('#os-zkratka').val();
            const popis = $('#os-popis').val();
            const aktivny = $('#os-aktivny').is(':checked') ? 1 : 0;

            console.log('Saving OS:', { id, nazov, zkratka, popis, aktivny });

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_save_operating_system',
                    _ajax_nonce: nonce,
                    id: id,
                    nazov: nazov,
                    zkratka: zkratka,
                    popis: popis,
                    aktivny: aktivny
                },
                dataType: 'json',
                success: function(response) {
                    console.log('OS save response:', response);
                    $('.error-message').text('');
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || 'Chyba pri uložení');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('OS save error:', error, 'Status:', status, 'Response:', xhr.responseText);
                    alert('AJAX chyba: ' + error + '\n\n' + xhr.responseText);
                }
            });
        });

        $(document).on('click', '#helpdesk-os-table .helpdesk-btn-edit-os', function() {
            const id = $(this).data('id');
            
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_get_operating_system',
                    _ajax_nonce: nonce,
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const os = response.data.os;
                        $('#os-id').val(os.id);
                        $('#os-nazov').val(os.nazov);
                        $('#os-zkratka').val(os.zkratka);
                        $('#os-popis').val(os.popis);
                        $('#os-aktivny').prop('checked', os.aktivny == 1);
                        $('#os-modal-title').text('Upraviť OS');
                        $('#helpdesk-os-modal').show();
                    } else {
                        alert(response.data.message || 'Chyba pri načítaní');
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX chyba: ' + error);
                }
            });
        });

        $(document).on('click', '#helpdesk-os-table .helpdesk-btn-delete-os', function() {
            if (confirm('Naozaj chcete zmazať tento OS?')) {
                const id = $(this).data('id');
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_delete_operating_system',
                        _ajax_nonce: nonce,
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert(response.data.message || 'Chyba pri mazaní');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('AJAX chyba: ' + error);
                    }
                });
            }
        });
    }

    function initContacts() {
        $(document).on('click', '.helpdesk-btn-new-contact', function() {
            $('#helpdesk-contact-id').val('');
            $('#helpdesk-contact-form')[0].reset();
            $('#contact-modal-title').text('Pridať kontakt');
            $('#helpdesk-contact-modal').show();
            $('.error-message').text('');
        });

        $(document).on('click', '#helpdesk-contact-modal .helpdesk-modal-close, #helpdesk-contact-modal .helpdesk-modal-close-btn', function() {
            $('#helpdesk-contact-modal').hide();
        });

        $(document).on('submit', '#helpdesk-contact-form', function(e) {
            e.preventDefault();
            const id = $('#helpdesk-contact-id').val();
            const nazov = $('#helpdesk-contact-nazov').val();
            const kontaktna_osoba = $('#helpdesk-contact-kontaktna-osoba').val();
            const klapka = $('#helpdesk-contact-klapka').val();
            const telefon = $('#helpdesk-contact-telefon').val();
            const email = $('#helpdesk-contact-email').val();
            const poznamka = $('#helpdesk-contact-poznamka').val();
            const aktivny = $('#helpdesk-contact-aktivny').is(':checked') ? 1 : 0;

            console.log('Saving contact:', { id, nazov, kontaktna_osoba, klapka, telefon, email, poznamka, aktivny });

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_save_contact',
                    _ajax_nonce: nonce,
                    id: id,
                    nazov: nazov,
                    kontaktna_osoba: kontaktna_osoba,
                    klapka: klapka,
                    telefon: telefon,
                    email: email,
                    poznamka: poznamka,
                    aktivny: aktivny
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Contact save response:', response);
                    $('.error-message').text('');
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        if (response.data.errors) {
                            $.each(response.data.errors, function(key, msg) {
                                $('#error-' + key).text(msg);
                            });
                        } else {
                            alert(response.data.message || 'Chyba pri uložení');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Contact save error:', error, 'Status:', status, 'Response:', xhr.responseText);
                    alert('AJAX chyba pri uložení kontaktu: ' + error);
                }
            });
        });

        $(document).on('click', '#helpdesk-contacts-table .helpdesk-btn-edit-contact', function() {
            const id = $(this).data('id');
            
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_get_contact',
                    _ajax_nonce: nonce,
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const contact = response.data.contact;
                        $('#helpdesk-contact-id').val(contact.id);
                        $('#helpdesk-contact-nazov').val(contact.nazov || '');
                        $('#helpdesk-contact-kontaktna-osoba').val(contact.kontaktna_osoba || '');
                        $('#helpdesk-contact-klapka').val(contact.klapka || '');
                        $('#helpdesk-contact-telefon').val(contact.telefon || '');
                        $('#helpdesk-contact-email').val(contact.email || '');
                        $('#helpdesk-contact-poznamka').val(contact.poznamka || '');
                        $('#helpdesk-contact-aktivny').prop('checked', contact.aktivny == 1);
                        $('#contact-modal-title').text('Upraviť kontakt');
                        $('#helpdesk-contact-modal').show();
                        $('.error-message').text('');
                    } else {
                        alert(response.data.message || 'Chyba pri načítaní');
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX chyba: ' + error);
                }
            });
        });

        $(document).on('click', '#helpdesk-contacts-table .helpdesk-btn-delete-contact', function() {
            if (confirm('Naozaj chcete zmazať tento kontakt?')) {
                const id = $(this).data('id');
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_delete_contact',
                        _ajax_nonce: nonce,
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert(response.data.message || 'Chyba pri mazaní');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('AJAX chyba: ' + error);
                    }
                });
            }
        });
    }

    function initProducts() {
        $(document).on('click', '.helpdesk-btn-new-product', function() {
            $('#product-id').val('');
            $('#helpdesk-product-form')[0].reset();
            $('#product-modal-title').text('Pridať produkt');
            $('#helpdesk-product-modal').show();
            $('.error-message').text('');
        });

        $(document).on('click', '#helpdesk-product-modal .helpdesk-modal-close, #helpdesk-product-modal .helpdesk-modal-close-btn', function() {
            $('#helpdesk-product-modal').hide();
        });

        $(document).on('submit', '#helpdesk-product-form', function(e) {
            e.preventDefault();
            const id = $('#product-id').val();
            const nazov = $('#product-nazov').val();
            const popis = $('#product-popis').val();
            const link = $('#product-link').val();
            const aktivny = $('#product-aktivny').is(':checked') ? 1 : 0;

            console.log('Saving product:', { id, nazov, popis, link, aktivny });

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_save_product',
                    _ajax_nonce: nonce,
                    id: id,
                    nazov: nazov,
                    popis: popis,
                    link: link,
                    aktivny: aktivny
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Product save response:', response);
                    $('.error-message').text('');
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || 'Chyba pri uložení');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Product save error:', error, 'Status:', status, 'Response:', xhr.responseText);
                    alert('AJAX chyba: ' + error + '\n\n' + xhr.responseText);
                }
            });
        });

        $(document).on('click', '#helpdesk-products-table .helpdesk-btn-edit-product', function() {
            const id = $(this).data('id');
            
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_get_product',
                    _ajax_nonce: nonce,
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const product = response.data.product;
                        $('#product-id').val(product.id);
                        $('#product-nazov').val(product.nazov);
                        $('#product-popis').val(product.popis);
                        $('#product-link').val(product.link || '');
                        $('#product-aktivny').prop('checked', product.aktivny == 1);
                        $('#product-modal-title').text('Upraviť produkt');
                        $('#helpdesk-product-modal').show();
                        $('.error-message').text('');
                    } else {
                        alert(response.data.message || 'Chyba pri načítaní');
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX chyba: ' + error);
                }
            });
        });

        $(document).on('click', '#helpdesk-products-table .helpdesk-btn-delete-product', function() {
            if (confirm('Naozaj chcete zmazať tento produkt?')) {
                const id = $(this).data('id');
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_delete_product',
                        _ajax_nonce: nonce,
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert(response.data.message || 'Chyba pri mazaní');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('AJAX chyba: ' + error);
                    }
                });
            }
        });
    }

    function initPositions() {
        $(document).on('click', '.helpdesk-btn-new-position', function() {
            $('#position-id').val('');
            $('#helpdesk-position-form')[0].reset();
            $('#position-modal-title').text('Pridať pozíciu');
            $('#helpdesk-position-modal').show();
            $('.error-message').text('');
        });

        $(document).on('click', '#helpdesk-position-modal .helpdesk-modal-close, #helpdesk-position-modal .helpdesk-modal-close-btn', function() {
            $('#helpdesk-position-modal').hide();
        });

        $(document).on('submit', '#helpdesk-position-form', function(e) {
            e.preventDefault();
            const id = $('#position-id').val();
            const profesia = $('#position-profesia').val();
            const skratka = $('#position-skratka').val();
            const priorita = $('#position-priorita').val();

            console.log('Saving position:', { id, profesia, skratka, priorita });

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_save_position',
                    _ajax_nonce: nonce,
                    id: id,
                    profesia: profesia,
                    skratka: skratka,
                    priorita: priorita
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Position save response:', response);
                    $('.error-message').text('');
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || 'Chyba pri uložení');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Position save error:', error);
                    alert('AJAX chyba: ' + error);
                }
            });
        });

        $(document).on('click', '#helpdesk-positions-table .helpdesk-btn-edit-position', function() {
            const id = $(this).data('id');
            const $row = $(this).closest('tr');
            
            $('#position-id').val(id);
            $('#position-profesia').val($row.find('.column-profesia').text());
            $('#position-skratka').val($row.find('.column-skratka').text());
            $('#position-priorita').val($row.find('.column-priorita').text());
            $('#position-modal-title').text('Upraviť pozíciu');
            $('#helpdesk-position-modal').show();
        });

        $(document).on('click', '#helpdesk-positions-table .helpdesk-btn-delete-position', function() {
            if (confirm('Naozaj chcete zmazať túto pozíciu?')) {
                const id = $(this).data('id');
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_delete_position',
                        _ajax_nonce: nonce,
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert(response.data.message || 'Chyba pri mazaní');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('AJAX chyba: ' + error);
                    }
                });
            }
        });

        // Export Positions
        $(document).on('click', '.helpdesk-btn-export-positions', function() {
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_export_positions',
                    _ajax_nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        const blob = new Blob([response.data.content], { type: 'text/csv;charset=utf-8;' });
                        const link = document.createElement('a');
                        const url = URL.createObjectURL(blob);
                        link.setAttribute('href', url);
                        link.setAttribute('download', response.data.filename);
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        alert('Chyba pri exporte: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX chyba: ' + error);
                }
            });
        });

        // Import Positions
        $(document).on('click', '.helpdesk-btn-import-positions', function() {
            $('#helpdesk-positions-csv-input').click();
        });

        $(document).on('change', '#helpdesk-positions-csv-input', function() {
            const file = this.files[0];
            if (!file) return;

            console.log('Importing positions file:', file.name, file.size, file.type);

            const formData = new FormData();
            formData.append('action', 'helpdesk_import_positions');
            formData.append('_ajax_nonce', nonce);
            formData.append('csv_file', file);

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Import response:', response);
                    if (response.success) {
                        alert('Importovanie úspešné: ' + response.data.message);
                        if (response.data.warnings && response.data.warnings.length > 0) {
                            console.warn('Upozornenia:', response.data.warnings);
                        }
                        location.reload();
                    } else {
                        alert('Chyba pri importe: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Import error:', xhr, status, error);
                    alert('AJAX chyba: ' + error);
                }
            });

            // Reset input
            this.value = '';
        });
    }

    function initStandby() {
        console.log('=== initStandby started ===');
        
        // Tab switching
        $(document).on('click', '.helpdesk-tab-btn', function() {
            const tabName = $(this).data('tab');
            
            // Hide all tabs
            $('.helpdesk-tab-content').hide();
            $('.helpdesk-tab-btn').css('border-bottom-color', 'transparent');
            
            // Show selected tab
            $('#' + tabName + '-tab').show();
            $(this).css('border-bottom-color', '#007cba');
        });
        
        // Set first tab as active
        $('.helpdesk-tab-btn:first').css('border-bottom-color', '#007cba');

        // Sortable table headers - Track sort state at table level
        let standbyTableSortState = {
            field: null,
            asc: true
        };

        $(document).on('click', 'th.sortable', function() {
            console.log('Sortable header clicked');
            const sortField = $(this).data('sort-field');
            const table = $(this).closest('table');
            
            console.log('Sort field:', sortField);
            console.log('Table ID:', table.attr('id'));
            
            if (!sortField || !table.length) {
                console.error('Invalid sort parameters');
                return;
            }
            
            const tbody = table.find('tbody');
            const rows = tbody.find('tr').toArray();
            
            console.log('Found', rows.length, 'rows to sort');

            // Determine sort direction
            if (standbyTableSortState.field === sortField) {
                standbyTableSortState.asc = !standbyTableSortState.asc;
            } else {
                standbyTableSortState.asc = true;
            }
            standbyTableSortState.field = sortField;

            console.log('Sort direction:', standbyTableSortState.asc ? 'ASC' : 'DESC');

            // Remove all sort indicators
            table.find('th.sortable .sort-indicator').hide();

            // Show current sort indicator
            $(this).find('.sort-indicator').text(standbyTableSortState.asc ? '↑' : '↓').show();

            // Sort rows
            rows.sort((a, b) => {
                let aVal = '';
                let bVal = '';

                // Extract value based on sort field
                switch(sortField) {
                    case 'meno_priezvisko':
                        aVal = $(a).find('td:first strong').text().toLowerCase().trim();
                        bVal = $(b).find('td:first strong').text().toLowerCase().trim();
                        console.log('Comparing names:', aVal, 'vs', bVal);
                        break;
                    case 'zakaznicke_cislo':
                        aVal = $(a).find('td:eq(1)').text().toLowerCase().trim();
                        bVal = $(b).find('td:eq(1)').text().toLowerCase().trim();
                        console.log('Comparing projects:', aVal, 'vs', bVal);
                        break;
                    case 'pohotovost_od':
                        aVal = $(a).data('date-from') || '';
                        bVal = $(b).data('date-from') || '';
                        console.log('Comparing dates (from):', aVal, 'vs', bVal);
                        break;
                    case 'pohotovost_do':
                        aVal = $(a).data('date-to') || '';
                        bVal = $(b).data('date-to') || '';
                        console.log('Comparing dates (to):', aVal, 'vs', bVal);
                        break;
                }

                // Compare values
                if (typeof aVal === 'string') {
                    aVal = aVal.trim();
                    bVal = bVal.trim();
                    const result = aVal.localeCompare(bVal);
                    return standbyTableSortState.asc ? result : -result;
                } else {
                    return standbyTableSortState.asc ? aVal - bVal : bVal - aVal;
                }
            });

            console.log('Re-appending sorted rows');
            // Re-append sorted rows
            rows.forEach(row => {
                tbody.append(row);
            });
            
            console.log('Sort complete');
        });

        // New standby button
        $(document).on('click', '.helpdesk-btn-new-standby', function() {
            console.log('New standby button clicked');
            $('#standby-id').val('');
            $('#standby-employee-id').val('');
            $('#standby-project-id').val('');
            $('#standby-od').val('');
            $('#standby-do').val('');
            $('#helpdesk-standby-modal-form')[0].reset();
            $('#standby-modal-title').text('Pridať pohotovosť');
            $('#helpdesk-standby-modal').show();
            $('.error-message').text('');
        });

        // Close modal
        $(document).on('click', '#helpdesk-standby-modal .helpdesk-modal-close, #helpdesk-standby-modal .helpdesk-modal-close-btn', function() {
            console.log('Closing standby modal');
            $('#helpdesk-standby-modal').hide();
        });

        // Submit form
        $(document).on('submit', '#helpdesk-standby-modal-form', function(e) {
            e.preventDefault();
            console.log('Standby form submitted');
            
            const id = $('#standby-id').val();
            const employeeId = $('#standby-employee-id').val();
            const projectId = $('#standby-project-id').val();
            const od = $('#standby-od').val();
            const doDate = $('#standby-do').val();

            if (!employeeId || !projectId || !od || !doDate) {
                alert('Prosím vyplňte všetky polia');
                return;
            }

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_save_standby',
                    _ajax_nonce: nonce,
                    id: id,
                    employee_id: employeeId,
                    project_id: projectId,
                    date_from: od,
                    date_to: doDate
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Save response:', response);
                    if (response.success) {
                        alert(response.data.message || 'Pohotovosť bola uložená');
                        location.reload();
                    } else {
                        alert('Chyba: ' + (response.data.message || 'Neznáma chyba'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    alert('AJAX chyba: ' + error);
                }
            });
        });

        // Edit button
        $(document).on('click', '.helpdesk-btn-edit-standby', function() {
            const id = $(this).data('standby-id');
            console.log('Edit standby:', id);
            
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_get_standby',
                    _ajax_nonce: nonce,
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Get response:', response);
                    if (response.success) {
                        const standby = response.data.standby;
                        $('#standby-id').val(standby.id);
                        $('#standby-employee-id').val(standby.pracovnik_id);
                        $('#standby-project-id').val(standby.projekt_id);
                        $('#standby-od').val(standby.pohotovost_od);
                        $('#standby-do').val(standby.pohotovost_do);
                        $('#standby-modal-title').text('Upraviť pohotovosť');
                        $('#helpdesk-standby-modal').show();
                    } else {
                        alert('Chyba pri načítaní: ' + (response.data.message || ''));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('Chyba pri načítaní');
                }
            });
        });

        // Delete button
        $(document).on('click', '.helpdesk-btn-delete-standby', function() {
            if (!confirm('Ste si istí, že chcete vymazať túto pohotovosť?')) {
                return;
            }

            const id = $(this).data('standby-id');
            
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_delete_standby',
                    _ajax_nonce: nonce,
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Chyba: ' + (response.data.message || ''));
                    }
                }
            });
        });

        // Filters
        $(document).on('change', '#filter-employee, #filter-project, #filter-position, #filter-today', function() {
            filterStandbyTable();
        });

        $(document).on('click', '#btn-reset-filters', function() {
            $('#filter-employee').val('');
            $('#filter-project').val('');
            $('#filter-position').val('');
            $('#filter-today').prop('checked', false);
            filterStandbyTable();
        });

        function filterStandbyTable() {
            const employeeId = $('#filter-employee').val();
            const projectId = $('#filter-project').val();
            const positionFilter = $('#filter-position').val();
            const filterToday = $('#filter-today').is(':checked');
            const today = new Date().toISOString().split('T')[0];
            
            console.log('=== Filter Standby ===');
            console.log('Employee ID:', employeeId);
            console.log('Project ID:', projectId);
            console.log('Position Filter:', positionFilter);
            console.log('Filter Today:', filterToday);
            console.log('Today date:', today);

            $('#helpdesk-standby-list tr').each(function() {
                const row = $(this);
                const rowEmployeeId = row.data('employee-id');
                const rowProjectId = row.data('project-id');
                const rowDateFrom = row.data('date-from');
                const rowDateTo = row.data('date-to');
                const rowPosition = row.data('position');

                console.log('Row:', {
                    employeeId: rowEmployeeId,
                    projectId: rowProjectId,
                    dateFrom: rowDateFrom,
                    dateTo: rowDateTo,
                    position: rowPosition
                });

                let show = true;

                if (employeeId && rowEmployeeId != employeeId) {
                    show = false;
                }

                if (projectId && rowProjectId != projectId) {
                    show = false;
                }

                if (positionFilter) {
                    if (positionFilter === 'has' && rowPosition !== 'yes') {
                        show = false;
                    } else if (positionFilter === 'no' && rowPosition !== 'no') {
                        show = false;
                    }
                }

                if (filterToday && show) {
                    // Show only if today is between date-from and date-to (inclusive)
                    // Today >= dateFrom AND Today <= dateTo
                    const dateFromStr = String(rowDateFrom).trim();
                    const dateToStr = String(rowDateTo).trim();
                    
                    if (today < dateFromStr || today > dateToStr) {
                        show = false;
                    }
                    
                    console.log('Today check:', {
                        today: today,
                        dateFrom: dateFromStr,
                        dateTo: dateToStr,
                        show: show,
                        comparison: `${today} >= ${dateFromStr} && ${today} <= ${dateToStr}`
                    });
                }

                row.toggle(show);
            });
        }

        // Auto generation
        $(document).on('submit', '#helpdesk-auto-standby-form', function(e) {
            e.preventDefault();
            
            const projectId = $('#auto-project').val();
            const startDate = $('#auto-start-date').val();
            const intervalType = $('#auto-interval-type').val();
            const intervalCount = parseInt($('#auto-interval-count').val());
            const numPeriods = parseInt($('#auto-num-periods').val());

            if (!projectId || !startDate) {
                alert('Vyplňte povinné polia');
                return;
            }

            // Get employees for this project
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_get_project_employees',
                    _ajax_nonce: nonce,
                    project_id: projectId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.employees.length > 0) {
                        const employees = response.data.employees;
                        generateAndSavePeriods(projectId, employees, startDate, intervalType, intervalCount, numPeriods);
                    } else {
                        alert('Pre tento projekt nie sú priradení žádní pracovníci');
                    }
                }
            });
        });

        function generateAndSavePeriods(projectId, employees, startDate, intervalType, intervalCount, numPeriods) {
            const periods = [];
            let currentDate = new Date(startDate);
            let employeeIndex = 0;

            for (let i = 0; i < numPeriods; i++) {
                const od = new Date(currentDate);
                const doDate = new Date(currentDate);

                // Calculate end date
                if (intervalType === 'weeks') {
                    doDate.setDate(doDate.getDate() + (intervalCount * 7) - 1);
                } else if (intervalType === 'months') {
                    doDate.setMonth(doDate.getMonth() + intervalCount);
                    doDate.setDate(doDate.getDate() - 1);
                }

                periods.push({
                    employee_id: employees[employeeIndex].id,
                    project_id: projectId,
                    date_from: formatDate(od),
                    date_to: formatDate(doDate)
                });

                // Next employee in rotation
                employeeIndex = (employeeIndex + 1) % employees.length;

                // Next period
                if (intervalType === 'weeks') {
                    currentDate.setDate(currentDate.getDate() + (intervalCount * 7));
                } else if (intervalType === 'months') {
                    currentDate.setMonth(currentDate.getMonth() + intervalCount);
                }
            }

            // Save all periods
            savePeriodsBatch(periods);
        }

        function savePeriodsBatch(periods) {
            let saved = 0;
            const total = periods.length;

            periods.forEach((period) => {
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_save_standby',
                        _ajax_nonce: nonce,
                        employee_id: period.employee_id,
                        project_id: period.project_id,
                        date_from: period.date_from,
                        date_to: period.date_to
                    },
                    dataType: 'json',
                    success: function(response) {
                        saved++;
                        if (saved === total) {
                            alert('✓ Vygenerovaných ' + total + ' periód pohotovosti');
                            location.reload();
                        }
                    }
                });
            });
        }

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Import standby from CSV
        $(document).on('click', '.helpdesk-btn-import-standby', function() {
            $('#helpdesk-standby-csv-input').click();
        });

        // Update employee positions from CSV (Step 2)
        $(document).on('click', '.helpdesk-btn-update-positions', function() {
            console.log('Update positions button clicked');
            $('#helpdesk-standby-csv-input-update').click();
        });

        $(document).on('change', '#helpdesk-standby-csv-input-update', function() {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('action', 'helpdesk_update_employee_positions');
            formData.append('_ajax_nonce', nonce);
            formData.append('file', file);

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    console.log('Update positions response:', response);
                    if (response.success) {
                        let message = response.data.message + '\n\n';
                        if (response.data.errors && response.data.errors.length > 0) {
                            message += 'Chyby:\n';
                            response.data.errors.forEach(err => {
                                message += '- ' + err + '\n';
                            });
                        }
                        alert('✓ ' + message);
                        location.reload();
                    } else {
                        alert('⚠ ' + response.data.message);
                    }
                    $(this).val('');
                },
                error: function(xhr, status, error) {
                    console.error('Update positions error:', error);
                    alert('Chyba pri aktualizácii pozícií');
                    $(this).val('');
                }
            });
        });

        // Delete all standby records
        $(document).on('click', '.helpdesk-btn-delete-all-standby', function() {
            console.log('Delete all standby button clicked');
            if (!confirm('Naozaj chceš vymazať VŠETKY pohotovosti?\n\nTáto akcia sa nedá vrátiť!')) {
                return;
            }
            
            if (!confirm('Potvrdenie: Chceš vážne vymazať VŠETKY pohotovosti?')) {
                return;
            }

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_delete_all_standby',
                    _ajax_nonce: nonce
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Delete all response:', response);
                    if (response.success) {
                        alert('✓ ' + response.data.message);
                        location.reload();
                    } else {
                        alert('⚠ Chyba: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Delete all error:', error);
                    alert('Chyba pri mazaní pohotovostí');
                }
            });
        });

        // Delete old standby records
        $(document).on('click', '.helpdesk-btn-delete-old-standby', function() {
            console.log('Delete old standby button clicked');
            if (!confirm('Vymaž všetky pohotovosti, ktoré už skončili?\n\nTáto akcia sa nedá vrátiť!')) {
                return;
            }

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_delete_old_standby',
                    _ajax_nonce: nonce
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Delete old standby response:', response);
                    if (response.success) {
                        alert('✓ ' + response.data.message);
                        location.reload();
                    } else {
                        alert('⚠ Chyba: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Delete old standby error:', error);
                    alert('Chyba pri mazaní starých pohotovostí');
                }
            });
        });

        // Check for duplicate standby records
        $(document).on('click', '.helpdesk-btn-check-duplicates', function() {
            console.log('Check duplicates button clicked');
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_check_standby_duplicates',
                    _ajax_nonce: nonce
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Duplicates check response:', response);
                    if (response.success) {
                        const data = response.data;
                        let message = 'Stav pohotovostí:\n';
                        message += 'Celkovo záznamov: ' + data.total_standby + '\n';
                        message += 'Duplikáty: ' + data.duplicate_count + '\n\n';
                        
                        if (data.duplicate_count > 0) {
                            message += 'Duplikáty:\n';
                            data.duplicates.forEach((dup, idx) => {
                                message += (idx + 1) + '. Pracovník ID: ' + dup.pracovnik_id + 
                                          ', Projekt ID: ' + dup.projekt_id + 
                                          ', Počet: ' + dup.count + '\n';
                            });
                            
                            // Ask if user wants to remove duplicates
                            if (confirm(message + '\n\nChcete odstrániť duplikáty? Ponechá sa len prvý záznam.')) {
                                console.log('Removing duplicates...');
                                $.ajax({
                                    type: 'POST',
                                    url: ajaxurl,
                                    data: {
                                        action: 'helpdesk_remove_standby_duplicates',
                                        _ajax_nonce: nonce
                                    },
                                    dataType: 'json',
                                    success: function(removeResponse) {
                                        if (removeResponse.success) {
                                            alert('✓ ' + removeResponse.data.message);
                                            location.reload();
                                        } else {
                                            alert('Chyba: ' + (removeResponse.data.message || 'Neznáma chyba'));
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        alert('AJAX chyba pri mazaní duplikátov: ' + error);
                                    }
                                });
                            }
                        } else {
                            alert(message + '✓ Žiadne duplikáty nebyly nájdené.');
                        }
                    } else {
                        alert('Chyba: ' + (response.data.message || 'Neznáma chyba'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Check duplicates error:', error);
                    alert('AJAX chyba pri kontrole duplikátov: ' + error);
                }
            });
        });

        $(document).on('change', '#helpdesk-standby-csv-input', function() {
            const files = $(this).prop('files');
            if (files.length === 0) {
                return;
            }

            const file = files[0];
            const formData = new FormData();
            formData.append('csv_file', file);
            formData.append('action', 'helpdesk_import_standby');
            formData.append('_ajax_nonce', nonce);

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    let message = response.data.message || 'Import ukončený';
                    if (response.data.warnings && response.data.warnings.length > 0) {
                        message += '\n\nUpozornenia:\n';
                        response.data.warnings.slice(0, 5).forEach(w => {
                            message += '- ' + w + '\n';
                        });
                        if (response.data.warnings.length > 5) {
                            message += '... a ďalšie ' + (response.data.warnings.length - 5) + ' upozornení';
                        }
                    }
                    
                    if (response.success) {
                        alert('✓ ' + message);
                    } else {
                        alert('⚠ ' + message);
                    }
                    
                    // Reset file input
                    $(this).val('');
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.error('Import error:', error);
                    let errorMsg = 'Chyba pri importe';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.message) {
                            errorMsg = response.data.message;
                        }
                    } catch (e) {}
                    alert('Chyba: ' + errorMsg);
                    
                    // Reset file input
                    $(this).val('');
                }
            });
        });
    }

    // ===== COMMUNICATION METHODS =====
    function initCommunicationMethods() {
        console.log('=== initCommunicationMethods started ===');

        // Add new communication method button
        $(document).on('click', '.helpdesk-btn-new-communication-method', function() {
            $('#communication-method-id').val('');
            $('#helpdesk-communication-method-form')[0].reset();
            $('#communication-method-modal-title').text('Pridať spôsob komunikácie');
            $('#helpdesk-communication-method-modal').show();
            $('.error-message').text('');
        });

        // Close modal
        $(document).on('click', '#helpdesk-communication-method-modal .helpdesk-modal-close, #helpdesk-communication-method-modal .helpdesk-modal-close-btn', function() {
            $('#helpdesk-communication-method-modal').hide();
        });

        // Save communication method
        $(document).on('submit', '#helpdesk-communication-method-form', function(e) {
            e.preventDefault();
            const id = $('#communication-method-id').val();
            const nazov = $('#communication-method-nazov').val();
            const popis = $('#communication-method-popis').val();
            const priorita = $('#communication-method-priorita').val();
            const aktivny = $('#communication-method-aktivny').prop('checked') ? 1 : 0;

            const ajaxData = {
                action: 'helpdesk_save_communication_method',
                _ajax_nonce: nonce,
                id: id,
                nazov: nazov,
                popis: popis,
                priorita: priorita,
                aktivny: aktivny
            };

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: ajaxData,
                dataType: 'json',
                success: function(response) {
                    $('.error-message').text('');
                    if (response.success) {
                        alert(response.data.message || 'Uložené');
                        location.reload();
                    } else {
                        alert(response.data.message || 'Chyba pri uložení');
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX chyba: ' + error);
                }
            });
        });

        // Edit communication method
        $(document).on('click', '.helpdesk-btn-edit-communication-method', function() {
            const id = $(this).data('id');
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_get_communication_method',
                    _ajax_nonce: nonce,
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const method = response.data;
                        $('#communication-method-id').val(method.id);
                        $('#communication-method-nazov').val(method.nazov);
                        $('#communication-method-popis').val(method.popis || '');
                        $('#communication-method-priorita').val(method.priorita || 0);
                        $('#communication-method-aktivny').prop('checked', method.aktivny == 1);
                        $('#communication-method-modal-title').text('Upraviť spôsob komunikácie');
                        $('#helpdesk-communication-method-modal').show();
                        $('.error-message').text('');
                    } else {
                        alert(response.data.message || 'Chyba pri načítaní');
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX chyba: ' + error);
                }
            });
        });

        // Delete communication method
        $(document).on('click', '.helpdesk-btn-delete-communication-method', function() {
            if (confirm('Naozaj chcete zmazať tento spôsob komunikácie?')) {
                const id = $(this).data('id');
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_delete_communication_method',
                        _ajax_nonce: nonce,
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message || 'Vymazané');
                            location.reload();
                        } else {
                            alert(response.data.message || 'Chyba pri mazaní');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('AJAX chyba: ' + error);
                    }
                });
            }
        });

        // Search communication methods
        $(document).on('keyup', '#helpdesk-communication-methods-search', function() {
            const query = $(this).val().toLowerCase();
            $('#helpdesk-communication-methods-table tbody tr').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(query === '' || text.includes(query));
            });
        });

        console.log('=== initCommunicationMethods finished ===');
    }

    // ===== VACATIONS =====
    function initVacations() {
        console.log('=== Initializing Vacations ===');

        // Helper function to normalize text (remove diacritics - accents)
        function normalizeText(text) {
            return text
                .toLowerCase()
                .normalize('NFD')                   // Decompose accented characters
                .replace(/[\u0300-\u036f]/g, '')   // Remove combining diacritical marks
                .trim();
        }

        // ===== VACATIONS FILTERS =====
        function filterVacationsTable() {
            const selectedEmployee = $('#filter-vacation-employee').val();
            const normalizedSelectedEmployee = normalizeText(selectedEmployee);
            const dateFrom = $('#filter-vacation-date-from').val();
            const dateTo = $('#filter-vacation-date-to').val();
            const today = new Date().toISOString().split('T')[0];
            let visibleCount = 0;

            $('#helpdesk-vacations-table tbody tr').each(function() {
                const $row = $(this);
                const employee = normalizeText($row.data('employee'));
                const rowDateFrom = $row.data('date-from');
                const rowDateTo = $row.data('date-to');

                let show = true;

                // Employee filter (ignore diacritics)
                if (selectedEmployee && employee !== normalizedSelectedEmployee) {
                    show = false;
                }

                // Date from filter
                if (dateFrom && rowDateTo < dateFrom) {
                    show = false;
                }

                // Date to filter
                if (dateTo && rowDateFrom > dateTo) {
                    show = false;
                }

                $row.toggle(show);
                if (show) {
                    visibleCount++;
                }
            });

            // Update counter
            $('#vacation-count-after-filter').text(visibleCount);
        }

        // Bind vacation filter events
        $(document).on('change', '#filter-vacation-employee, #filter-vacation-date-from, #filter-vacation-date-to', function() {
            filterVacationsTable();
        });

        // Today button
        $(document).on('click', '#btn-vacation-today', function() {
            const today = new Date().toISOString().split('T')[0];
            $('#filter-vacation-employee').val('');
            $('#filter-vacation-date-from').val(today);
            $('#filter-vacation-date-to').val(today);
            filterVacationsTable();
        });

        // Reset vacation filters
        $(document).on('click', '#btn-reset-vacation-filters', function() {
            $('#filter-vacation-employee').val('');
            $('#filter-vacation-date-from').val('');
            $('#filter-vacation-date-to').val('');
            filterVacationsTable();
        });

        // Show sync/apply buttons only if vacations exist
        if ($('#helpdesk-vacations-table tbody tr').length > 1) {
            $('#helpdesk-sync-vacation-ids').show();
            $('#helpdesk-apply-vacations').show();
        }

        // Import vacations CSV
        $(document).on('click', '.helpdesk-btn-import-vacations', function() {
            $('#helpdesk-vacations-csv-input').click();
        });

        $(document).on('change', '#helpdesk-vacations-csv-input', function() {
            const file = $(this)[0].files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('csv_file', file);
            formData.append('action', 'helpdesk_import_vacations');
            formData.append('_ajax_nonce', nonce);

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#helpdesk-sync-vacation-ids').show();
                        location.reload();
                    } else {
                        alert('Chyba: ' + (response.data.message || 'Neznáma chyba'));
                    }
                },
                error: function() {
                    alert('Chyba pri importe');
                }
            });

            // Clear input
            $(this).val('');
        });

        // Sync vacation IDs
        $(document).on('click', '#helpdesk-sync-vacation-ids', function() {
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_sync_vacation_ids',
                    _ajax_nonce: nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#helpdesk-apply-vacations').show();
                        location.reload();
                    } else {
                        alert('Chyba: ' + (response.data.message || 'Neznáma chyba'));
                    }
                },
                error: function() {
                    alert('Chyba pri synchronizácii');
                }
            });
        });

        // Apply vacations
        $(document).on('click', '#helpdesk-apply-vacations', function() {
            if (!confirm('Naozaj chcete aplikovať nepritomnosti na pracovníkov?')) {
                return;
            }

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_apply_vacations',
                    _ajax_nonce: nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Chyba: ' + (response.data.message || 'Neznáma chyba'));
                    }
                },
                error: function() {
                    alert('Chyba pri aplikovaní dovoleniek');
                }
            });
        });

        // Delete vacation
        $(document).on('click', '.helpdesk-btn-delete-vacation', function() {
            if (!confirm('Naozaj chcete vymazať túto dovolenku?')) {
                return;
            }

            const vacationId = $(this).data('id');

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_delete_vacation',
                    _ajax_nonce: nonce,
                    id: vacationId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Chyba: ' + (response.data.message || 'Neznáma chyba'));
                    }
                },
                error: function() {
                    alert('Chyba pri mazaní dovolenky');
                }
            });
        });

        // Edit vacation
        $(document).on('click', '.helpdesk-btn-edit-vacation', function() {
            const vacationId = $(this).data('id');
            const row = $(this).closest('tr');
            
            const meno = row.find('.column-meno').text();
            const od = row.data('date-from');  // Use data attribute instead of text
            const do_text = row.data('date-to');  // Use data attribute instead of text

            $('#edit-vacation-id').val(vacationId);
            $('#edit-vacation-meno').val(meno);
            $('#edit-vacation-od').val(od);
            $('#edit-vacation-do').val(do_text);

            $('#helpdesk-vacation-edit-modal').show();
        });

        // Save edited vacation
        $(document).on('submit', '#helpdesk-vacation-edit-form', function(e) {
            e.preventDefault();

            const vacationId = $('#edit-vacation-id').val();
            const meno = $('#edit-vacation-meno').val();
            const od = $('#edit-vacation-od').val();
            const do_text = $('#edit-vacation-do').val();

            if (!meno || !od || !do_text) {
                alert('Vyplňte všetky polia');
                return;
            }

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_update_vacation',
                    _ajax_nonce: nonce,
                    id: vacationId,
                    meno_pracovnika: meno,
                    nepritomnost_od: od,
                    nepritomnost_do: do_text
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message || 'Nepritomnosť bola aktualizovaná');
                        $('#helpdesk-vacation-edit-modal').hide();
                        location.reload();
                    } else {
                        alert('Chyba: ' + (response.data.message || 'Neznáma chyba'));
                    }
                },
                error: function() {
                    alert('Chyba pri aktualizácii nepritomnosti');
                }
            });
        });

        // Close edit modal
        $(document).on('click', '#helpdesk-vacation-edit-modal .helpdesk-modal-close, #helpdesk-vacation-edit-modal .helpdesk-modal-close-btn', function() {
            $('#helpdesk-vacation-edit-modal').hide();
        });

        // Delete all vacations
        $(document).on('click', '#helpdesk-delete-all-vacations', function() {
            if (!confirm('Naozaj chcete vymazať VŠETKY nepritomnosti?')) {
                return;
            }

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_delete_all_vacations',
                    _ajax_nonce: nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Chyba: ' + (response.data.message || 'Neznáma chyba'));
                    }
                },
                error: function() {
                    alert('Chyba pri mazaní dovoleniek');
                }
            });
        });

        console.log('=== initVacations finished ===');
    }

    function initSignatures() {
        $(document).on('click', '.helpdesk-btn-new-signature', function() {
            $('#signature-id').val('');
            $('#helpdesk-signature-form')[0].reset();
            $('#signature-modal-title').text('Pridať podpis');
            $('#helpdesk-signature-modal').show();
            $('.error-message').text('');
        });

        $(document).on('click', '#helpdesk-signature-modal .helpdesk-modal-close, #helpdesk-signature-modal .helpdesk-modal-close-btn', function() {
            $('#helpdesk-signature-modal').hide();
        });

        $(document).on('submit', '#helpdesk-signature-form', function(e) {
            e.preventDefault();
            const id = $('#signature-id').val();
            const podpis = $('#signature-podpis').val();
            const text_podpisu = $('#signature-text-podpisu').val();
            const produkt_id = $('#signature-produkt').val();
            const pracovnik_id = $('#signature-pracovnik').val();

            if (!podpis) {
                alert('Podpis je povinný');
                return;
            }

            if (!produkt_id) {
                alert('Produkt je povinný');
                return;
            }

            if (!pracovnik_id) {
                alert('Pracovník je povinný');
                return;
            }

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_save_signature',
                    _ajax_nonce: nonce,
                    id: id,
                    podpis: podpis,
                    text_podpisu: text_podpisu,
                    produkt_id: produkt_id,
                    pracovnik_id: pracovnik_id
                },
                dataType: 'json',
                success: function(response) {
                    $('.error-message').text('');
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || 'Chyba pri uložení');
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX chyba: ' + error);
                }
            });
        });

        $(document).on('click', '#helpdesk-signatures-table .helpdesk-btn-edit-signature', function() {
            const id = $(this).data('id');
            console.log('Edit signature clicked, ID:', id);
            
            // Reset form before loading
            $('#helpdesk-signature-form')[0].reset();
            
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'helpdesk_get_signature',
                    _ajax_nonce: nonce,
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Signature loaded:', response.data);
                    if (response.success) {
                        const signature = response.data;
                        console.log('Setting text_podpisu to:', signature.text_podpisu);
                        $('#signature-id').val(signature.id);
                        $('#signature-podpis').val(signature.podpis);
                        $('#signature-text-podpisu').val(signature.text_podpisu || '');
                        console.log('Textarea value after set:', $('#signature-text-podpisu').val());
                        $('#signature-produkt').val(signature.produkt_id);
                        $('#signature-pracovnik').val(signature.pracovnik_id);
                        $('#signature-modal-title').text('Upraviť podpis');
                        $('#helpdesk-signature-modal').show();
                        $('.error-message').text('');
                    } else {
                        alert(response.data.message || 'Chyba pri načítaní');
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX chyba: ' + error);
                }
            });
        });

        $(document).on('click', '#helpdesk-signatures-table .helpdesk-btn-delete-signature', function() {
            if (confirm('Naozaj chcete zmazať tento podpis?')) {
                const id = $(this).data('id');
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'helpdesk_delete_signature',
                        _ajax_nonce: nonce,
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert(response.data.message || 'Chyba pri mazaní');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('AJAX chyba: ' + error);
                    }
                });
            }
        });

        console.log('=== initSignatures finished ===');
    }

    // ===== GENERAL GUIDES MODULE =====
    function initGeneralGuides() {
        console.log('=== General Guides Init Start ===');

        var $guideSearchInput = $('#helpdesk-guides-search');
        var $guideCategoryFilter = $('#helpdesk-guides-category');
        var $guideProductFilter = $('#helpdesk-guides-product');
        var $guidesTable = $('#helpdesk-guides-table');
        var $guidesTbody = $('#helpdesk-guides-table tbody');

        // Load products for dropdowns
        function loadProductsForGuides() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'helpdesk_get_products',
                    nonce: nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.products) {
                        var products = response.data.products;
                        var html = '<option value="">-- Všetky --</option>';
                        products.forEach(function(product) {
                            html += '<option value="' + product.id + '">' + product.nazov + '</option>';
                        });
                        $('#helpdesk-guides-product').html(html);
                        $('#guide-produkt').html('<option value="0">-- Nezvolené --</option>' + html.replace('-- Všetky --', '-- Nezvolené --'));
                        $('#link-produkt').html('<option value="0">-- Nezvolené --</option>' + html.replace('-- Všetky --', '-- Nezvolené --'));
                    }
                }
            });
        }

        loadProductsForGuides();

        // Filter table rows based on search, category, product
        function filterGuidesTable() {
            const searchVal = $guideSearchInput.val().toLowerCase();
            const categoryVal = $guideCategoryFilter.val().toLowerCase();
            const productVal = $guideProductFilter.val();

            $guidesTbody.find('tr').each(function() {
                const $row = $(this);
                const nazov = $row.data('nazov') ? $row.data('nazov').toLowerCase() : '';
                const kategoria = $row.data('kategoria') ? $row.data('kategoria').toLowerCase() : '';
                const produkt = $row.data('produkt') ? $row.data('produkt').toString() : '';
                
                let show = true;

                // Search filter
                if (searchVal && nazov.indexOf(searchVal) === -1) {
                    show = false;
                }

                // Category filter
                if (categoryVal && kategoria.indexOf(categoryVal) === -1) {
                    show = false;
                }

                // Product filter
                if (productVal && produkt !== productVal) {
                    show = false;
                }

                $row.toggle(show);
            });
        }

        // New guide button
        $(document).on('click', '.helpdesk-btn-new-guide, #btn-add-guide', function(e) {
            e.preventDefault();
            $('#guide-id').val('');
            $('#general-guide-form')[0].reset();
            $('#guide-modal-title').text('Pridať návod');
            $('#btn-delete-guide').hide();
            $('#guide-links-container').html('');
            $('#helpdesk-guide-modal').show();
            $('.error-message').text('');
        });

        // Close modals
        $(document).on('click', '.helpdesk-modal-close, .helpdesk-modal-close-btn', function() {
            $('.helpdesk-modal').hide();
        });

        // Search and filter guides
        $guideSearchInput.on('keyup', function() {
            filterGuidesTable();
        });

        $guideCategoryFilter.on('change', function() {
            filterGuidesTable();
        });

        $guideProductFilter.on('change', function() {
            filterGuidesTable();
        });

        // Reset filters
        $(document).on('click', '#btn-reset-guides-filters', function() {
            $('#helpdesk-guides-search').val('');
            $('#helpdesk-guides-category').val('');
            $('#helpdesk-guides-product').val('');
            filterGuidesTable();
        });

        // Edit guide
        $(document).on('click', '.helpdesk-btn-edit-guide', function(e) {
            e.stopPropagation();
            const $row = $(this).closest('tr');
            const guideId = $row.data('guide-id');
            loadGuideForEdit(guideId);
        });

        // Delete guide
        $(document).on('click', '.helpdesk-btn-delete-guide', function(e) {
            e.stopPropagation();
            const $row = $(this).closest('tr');
            const guideId = $row.data('guide-id');
            if (confirm('Naozaj chcete vymazať tento návod?')) {
                deleteGuide(guideId);
            }
        });

        // Load guide for editing
        function loadGuideForEdit(guideId) {
            $.ajax({
                url: ajaxurl,
                type: 'GET',
                data: {
                    action: 'helpdesk_get_general_guide',
                    nonce: nonce,
                    id: guideId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.guide) {
                        const guide = response.data.guide;
                        
                        $('#guide-id').val(guide.id);
                        $('#guide-nazov').val(guide.nazov || '');
                        $('#guide-kategoria').val(guide.kategoria || '');
                        $('#guide-produkt').val(guide.produkt || 0);
                        $('#guide-popis').val(guide.popis || '');
                        $('#guide-tagy').val(guide.tagy ? (Array.isArray(guide.tagy) ? guide.tagy.join(', ') : guide.tagy) : '');
                        $('#guide-aktivny').prop('checked', guide.aktivny == 1);
                        
                        $('#guide-modal-title').text('Editovať návod');
                        $('#btn-delete-guide').show();

                        // Load links
                        renderGuideLinks(guide.links || []);

                        $('#helpdesk-guide-modal').show();
                        $('.error-message').text('');
                    }
                }
            });
        }

        // Render guide links
        function renderGuideLinks(links) {
            let html = '';
            if (links && links.length > 0) {
                links.forEach(function(link) {
                    html += '<div class="guide-link-item">';
                    html += '<div>';
                    html += '<strong>' + link.nazov + '</strong><br>';
                    html += '<small><a href="' + link.url + '" target="_blank">' + link.url + '</a></small>';
                    html += '</div>';
                    html += '<div class="link-actions">';
                    html += '<button type="button" class="button button-small edit-guide-link" data-link-id="' + link.id + '">Editovať</button>';
                    html += '<button type="button" class="button button-small button-danger delete-guide-link" data-link-id="' + link.id + '">Vymazať</button>';
                    html += '</div>';
                    html += '</div>';
                });
            }
            $('#guide-links-container').html(html);
        }

        // Add link button
        $(document).on('click', '#btn-add-guide-link', function(e) {
            e.preventDefault();
            const guideId = $('#guide-id').val();
            if (!guideId) {
                alert('Najprv si ulož návod pred pridaním linky');
                return;
            }
            openLinkModal(0, guideId);
        });

        // Edit link
        $(document).on('click', '.edit-guide-link', function(e) {
            e.preventDefault();
            const linkId = $(this).data('link-id');
            const guideId = $('#guide-id').val();
            openLinkModal(linkId, guideId);
        });

        // Delete link
        $(document).on('click', '.delete-guide-link', function(e) {
            e.preventDefault();
            const linkId = $(this).data('link-id');
            if (confirm('Naozaj chcete vymazať túto linku?')) {
                deleteGuideLink(linkId);
            }
        });

        // Open link modal
        function openLinkModal(linkId, guideId) {
            $('#link-id').val(linkId || '');
            $('#link-navod-id').val(guideId);
            
            if (linkId) {
                // Edit mode - load link data
                // For now, we'll load from current links
                $('#btn-delete-guide-link').show();
            } else {
                // New mode
                $('#guide-link-form')[0].reset();
                $('#btn-delete-guide-link').hide();
            }
            
            $('#helpdesk-guide-link-modal').show();
        }

        // Save guide
        $(document).on('submit', '#general-guide-form', function(e) {
            e.preventDefault();
            const guideId = $('#guide-id').val();
            
            let tagy = $('#guide-tagy').val();
            if (tagy) {
                tagy = tagy.split(',').map(t => t.trim());
            } else {
                tagy = [];
            }
            
            const data = {
                action: 'helpdesk_save_general_guide',
                nonce: nonce,
                id: guideId || 0,
                nazov: $('#guide-nazov').val(),
                kategoria: $('#guide-kategoria').val(),
                produkt: $('#guide-produkt').val(),
                popis: $('#guide-popis').val(),
                tagy: JSON.stringify(tagy),
                aktivny: $('#guide-aktivny').is(':checked') ? 1 : 0
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        // If new guide, update ID
                        if (!guideId && response.data.guide) {
                            $('#guide-id').val(response.data.guide.id);
                        }
                        // Close modal and refresh page
                        $('#helpdesk-guide-modal').hide();
                        location.reload();
                    } else {
                        alert('Chyba: ' + response.data.message);
                    }
                }
            });
        });

        // Delete guide
        function deleteGuide(guideId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'helpdesk_delete_general_guide',
                    nonce: nonce,
                    id: guideId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#helpdesk-guide-modal').hide();
                        location.reload();
                    } else {
                        alert('Chyba: ' + response.data.message);
                    }
                }
            });
        }

        // Delete from button
        $(document).on('click', '#btn-delete-guide', function(e) {
            e.preventDefault();
            const guideId = $('#guide-id').val();
            if (guideId && confirm('Naozaj chcete vymazať tento návod a všetky jeho linky?')) {
                deleteGuide(guideId);
            }
        });

        // Save link
        $(document).on('submit', '#guide-link-form', function(e) {
            e.preventDefault();
            
            const data = {
                action: 'helpdesk_save_guide_link',
                nonce: nonce,
                id: $('#link-id').val() || 0,
                navod_id: $('#link-navod-id').val(),
                nazov: $('#link-nazov').val(),
                url: $('#link-url').val(),
                produkt: $('#link-produkt').val()
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#helpdesk-guide-link-modal').hide();
                        // Reload guide to refresh links
                        const guideId = $('#guide-id').val();
                        loadGuideForEdit(guideId);
                    } else {
                        alert('Chyba: ' + response.data.message);
                    }
                }
            });
        });

        // Delete link
        function deleteGuideLink(linkId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'helpdesk_delete_guide_link',
                    nonce: nonce,
                    id: linkId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#helpdesk-guide-link-modal').hide();
                        const guideId = $('#guide-id').val();
                        loadGuideForEdit(guideId);
                    } else {
                        alert('Chyba: ' + response.data.message);
                    }
                }
            });
        }

        console.log('=== initGeneralGuides finished ===');
    }

    function initGuideCategories() {
        console.log('=== Guide Categories Init Start ===');

        // New category button
        $(document).on('click', '.helpdesk-btn-new-category', function() {
            $('#category-id').val('');
            $('#helpdesk-category-form')[0].reset();
            $('#category-modal-title').text('Pridať kategóriu');
            $('#btn-delete-category').hide();
            $('#helpdesk-category-modal').show();
            $('.error-message').text('');
        });

        // Close modal
        $(document).on('click', '#helpdesk-category-modal .helpdesk-modal-close, #helpdesk-category-modal .helpdesk-modal-close-btn', function() {
            $('#helpdesk-category-modal').hide();
        });

        // Edit category
        $(document).on('click', '.helpdesk-btn-edit-category', function() {
            const id = $(this).data('id');
            const $row = $(this).closest('tr');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'helpdesk_get_guide_category',
                    nonce: nonce,
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.category) {
                        const cat = response.data.category;
                        $('#category-id').val(cat.id);
                        $('#category-nazov').val(cat.nazov || '');
                        $('#category-popis').val(cat.popis || '');
                        $('#category-poradie').val(cat.poradie || 0);
                        $('#category-aktivny').prop('checked', cat.aktivny == 1);
                        $('#category-modal-title').text('Upraviť kategóriu');
                        $('#btn-delete-category').show();
                        $('#helpdesk-category-modal').show();
                    }
                }
            });
        });

        // Delete category
        $(document).on('click', '.helpdesk-btn-delete-category', function() {
            const id = $(this).data('id');
            if (confirm('Naozaj chcete vymazať túto kategóriu?')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'helpdesk_delete_guide_category',
                        nonce: nonce,
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert('Chyba: ' + (response.data.message || ''));
                        }
                    }
                });
            }
        });

        // Save category
        $(document).on('submit', '#helpdesk-category-form', function(e) {
            e.preventDefault();
            const id = $('#category-id').val();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'helpdesk_save_guide_category',
                    nonce: nonce,
                    id: id || 0,
                    nazov: $('#category-nazov').val(),
                    popis: $('#category-popis').val(),
                    poradie: $('#category-poradie').val(),
                    aktivny: $('#category-aktivny').is(':checked') ? 1 : 0
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Chyba: ' + (response.data.message || ''));
                    }
                }
            });
        });

        console.log('=== Guide Categories Init End ===');
    }

    function initGuideLinks() {
        console.log('=== Guide Links Init Start ===');

        // New link button
        $(document).on('click', '.helpdesk-btn-new-link', function() {
            $('#link-id').val('');
            $('#helpdesk-link-form')[0].reset();
            $('#link-modal-title').text('Pridať linku');
            $('#btn-delete-link').hide();
            $('#helpdesk-link-modal').show();
            $('.error-message').text('');
        });

        // Close modal
        $(document).on('click', '#helpdesk-link-modal .helpdesk-modal-close, #helpdesk-link-modal .helpdesk-modal-close-btn', function() {
            $('#helpdesk-link-modal').hide();
        });

        // Edit link
        $(document).on('click', '.helpdesk-btn-edit-link', function() {
            const id = $(this).data('id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'helpdesk_get_guide_link',
                    nonce: nonce,
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.link) {
                        const link = response.data.link;
                        $('#link-id').val(link.id);
                        $('#link-nazov').val(link.nazov || '');
                        $('#link-url').val(link.url || '');
                        $('#link-typ').val(link.typ || '');
                        $('#link-aktivny').prop('checked', link.aktivny == 1);
                        $('#link-modal-title').text('Upraviť linku');
                        $('#btn-delete-link').show();
                        $('#helpdesk-link-modal').show();
                    }
                }
            });
        });

        // Delete link
        $(document).on('click', '.helpdesk-btn-delete-link', function() {
            const id = $(this).data('id');
            if (confirm('Naozaj chcete vymazať túto linku?')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'helpdesk_delete_guide_link',
                        nonce: nonce,
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert('Chyba: ' + (response.data.message || ''));
                        }
                    }
                });
            }
        });

        // Save link
        $(document).on('submit', '#helpdesk-link-form', function(e) {
            e.preventDefault();
            const id = $('#link-id').val();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'helpdesk_save_guide_link',
                    nonce: nonce,
                    id: id || 0,
                    nazov: $('#link-nazov').val(),
                    url: $('#link-url').val(),
                    typ: $('#link-typ').val(),
                    aktivny: $('#link-aktivny').is(':checked') ? 1 : 0
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Chyba: ' + (response.data.message || ''));
                    }
                }
            });
        });

        console.log('=== Guide Links Init End ===');
    }

})(jQuery);


