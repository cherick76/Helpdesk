/**
 * HelpDesk - Employees Module
 * Handles all employee management functionality
 */

window.HelpDeskEmployees = (function($) {
    'use strict';

    const nonce = window.helpdesk?.nonce || '';
    const ajaxurl = window.helpdesk?.ajaxurl || '';
    let selectedEmployees = [];

    /**
     * Initialize employees module
     */
    function init() {
        bindEvents();
        loadEmployeesList();
    }

    /**
     * Bind event handlers
     */
    function bindEvents() {
        $(document).on('click', '.helpdesk-btn-new-employee', handleNewEmployee);
        $(document).on('click', '.helpdesk-btn-edit-employee', handleEditEmployee);
        $(document).on('click', '.helpdesk-btn-delete-employee', handleDeleteEmployee);
        $(document).on('click', '.helpdesk-btn-export-employees', handleExportEmployees);
        $(document).on('click', '.helpdesk-btn-import-employees', handleImportClick);
        $(document).on('change', '#helpdesk-employees-csv-input', handleFileImport);
        $(document).on('keyup', '#helpdesk-employees-search', handleSearch);
        $(document).on('change', '.helpdesk-employee-checkbox', handleCheckbox);
        $(document).on('click', '#helpdesk-select-all-employees', handleSelectAll);
    }

    /**
     * Load employees list
     */
    function loadEmployeesList() {
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'helpdesk_get_employees',
                _ajax_nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    renderEmployeesList(response.data.employees);
                }
            }
        });
    }

    /**
     * Render employees table
     */
    function renderEmployeesList(employees) {
        let html = '';
        employees.forEach(function(emp) {
            html += '<tr data-employee-id="' + emp.id + '">';
            html += '<td><input type="checkbox" class="helpdesk-employee-checkbox" value="' + emp.id + '"></td>';
            html += '<td>' + emp.meno_priezvisko + '</td>';
            html += '<td>' + emp.klapka + '</td>';
            html += '<td>' + (emp.mobil || '') + '</td>';
            html += '<td><button class="button helpdesk-btn-edit-employee" data-id="' + emp.id + '">Upraviť</button></td>';
            html += '</tr>';
        });
        $('#helpdesk-employees-table tbody').html(html);
    }

    /**
     * Handle new employee button
     */
    function handleNewEmployee() {
        // TODO: Show new employee modal
    }

    /**
     * Handle edit employee
     */
    function handleEditEmployee() {
        const empId = $(this).data('id');
        // TODO: Show edit employee modal
    }

    /**
     * Handle delete employee
     */
    function handleDeleteEmployee() {
        if (!confirm('Naozaj chcete zmazať tohto pracovníka?')) return;

        const empId = $(this).closest('tr').data('employee-id');
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'helpdesk_delete_employee',
                _ajax_nonce: nonce,
                id: empId
            },
            success: function(response) {
                if (response.success) {
                    loadEmployeesList();
                }
            }
        });
    }

    /**
     * Handle export employees
     */
    function handleExportEmployees() {
        window.location.href = ajaxurl + '?action=helpdesk_export_employees&_wpnonce=' + nonce;
    }

    /**
     * Handle import button click
     */
    function handleImportClick() {
        $('#helpdesk-employees-csv-input').click();
    }

    /**
     * Handle file import
     */
    function handleFileImport() {
        const file = this.files[0];
        if (!file) return;

        startAsyncImport(file);
        this.value = '';
    }

    /**
     * Start async import
     */
    function startAsyncImport(file) {
        const formData = new FormData();
        formData.append('action', 'helpdesk_import_employees_start');
        formData.append('_ajax_nonce', nonce);
        formData.append('csv_file', file);

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showProgressModal(response.data);
                }
            }
        });
    }

    /**
     * Show import progress modal
     */
    function showProgressModal(sessionData) {
        const modalId = 'import-progress-' + Date.now();
        const html = buildProgressModal(modalId, sessionData);

        $('body').append('<div id="' + modalId + '-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000;"></div>');
        $('body').append(html);

        processBatch(sessionData.session_id, 1, sessionData.total_batches, 0, 0, modalId);
    }

    /**
     * Process batch
     */
    function processBatch(sessionId, batchNum, totalBatches, imported, updated, modalId) {
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'helpdesk_import_employees_batch',
                _ajax_nonce: nonce,
                session_id: sessionId,
                batch_num: batchNum
            },
            success: function(response) {
                if (response.success) {
                    updateProgress(response.data, modalId);

                    if (batchNum < totalBatches) {
                        setTimeout(function() {
                            processBatch(sessionId, batchNum + 1, totalBatches, imported + response.data.imported, updated + response.data.updated, modalId);
                        }, 100);
                    } else {
                        finishImport(imported + response.data.imported, updated + response.data.updated, modalId);
                    }
                }
            }
        });
    }

    /**
     * Update progress display
     */
    function updateProgress(data, modalId) {
        $('#' + modalId + ' #import-progress-bar').css('width', data.progress + '%').text(data.progress + '%');
        $('#' + modalId + ' #import-progress-text').text(data.batch_num + '/' + data.total_batches + ' dávok');
        $('#' + modalId + ' #import-status').text(data.message);
    }

    /**
     * Finish import
     */
    function finishImport(imported, updated, modalId) {
        $('#' + modalId + ' #import-progress-bar').css('background', '#28a745');
        $('#' + modalId + ' #import-status').text('✓ Hotovo!');

        setTimeout(function() {
            location.reload();
        }, 2000);
    }

    /**
     * Handle search
     */
    function handleSearch() {
        const searchVal = $(this).val().toLowerCase();
        filterTable('helpdesk-employees-table', searchVal);
    }

    /**
     * Handle checkbox
     */
    function handleCheckbox() {
        const empId = $(this).val();
        if ($(this).is(':checked')) {
            selectedEmployees.push(empId);
        } else {
            selectedEmployees = selectedEmployees.filter(id => id !== empId);
        }
    }

    /**
     * Handle select all
     */
    function handleSelectAll() {
        const isChecked = $(this).is(':checked');
        $('#helpdesk-employees-table input.helpdesk-employee-checkbox').prop('checked', isChecked);

        if (isChecked) {
            selectedEmployees = $('#helpdesk-employees-table input.helpdesk-employee-checkbox').map(function() {
                return $(this).val();
            }).get();
        } else {
            selectedEmployees = [];
        }
    }

    /**
     * Build progress modal HTML
     */
    function buildProgressModal(modalId, sessionData) {
        return '<div class="import-progress-modal" id="' + modalId + '" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10001; background: white; border: 2px solid #0073aa; border-radius: 5px; padding: 0; box-shadow: 0 5px 30px rgba(0,0,0,0.3); width: 500px;">' +
            '<div style="padding: 20px; background-color: #f9f9f9; border-bottom: 1px solid #ddd;">' +
            '<h2 style="margin: 0; color: #0073aa;">Import pracovníkov</h2>' +
            '</div>' +
            '<div style="padding: 20px;">' +
            '<p style="color: #666; margin: 0 0 15px 0;">Spracovávam ' + sessionData.total_rows + ' pracovníkov...</p>' +
            '<div style="width: 100%; height: 30px; background: #e0e0e0; border-radius: 15px; overflow: hidden; margin-bottom: 10px;">' +
            '<div id="import-progress-bar" style="height: 100%; background: linear-gradient(90deg, #0073aa, #00a0d2); width: 0%; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">0%</div>' +
            '</div>' +
            '<div style="display: flex; justify-content: space-between; margin-bottom: 15px;">' +
            '<span id="import-progress-text">0/' + sessionData.total_batches + ' dávok</span>' +
            '<span id="import-status">Inicializácia...</span>' +
            '</div>' +
            '<div style="text-align: right;">' +
            '<button class="button button-secondary" onclick="$(\'#' + modalId + '-overlay\').remove(); $(\'#' + modalId + '\').remove();">Zrušiť</button>' +
            '</div>' +
            '</div>' +
            '</div>';
    }

    // Public API
    return {
        init: init,
        loadList: loadEmployeesList
    };
})(jQuery);

// Initialize when document ready
jQuery(document).ready(function() {
    window.HelpDeskEmployees.init();
});
