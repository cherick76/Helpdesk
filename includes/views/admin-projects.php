<?php
/**
 * Projects Admin View
 */

use HelpDesk\Models\Project;
use HelpDesk\Models\Employee;
use HelpDesk\Models\CommunicationMethod;
use HelpDesk\Models\Position;
use HelpDesk\Utils\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Initialize database and get tables - MUST BE FIRST
global $wpdb;
$projects_table = Database::get_projects_table();
$employees_table = Database::get_employees_table();
$positions_table = Database::get_positions_table();

// Pagination setup for projects
$per_page = 50;
$current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
$offset = ( $current_page - 1 ) * $per_page;

// Get dashboard filter settings
$dashboard_filters = get_option( 'helpdesk_dashboard_filters', array(
    'show_nw_projects' => false,
) );
$show_nw_projects = (bool) $dashboard_filters['show_nw_projects'];

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM {$projects_table}";
if ( ! $show_nw_projects ) {
    $count_query .= " WHERE nazov NOT LIKE '%nw%'";
}
$total_projects = $wpdb->get_var( $count_query );
$total_pages = ceil( $total_projects / $per_page );

// Build WHERE clause for -nw filter - without prepare since we interpolate directly
$where_clause = '';
if ( ! $show_nw_projects ) {
    $where_clause = " WHERE p.nazov NOT LIKE '%nw%'";
}

// Direct SQL query to get projects with all fields INCLUDING PM and SLA names
// NOTE: WHERE clause is interpolated directly, not via prepare() to avoid parameter confusion
$sql = "SELECT p.*, 
         pm.meno_priezvisko as pm_name,
         sla.meno_priezvisko as sla_name
         FROM {$projects_table} p
         LEFT JOIN {$employees_table} pm ON p.pm_manazer_id = pm.id
         LEFT JOIN {$employees_table} sla ON p.sla_manazer_id = sla.id
         {$where_clause}
         ORDER BY p.nazov ASC
         LIMIT " . absint($offset) . ", " . absint($per_page);

error_log("DEBUG: Projects SQL Query: " . $sql);
$projects = $wpdb->get_results( $sql, ARRAY_A );
error_log("DEBUG: Projects count: " . count($projects));
if (!empty($projects)) {
    error_log("DEBUG: First project: " . print_r($projects[0], true));
}

$employees = Employee::get_all( array( 'limit' => 999999 ) );
$communication_methods = CommunicationMethod::get_all( array( 'limit' => 999999 ) );

// Load PM managers (employees with position "SWD Projektový manažér" - skratka PM)
$pm_managers = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT e.id, e.meno_priezvisko, p.profesia, p.skratka 
        FROM {$employees_table} e 
        LEFT JOIN {$positions_table} p ON e.pozicia_id = p.id 
        WHERE p.skratka = %s 
        ORDER BY e.meno_priezvisko ASC",
        'PM'
    ),
    ARRAY_A
) ?: array();

// Load SLA managers (employees with position "SWD SLA manažér" - skratka SLA)
$sla_managers = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT e.id, e.meno_priezvisko, p.profesia, p.skratka 
        FROM {$employees_table} e 
        LEFT JOIN {$positions_table} p ON e.pozicia_id = p.id 
        WHERE p.skratka = %s 
        ORDER BY e.meno_priezvisko ASC",
        'SLA'
    ),
    ARRAY_A
) ?: array();
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Projekty', HELPDESK_TEXT_DOMAIN ); ?></h1>

    <script>
        const nonce = <?php echo json_encode( wp_create_nonce( 'helpdesk-nonce' ) ); ?>;
    </script>

    <div class="helpdesk-admin-container">
        <div class="helpdesk-button-group">
            <button class="button button-primary helpdesk-btn-new-project">
                <?php echo esc_html__( '+ Pridať projekt', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
            <button class="button helpdesk-btn-export-projects">
                <?php echo esc_html__( 'Exportovať do CSV', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
            <button class="button helpdesk-btn-import-projects">
                <?php echo esc_html__( 'Importovať z CSV', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
        </div>

        <div style="margin-bottom: 20px; display: flex; gap: 10px;">
            <input type="text" id="helpdesk-projects-search" class="helpdesk-search-input" placeholder="<?php echo esc_attr__( 'Vyhľadať projekt...', HELPDESK_TEXT_DOMAIN ); ?>" style="flex: 1;">
            <button id="btn-reset-projects-filter" class="button" style="padding: 8px 12px;">
                <?php echo esc_html__( '✕ Vynulovať', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
        </div>

        <!-- Hidden file input for CSV import -->
        <input type="file" id="helpdesk-projects-csv-input" accept=".csv" style="display: none;">

        <div class="helpdesk-table-wrapper">
        <table class="wp-list-table widefat fixed striped" id="helpdesk-projects-table">
            <thead>
                <tr>
                    <th scope="col" class="column-zakaznicke-cislo"><?php echo esc_html__( 'Projekt', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-spobsob-komunikacie"><?php echo esc_html__( 'Spôsob Komunikácie', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-pm"><?php echo esc_html__( 'PM', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-sla"><?php echo esc_html__( 'SLA', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-pracovnici"><?php echo esc_html__( 'Pracovníci', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-actions"><?php echo esc_html__( 'Akcie', HELPDESK_TEXT_DOMAIN ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $projects ) ) : ?>
                    <?php foreach ( $projects as $project ) : ?>
                        <tr data-project-id="<?php echo absint( $project['id'] ); ?>">
                            <td class="column-zakaznicke-cislo">
                                <strong><?php echo esc_html( $project['zakaznicke_cislo'] ?? '' ); ?></strong>
                                <?php if ( isset( $project['nazov'] ) && ! empty( $project['nazov'] ) ) : ?>
                                    <?php echo esc_html( $project['nazov'] ); ?>
                                <?php endif; ?>
                            </td>
                            <td class="column-spobsob-komunikacie"><?php echo esc_html( $project['hd_kontakt'] ?? '' ); ?></td>
                            <td class="column-pm"><?php if ( ! empty( $project['pm_manazer_id'] ) ) : ?><?php echo esc_html( $project['pm_name'] ?? '' ); ?><?php endif; ?></td>
                            <td class="column-sla"><?php if ( ! empty( $project['sla_manazer_id'] ) ) : ?><?php echo esc_html( $project['sla_name'] ?? '' ); ?><?php endif; ?></td>
                            <td class="column-pracovnici">
                                <span class="project-employees-display" data-project-id="<?php echo absint( $project['id'] ); ?>">
                                    <!-- Naplnené JavaScriptom -->
                                </span>
                            </td>
                            <td class="column-actions" style="text-align: center; font-size: 18px;">
                                <button class="button button-small helpdesk-btn-edit" data-id="<?php echo absint( $project['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Upraviť', HELPDESK_TEXT_DOMAIN ); ?>">
                                    ✏️
                                </button>
                                <button class="button button-small button-link-delete helpdesk-btn-delete" data-id="<?php echo absint( $project['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Zmazať', HELPDESK_TEXT_DOMAIN ); ?>">
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3" class="center"><?php echo esc_html__( 'Žádné projekty nebyly nalezeny.', HELPDESK_TEXT_DOMAIN ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <!-- Pagination -->
        <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav bottom" style="margin-top: 20px;">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf( esc_html__( '%d z %d položiek', HELPDESK_TEXT_DOMAIN ), count( $projects ), $total_projects ); ?></span>
                <span class="pagination-links">
                    <?php if ( $current_page > 1 ) : ?>
                        <a class="first-page button" href="<?php echo esc_url( add_query_arg( 'paged', '1' ) ); ?>" title="<?php esc_attr_e( 'Prvá stránka', HELPDESK_TEXT_DOMAIN ); ?>">&laquo;</a>
                        <a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1 ) ); ?>" title="<?php esc_attr_e( 'Predchádzajúca stránka', HELPDESK_TEXT_DOMAIN ); ?>">&lsaquo;</a>
                    <?php else : ?>
                        <span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>
                        <span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>
                    <?php endif; ?>

                    <span class="paging-input">
                        <label for="current-page-selector" class="screen-reader-text"><?php esc_html_e( 'Zvolte stránku', HELPDESK_TEXT_DOMAIN ); ?></label>
                        <input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo absint( $current_page ); ?>" size="3" aria-describedby="table-paging">
                        <span class="tablenav-paging-text"> <?php printf( esc_html__( 'z %d', HELPDESK_TEXT_DOMAIN ), $total_pages ); ?></span>
                    </span>

                    <?php if ( $current_page < $total_pages ) : ?>
                        <a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1 ) ); ?>" title="<?php esc_attr_e( 'Nasledujúca stránka', HELPDESK_TEXT_DOMAIN ); ?>">&rsaquo;</a>
                        <a class="last-page button" href="<?php echo esc_url( add_query_arg( 'paged', $total_pages ) ); ?>" title="<?php esc_attr_e( 'Posledná stránka', HELPDESK_TEXT_DOMAIN ); ?>">&raquo;</a>
                    <?php else : ?>
                        <span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>
                        <span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Project Modal -->
<div id="helpdesk-project-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content">
        <div class="helpdesk-modal-header">
            <h2 id="project-modal-title"><?php echo esc_html__( 'Pridať projekt', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close">&times;</button>
        </div>
        <div class="helpdesk-modal-body">
            <form id="helpdesk-project-form" class="helpdesk-form">
                <input type="hidden" id="project-id" name="id" value="">
                <input type="hidden" id="project-employees-selected" value="">

                <!-- Zákaznícke číslo -->
                <div class="form-group">
                    <label for="project-zakaznicke_cislo">
                        <?php echo esc_html__( 'Zákaznícke Číslo', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                    </label>
                    <input type="text" id="project-zakaznicke_cislo" name="zakaznicke_cislo" required maxlength="255">
                    <span class="error-message" id="error-zakaznicke_cislo"></span>
                </div>

                <!-- HD Kontakt -->
                <div class="form-group">
                    <label for="project-hd_kontakt">
                        <?php echo esc_html__( 'HD Kontakt - Spôsob Komunikácie', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="project-hd_kontakt" name="hd_kontakt" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                        <option value="">-- <?php echo esc_html__( 'Vyberte spôsob komunikácie', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                        <?php foreach ( $communication_methods as $method ) : ?>
                            <option value="<?php echo esc_attr( $method['nazov'] ); ?>">
                                <?php echo esc_html( $method['nazov'] ); ?>
                                <?php if ( $method['popis'] ) : ?>
                                    (<?php echo esc_html( $method['popis'] ); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message" id="error-hd_kontakt"></span>
                </div>

                <!-- PM - Projektový Manažér -->
                <div class="form-group">
                    <label for="project-pm_manazer_id">
                        <?php echo esc_html__( 'PM - Projektový Manažér', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="project-pm_manazer_id" name="pm_manazer_id" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                        <option value="">-- <?php echo esc_html__( 'Bez PM', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                        <?php foreach ( $pm_managers as $pm ) : ?>
                            <option value="<?php echo absint( $pm['id'] ); ?>">
                                <?php echo esc_html( $pm['meno_priezvisko'] ); ?>
                                <?php if ( $pm['skratka'] ) : ?>
                                    (<?php echo esc_html( $pm['skratka'] ); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message" id="error-pm_manazer_id"></span>
                </div>

                <!-- SLA - SLA Manažér -->
                <div class="form-group">
                    <label for="project-sla_manazer_id">
                        <?php echo esc_html__( 'SLA - SLA Manažér', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="project-sla_manazer_id" name="sla_manazer_id" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                        <option value="">-- <?php echo esc_html__( 'Bez SLA', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                        <?php foreach ( $sla_managers as $sla ) : ?>
                            <option value="<?php echo absint( $sla['id'] ); ?>">
                                <?php echo esc_html( $sla['meno_priezvisko'] ); ?>
                                <?php if ( $sla['skratka'] ) : ?>
                                    (<?php echo esc_html( $sla['skratka'] ); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message" id="error-sla_manazer_id"></span>
                </div>

                <!-- Pracovníci (iba zobrazenie) -->
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label><?php echo esc_html__( 'Pracovníci', HELPDESK_TEXT_DOMAIN ); ?></label>
                    <div id="project-employees-display-modal" style="border: 1px solid #ddd; border-radius: 3px; padding: 12px; background-color: #f9f9f9; min-height: 60px;">
                        <span style="color: #999; font-size: 13px;"><?php echo esc_html__( 'Žádný pracovník', HELPDESK_TEXT_DOMAIN ); ?></span>
                    </div>
                    <button type="button" id="project-edit-employees-btn" class="button" style="margin-top: 8px; width: 100%;">
                        <?php echo esc_html__( '✎ Upraviť pracovníkov', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                </div>

                <!-- Nemenit flag -->
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="project-poznamka">
                        <?php echo esc_html__( 'Poznámka', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <textarea id="project-poznamka" name="poznamka" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px; min-height: 60px;"></textarea>
                </div>

                <!-- Nemenit flag -->
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                        <input type="checkbox" id="project-nemenit" name="nemenit" style="width: auto; margin: 0;">
                        <span style="color: #d32f2f; font-weight: 600;"><?php echo esc_html__( '🔒 Nemeniť pri importe pohotovosti', HELPDESK_TEXT_DOMAIN ); ?></span>
                    </label>
                    <p style="margin: 6px 0 0 26px; font-size: 12px; color: #666;">
                        <?php echo esc_html__( 'Ak je zaškrtnuté: pri importe pohotovosti sa pracovníci projektu nebudú meniť. Nový pracovník sa naimportuje ale nebude priradený na projekt.', HELPDESK_TEXT_DOMAIN ); ?>
                    </p>
                </div>

                <!-- Tlačidlá -->
                <div class="form-actions" style="grid-column: 1 / -1; margin-top: 16px;">
                    <button type="submit" class="button button-primary">
                        <?php echo esc_html__( 'Uložiť', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                    <button type="button" class="button helpdesk-modal-close-btn">
                        <?php echo esc_html__( 'Zrušiť', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal na úpravu pracovníkov projektu -->
<div id="helpdesk-project-employees-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content">
        <div class="helpdesk-modal-header">
            <h2><?php echo esc_html__( 'Upraviť pracovníkov', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close">&times;</button>
        </div>
        <div class="helpdesk-modal-body">
            <input type="hidden" id="project-employees-json" value="<?php echo esc_attr( wp_json_encode( $employees ) ); ?>">
            <div style="display: flex; gap: 8px; margin-bottom: 12px; align-items: center;">
                <input type="text" id="project-employees-search" placeholder="<?php echo esc_attr__( 'Vyhľadať...', HELPDESK_TEXT_DOMAIN ); ?>" style="flex: 1; padding: 5px 6px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
                <button type="button" id="project-employees-toggle" class="button" style="white-space: nowrap; padding: 5px 10px; font-size: 12px;">
                    <?php echo esc_html__( 'Všetci', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
            </div>
            <div id="project-employees-list" class="employees-list" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; border: 1px solid #ddd; border-radius: 3px; padding: 8px; max-height: 300px; overflow-y: auto; background-color: #f9f9f9;">
                <!-- Naplnené JavaScriptom -->
            </div>
            <div class="form-actions" style="grid-column: 1 / -1; margin-top: 16px;">
                <button type="button" id="project-save-employees-btn" class="button button-primary">
                    <?php echo esc_html__( 'Potvrdiť', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
                <button type="button" class="button helpdesk-modal-close-btn">
                    <?php echo esc_html__( 'Zrušiť', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
            </div>
        </div>
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
    background-color: rgba(0, 0, 0, 0.4);
}

.helpdesk-modal-content {
    background-color: #fefefe;
    margin: 3% auto;
    padding: 12px;
    border: 1px solid #888;
    border-radius: 5px;
    width: 95%;
    max-width: 900px;
    max-height: 85vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.helpdesk-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    flex-shrink: 0;
}

.helpdesk-modal-header h2 {
    margin: 0;
    font-size: 18px;
}

.helpdesk-modal-close {
    font-size: 24px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
    border: none;
    background: none;
}

.helpdesk-modal-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    overflow-y: auto;
    flex-grow: 1;
    padding-bottom: 10px;
}

.form-group {
    margin-bottom: 8px;
}

.form-group label {
    display: block;
    margin-bottom: 3px;
    font-weight: 600;
    font-size: 12px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 5px 6px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 12px;
    box-sizing: border-box;
}

.form-group input[type="checkbox"] {
    width: auto;
    height: 16px;
    width: 16px;
    padding: 0;
    margin-right: 6px;
}

.form-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 12px;
    grid-column: 1 / -1;
}

.form-actions button {
    padding: 6px 12px;
    font-size: 12px;
}

.error-message {
    display: block;
    color: #d32f2f;
    font-size: 11px;
    margin-top: 2px;
}

.required {
    color: #d32f2f;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.helpdesk-button-group {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.helpdesk-search-input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 3px;
    min-width: 250px;
    display: block !important;
    visibility: visible !important;
}

.column-name {
    width: 250px;
}

.column-cislo, .column-zakaznik, .column-actions {
    width: 150px;
}

.employees-list {
    border: 1px solid #ddd;
    border-radius: 3px;
    max-height: 300px;
    overflow-y: auto;
    padding: 10px;
}

.employee-item {
    margin-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.employee-item:last-child {
    border-bottom: none;
}

.employee-checkbox {
    display: flex;
    align-items: center;
    padding: 8px 0;
    gap: 10px;
    cursor: pointer;
}

.employee-checkbox input[type="checkbox"] {
    margin-right: 0;
    cursor: pointer;
    width: 18px;
    height: 18px;
}

.employee-checkbox label {
    margin: 0;
    cursor: pointer;
    flex: 1;
}

.employee-checkbox input[type="radio"] {
    margin: 0 0 0 20px;
    cursor: pointer;
    width: 18px;
    height: 18px;
}

.employee-main-radio {
    display: none;
}

.employee-main-radio input[type="radio"] {
    margin: 0;
}
</style>


