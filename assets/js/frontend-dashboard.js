/**
 * HelpDesk Frontend Dashboard
 */

jQuery(document).ready(function($) {
    const ajaxurl = window.helpdesk?.ajaxurl || '';
    const nonce = window.helpdesk?.nonce || '';

    console.log('Frontend dashboard initialized');

    // Tab switching
    $(document).on('click', '.frontend-tab-button', function() {
        const tab = $(this).data('tab');
        
        console.log('Switching to tab:', tab);
        
        $('.frontend-tab-button').removeClass('active');
        $(this).addClass('active');
        
        $('.frontend-tab-content').hide();
        $('#' + tab + '-tab').fadeIn();
    });

    // Project search
    $(document).on('keyup', '#frontend-project-search', function() {
        const searchVal = $(this).val().toLowerCase();
        
        console.log('Project search:', searchVal);
        
        if (searchVal.length === 0) {
            $('.project-card').show();
            return;
        }
        
        $('.project-card').each(function() {
            const text = $(this).text().toLowerCase();
            const shouldShow = text.includes(searchVal);
            $(this).toggle(shouldShow);
        });
    });

    // Bug search
    $(document).on('keyup', '#frontend-bug-search', function() {
        const searchVal = $(this).val().toLowerCase();
        
        console.log('Bug search:', searchVal);
        
        if (searchVal.length === 0) {
            $('#bugs-display tr').show();
            return;
        }
        
        $('#bugs-display tr').each(function() {
            const text = $(this).text().toLowerCase();
            const shouldShow = text.includes(searchVal);
            $(this).toggle(shouldShow);
        });
    });

    // View bug details
    $(document).on('click', '.frontend-view-bug', function() {
        const bugId = $(this).data('bug-id');
        
        console.log('Loading bug details for:', bugId);
        
        if (!ajaxurl || !nonce) {
            alert('AJAX nie je dostupný');
            return;
        }
        
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'helpdesk_get_bug',
                _ajax_nonce: nonce,
                id: bugId
            },
            dataType: 'json',
            success: function(response) {
                console.log('Bug response:', response);
                
                if (response.success) {
                    const bug = response.data.bug;
                    const signature = response.data.bug.signature;
                    
                    let html = '<div style="padding: 20px;">';
                    html += '<h3 style="margin: 0 0 20px 0; color: #0073aa;">' + escapeHtml(bug.nazov) + '</h3>';
                    html += '<table style="width: 100%; border-collapse: collapse;">';
                    html += '<tr><td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #ddd; width: 30%;">Produkt:</td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' + escapeHtml(bug.product_name || '-') + '</td></tr>';
                    html += '<tr><td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #ddd;">Popis:</td><td style="padding: 8px; border-bottom: 1px solid #ddd; word-break: break-word;">' + escapeHtml(bug.popis || '-') + '</td></tr>';
                    
                    let solutionText = bug.riesenie || '-';
                    if (signature && signature.text_podpisu) {
                        solutionText += '\n\n---\n' + signature.text_podpisu;
                    }
                    
                    html += '<tr><td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #ddd; vertical-align: top;">Riešenie:</td><td style="padding: 8px; border-bottom: 1px solid #ddd; word-break: break-word; white-space: pre-wrap; max-height: 300px; overflow-y: auto;">' + escapeHtml(solutionText) + '</td></tr>';
                    html += '</table>';
                    
                    if (bug.riesenie && bug.riesenie.trim()) {
                        html += '<div style="margin-top: 20px;">';
                        html += '<button type="button" class="button button-primary copy-solution-btn" data-solution="' + escapeHtml(bug.riesenie) + '" data-signature="' + escapeHtml((signature && signature.text_podpisu) ? signature.text_podpisu : '') + '" style="cursor: pointer;">📋 Kopírovať riešenie</button>';
                        html += '</div>';
                    }
                    
                    html += '</div>';
                    
                    $('#frontend-bug-detail-content').html(html);
                    $('#frontend-bug-detail-modal').fadeIn();
                } else {
                    alert('Chyba pri načítaní detailov: ' + (response.data.message || ''));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('AJAX chyba: ' + error);
            }
        });
    });

    // Copy solution
    $(document).on('click', '.copy-solution-btn', function() {
        let textToCopy = $(this).data('solution');
        const signature = $(this).data('signature');
        
        if (signature && signature.trim()) {
            textToCopy = textToCopy + '\n\n' + signature;
        }
        
        navigator.clipboard.writeText(textToCopy).then(function() {
            alert('✅ Riešenie skopírované do clipboardu');
        }).catch(function(err) {
            const textarea = document.createElement('textarea');
            textarea.value = textToCopy;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            alert('✅ Riešenie skopírované do clipboardu');
        });
    });

    // Close modal - click on modal background
    $(document).on('click', '#frontend-bug-detail-modal', function(e) {
        if ($(e.target).is('#frontend-bug-detail-modal')) {
            $(this).fadeOut();
        }
    });

    // Close modal - click on close button
    $(document).on('click', '#frontend-bug-detail-modal .close-btn', function() {
        $('#frontend-bug-detail-modal').fadeOut();
    });

    // Project details button handler
    $(document).on('click', '.project-card .button', function(e) {
        e.preventDefault();
        const projectId = $(this).closest('.project-card').data('project-id');
        
        if (!projectId) {
            alert('Projekt ID nie je dostupný');
            return;
        }
        
        console.log('Loading project details for ID:', projectId);
        
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'helpdesk_get_project',
                nonce: nonce,
                id: projectId
            },
            dataType: 'json',
            success: function(response) {
                console.log('Project response:', response);
                
                if (response.success && response.data.project) {
                    const project = response.data.project;
                    const employees = response.data.employees || [];
                    
                    let html = '<div style="padding: 20px;">';
                    html += '<h3 style="margin: 0 0 20px 0; color: #0073aa;">' + escapeHtml(project.nazov) + '</h3>';
                    html += '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';
                    html += '<tr><td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #ddd; width: 30%;">Popis:</td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' + escapeHtml(project.popis || '-') + '</td></tr>';
                    html += '<tr><td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #ddd;">Stav:</td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' + escapeHtml(project.stav || '-') + '</td></tr>';
                    html += '</table>';
                    
                    html += '<h4 style="margin: 20px 0 10px 0; color: #333;">Tím projektu:</h4>';
                    
                    if (employees && employees.length > 0) {
                        html += '<table style="width: 100%; border-collapse: collapse;">';
                        html += '<thead><tr style="background: #f5f5f5;"><th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: left;">Meno</th><th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: left;">Pozícia</th><th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: left;">Klapka</th><th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: left;">Status</th></tr></thead>';
                        html += '<tbody>';
                        
                        employees.forEach(function(emp) {
                            // Check if employee is on vacation
                            const isOnVacation = emp.nepritomnost_od && emp.nepritomnost_do && 
                                new Date(new Date().toISOString().split('T')[0]) >= new Date(emp.nepritomnost_od) && 
                                new Date(new Date().toISOString().split('T')[0]) <= new Date(emp.nepritomnost_do);
                            
                            const vacationStyle = isOnVacation ? 'text-decoration: line-through; color: #999;' : '';
                            const statusText = isOnVacation ? '🏖️ Na dovolenke' : '✅ Dostupný';
                            
                            html += '<tr style="border-bottom: 1px solid #ddd;">';
                            html += '<td style="padding: 8px; ' + vacationStyle + '">' + escapeHtml(emp.meno_priezvisko || '-') + '</td>';
                            html += '<td style="padding: 8px;">' + escapeHtml(emp.pozicia_nazov || '-') + '</td>';
                            html += '<td style="padding: 8px;">' + escapeHtml(emp.klapka || '-') + '</td>';
                            html += '<td style="padding: 8px; font-weight: bold;">' + statusText + '</td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table>';
                    } else {
                        html += '<p style="color: #999;">Žiadni pracovníci v tíme</p>';
                    }
                    
                    html += '</div>';
                    
                    $('#frontend-bug-detail-content').html(html);
                    $('#frontend-bug-detail-modal').fadeIn();
                } else {
                    alert('Chyba pri načítaní detailov: ' + (response.data.message || 'Neznáma chyba'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('AJAX chyba: ' + error);
            }
        });
    });

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
