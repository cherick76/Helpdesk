/**
 * HelpDesk Frontend
 */

console.log('frontend.js loading...');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM ready, initializing HelpDesk frontend');
    
    // Check for jQuery availability
    if (typeof jQuery !== 'undefined') {
        console.log('jQuery available');
        initializeJQuery();
    } else {
        console.log('jQuery not available, using vanilla JS only');
    }
    
    initializeVanillaJS();
});

// jQuery-based initialization (for tab navigation, etc)
function initializeJQuery() {
    if (typeof jQuery === 'undefined') {
        console.log('jQuery not available, skipping jQuery-based initialization');
        return;
    }
    
    console.log('Initializing jQuery components');
    
    // No need for jQuery(document).ready since we're already in DOMContentLoaded
    var $ = jQuery;
    
    // Tab Navigation
    $('.helpdesk-frontend-nav .nav-link').on('click', function(e) {
        e.preventDefault();
        
        const section = $(this).data('section');
        console.log('Switching to section:', section);
        
        // Update active nav
        $('.helpdesk-frontend-nav .nav-link').removeClass('active');
        $(this).addClass('active');
        
        // Update active section
        $('.helpdesk-section').removeClass('active');
        $('#' + section).addClass('active');
    });
}

// Vanilla JavaScript initialization (for search, etc)
function initializeVanillaJS() {
    console.log('Initializing vanilla JS components');
    
    // Project search (Dashboard) - Use event delegation for better performance
    const projectSearchInput = document.getElementById('dashboard-project-search');
    if (projectSearchInput) {
        console.log('✓ Project search input found');
        projectSearchInput.addEventListener('keyup', function(e) {
            const val = this.value.toLowerCase();
            console.log('Project search filtering by:', val);
            
            const container = this.closest('.helpdesk-admin-container');
            if (!container) {
                console.log('✗ Project container not found');
                return;
            }
            
            const items = container.querySelectorAll('.project-result-item');
            console.log('Found ' + items.length + ' project items to filter');
            
            let visibleCount = 0;
            items.forEach((item, index) => {
                const text = item.textContent.toLowerCase();
                const matches = text.includes(val) || val === '';
                item.style.display = matches ? 'block' : 'none';
                if (matches) visibleCount++;
            });
            console.log('Visible projects after filter:', visibleCount);
        });
    } else {
        console.log('✗ Project search input NOT found - checking DOM');
        console.log('Available inputs:', document.querySelectorAll('input[type="text"]'));
    }

    // Bug search (Dashboard)
    const bugSearchInput = document.getElementById('dashboard-bug-search');
    if (bugSearchInput) {
        console.log('✓ Bug search input found');
        bugSearchInput.addEventListener('keyup', function(e) {
            const val = this.value.toLowerCase();
            console.log('Bug search filtering by:', val);
            
            const container = this.closest('.helpdesk-admin-container');
            if (!container) {
                console.log('✗ Bug container not found');
                return;
            }
            
            const items = container.querySelectorAll('.bug-result-item');
            console.log('Found ' + items.length + ' bug items to filter');
            
            let visibleCount = 0;
            items.forEach((item, index) => {
                const text = item.textContent.toLowerCase();
                const matches = text.includes(val) || val === '';
                item.style.display = matches ? 'block' : 'none';
                if (matches) visibleCount++;
            });
            console.log('Visible bugs after filter:', visibleCount);
        });
    } else {
        console.log('✗ Bug search input NOT found');
    }

    // Projects page search
    const projectsSearchInput = document.getElementById('projects-search');
    if (projectsSearchInput) {
        console.log('✓ Projects search input found');
        projectsSearchInput.addEventListener('keyup', function(e) {
            const val = this.value.toLowerCase();
            
            const cards = document.querySelectorAll('.project-card');
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = (text.includes(val) || val === '') ? 'block' : 'none';
            });
        });
    }

    // Bugs page search
    const bugsSearchInput = document.getElementById('bugs-search');
    if (bugsSearchInput) {
        console.log('✓ Bugs search input found');
        bugsSearchInput.addEventListener('keyup', function(e) {
            const val = this.value.toLowerCase();
            
            const rows = document.querySelectorAll('#bugs-table-body tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = (text.includes(val) || val === '') ? 'block' : 'none';
            });
        });
    }

    // Bug detail loading from clicks
    document.addEventListener('click', function(e) {
        const target = e.target;
        
        // Check if clicked element or parent is a bug result item
        let bugElement = null;
        if (target.classList.contains('frontend-view-bug')) {
            bugElement = target;
        } else if (target.classList.contains('bug-result-item')) {
            bugElement = target;
        } else if (target.closest('.bug-result-item')) {
            bugElement = target.closest('.bug-result-item');
        }
        
        if (bugElement) {
            const bugId = bugElement.dataset.bugId;
            if (bugId) {
                console.log('Loading bug details for ID:', bugId);
                loadBugDetails(bugId);
            }
        }
    });
}

// Load bug details via Fetch API
function loadBugDetails(bugId) {
    const helpdesk = window.helpdesk || {};
    
    const formData = new FormData();
    formData.append('action', 'helpdesk_get_bug');
    formData.append('_ajax_nonce', helpdesk.nonce || '');
    formData.append('id', bugId);

    console.log('Fetching bug details:', {
        url: helpdesk.ajaxurl,
        action: 'helpdesk_get_bug',
        id: bugId
    });

    fetch(helpdesk.ajaxurl || '', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(detailResponse => {
        console.log('Received response:', detailResponse);
        if (detailResponse.success) {
            const bugData = detailResponse.data.bug;
            displayBugModal(bugData, bugId);
        }
    })
    .catch(error => {
        console.error('Error loading bug details:', error);
        alert('Chyba pri načítavaní detailov riešenia.');
    });
}

// Display bug modal
function displayBugModal(bugData, bugId) {
    console.log('Displaying bug modal for:', bugData.nazov);
    
    let html = '<div style="padding: 20px; background: white; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">';
    html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
    html += '<h3 style="margin: 0;">📋 Detail riešenia</h3>';
    html += '<button type="button" class="button-link" style="color: #666; font-size: 24px; cursor: pointer; border: none; background: none;" onclick="closeBugModal()">✕</button>';
    html += '</div>';
    
    html += '<table style="width: 100%; border-collapse: collapse;">';
    html += '<tr><td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #ddd;">Názov:</td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' + (bugData.nazov || '-') + '</td></tr>';
    html += '<tr><td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #ddd;">Produkt:</td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' + (bugData.product_name || '-') + '</td></tr>';
    html += '<tr><td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #ddd; vertical-align: top;">Popis:</td><td style="padding: 8px; border-bottom: 1px solid #ddd; word-break: break-word; max-width: 400px;">' + (bugData.popis || '-') + '</td></tr>';
    
    // Build solution text with signature if exists
    let solutionDisplay = bugData.riesenie || '-';
    console.log('Bug signature:', bugData.signature);
    if (bugData.signature && bugData.signature.text_podpisu && bugData.signature.text_podpisu.trim()) {
        solutionDisplay = solutionDisplay + '\n\n---\n' + bugData.signature.text_podpisu;
    }
    
    html += '<tr><td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #ddd; vertical-align: top;">Riešenie:</td><td style="padding: 8px; border-bottom: 1px solid #ddd; word-break: break-word; max-width: 400px; white-space: pre-wrap;">' + solutionDisplay + '</td></tr>';
    
    html += '</table>';
    
    // Add copy button if solution exists
    if (bugData.riesenie && bugData.riesenie.trim()) {
        html += '<div style="margin-top: 15px;">';
        html += '<button type="button" class="button copy-solution-btn" data-solution="' + bugData.riesenie.replace(/"/g, '&quot;') + '" data-signature="' + (bugData.signature && bugData.signature.text_podpisu ? bugData.signature.text_podpisu.replace(/"/g, '&quot;') : '') + '">Kopírovať riešenie do clipboardu</button>';
        html += '</div>';
    }
    
    html += '</div>';
    
    const modalId = 'bug-modal-' + bugId;
    let existing = document.getElementById(modalId);
    if (existing) {
        existing.remove();
    }
    
    const modal = document.createElement('div');
    modal.id = modalId;
    modal.className = 'helpdesk-bug-modal';
    modal.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10000; max-width: 600px; max-height: 80vh; overflow-y: auto; background: white; border: 2px solid #0073aa; border-radius: 5px; padding: 0; box-shadow: 0 5px 30px rgba(0,0,0,0.3);';
    modal.innerHTML = html;
    document.body.appendChild(modal);
    
    if (!document.getElementById('bug-modal-backdrop')) {
        const backdrop = document.createElement('div');
        backdrop.id = 'bug-modal-backdrop';
        backdrop.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999;';
        backdrop.onclick = closeBugModal;
        document.body.appendChild(backdrop);
    }
    
    // Add copy button handler
    const copyBtn = modal.querySelector('.copy-solution-btn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            let textToCopy = this.dataset.solution;
            const signatureText = this.dataset.signature;
            
            // Append signature if exists
            if (signatureText && signatureText.trim()) {
                textToCopy = textToCopy + '\n\n' + signatureText;
            }
            
            // Copy to clipboard
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(textToCopy).then(function() {
                    alert('Riešenie bolo skopírované do clipboardu!');
                }).catch(function() {
                    fallbackCopyToClipboard(textToCopy);
                });
            } else {
                fallbackCopyToClipboard(textToCopy);
            }
        });
    }
}

// Close bug modal
function closeBugModal() {
    const allBugModals = document.querySelectorAll('.helpdesk-bug-modal');
    allBugModals.forEach(m => m.remove());
    const backdrop = document.getElementById('bug-modal-backdrop');
    if (backdrop) {
        backdrop.remove();
    }
}

// Fallback copy to clipboard
function fallbackCopyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    alert('Riešenie bolo skopírované do clipboardu!');
}

console.log('frontend.js loaded successfully');
