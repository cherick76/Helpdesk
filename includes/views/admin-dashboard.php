<?php
/**
 * Dashboard View
 */

use HelpDesk\Models\Employee;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$employees = Employee::get_all();
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

        <!-- RIGHT COLUMN: Application Support (Bugs) -->
        <div class="helpdesk-admin-container dashboard-column">
            <div class="dashboard-column-header">
                <h2><?php echo esc_html__( '🐛 Aplikačná podpora', HELPDESK_TEXT_DOMAIN ); ?></h2>
            </div>

            <div class="dashboard-search-box">
                <input 
                    type="text" 
                    id="dashboard-bug-search" 
                    class="helpdesk-search-input" 
                    placeholder="<?php echo esc_attr__( 'Zadajte názov chyby...', HELPDESK_TEXT_DOMAIN ); ?>"
                    autocomplete="off"
                >
                <div class="search-info"><?php echo esc_html__( 'Vyhľadávajte chyby podľa názvu alebo produktu', HELPDESK_TEXT_DOMAIN ); ?></div>
            </div>

            <div id="dashboard-bug-search-results" class="dashboard-results-container" style="display: none;">
                <div class="results-count">
                    <span id="dashboard-bug-results-count">0</span> <?php echo esc_html__( 'chýb', HELPDESK_TEXT_DOMAIN ); ?>
                </div>
                <table class="wp-list-table widefat fixed striped bugs-table">
                    <colgroup>
                        <col style="width: 40%;">
                        <col style="width: 30%;">
                        <col style="width: 20%;">
                        <col style="width: 10%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th><?php echo esc_html__( 'Názov', HELPDESK_TEXT_DOMAIN ); ?></th>
                            <th><?php echo esc_html__( 'Produkt', HELPDESK_TEXT_DOMAIN ); ?></th>
                            <th><?php echo esc_html__( 'Tagy', HELPDESK_TEXT_DOMAIN ); ?></th>
                            <th><?php echo esc_html__( 'Akcie', HELPDESK_TEXT_DOMAIN ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="dashboard-bug-search-results-tbody">
                    </tbody>
                </table>
            </div>

            <div id="dashboard-no-bugs" class="no-results-message">
                <p><?php echo esc_html__( 'Začnite písaním do vyhľadávacieho poľa...', HELPDESK_TEXT_DOMAIN ); ?></p>
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
