<?php
/**
 * Signatures Admin View (Application Support)
 */

use HelpDesk\Models\Signature;
use HelpDesk\Models\Product;
use HelpDesk\Models\Employee;
use HelpDesk\Utils\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$signatures = Signature::get_all();
$products = Product::get_all();

// Load APHD employees (employees with position "SWD Aplikačná podpora HD" - skratka APHD)
global $wpdb;
$employees_table = Database::get_employees_table();
$positions_table = Database::get_positions_table();
$aphd_employees = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT e.id, e.meno_priezvisko, p.profesia, p.skratka 
        FROM {$employees_table} e 
        LEFT JOIN {$positions_table} p ON e.pozicia_id = p.id 
        WHERE p.skratka = %s 
        ORDER BY e.meno_priezvisko ASC",
        'APHD'
    ),
    ARRAY_A
) ?: array();
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Podpisy', HELPDESK_TEXT_DOMAIN ); ?></h1>

    <div class="helpdesk-admin-container">
        <div class="helpdesk-button-group">
            <button class="button button-primary helpdesk-btn-new-signature">
                <?php echo esc_html__( '+ Pridať podpis', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
        </div>

        <table class="wp-list-table widefat fixed striped" id="helpdesk-signatures-table">
            <thead>
                <tr>
                    <th scope="col" class="column-podpis"><?php echo esc_html__( 'Podpis', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-produkt"><?php echo esc_html__( 'Produkt', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-pracovnik"><?php echo esc_html__( 'Pracovník', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-actions"><?php echo esc_html__( 'Akcie', HELPDESK_TEXT_DOMAIN ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $signatures ) ) : ?>
                    <?php foreach ( $signatures as $signature ) : ?>
                        <tr data-signature-id="<?php echo absint( $signature['id'] ); ?>">
                            <td class="column-podpis"><?php echo esc_html( $signature['podpis'] ?? '--' ); ?></td>
                            <td class="column-produkt"><?php echo esc_html( $signature['produkt_nazov'] ?? '--' ); ?></td>
                            <td class="column-pracovnik"><?php echo esc_html( $signature['meno_priezvisko'] ?? '--' ); ?></td>
                            <td class="column-actions" style="text-align: center; font-size: 18px;">
                                <button class="button button-small helpdesk-btn-edit-signature" data-id="<?php echo absint( $signature['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Upraviť', HELPDESK_TEXT_DOMAIN ); ?>">
                                    ✏️
                                </button>
                                <button class="button button-small button-link-delete helpdesk-btn-delete-signature" data-id="<?php echo absint( $signature['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Zmazať', HELPDESK_TEXT_DOMAIN ); ?>">
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4" class="center"><?php echo esc_html__( 'Žádné podpisy nebyly nalezeny.', HELPDESK_TEXT_DOMAIN ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Signature Modal -->
<div id="helpdesk-signature-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content">
        <div class="helpdesk-modal-header">
            <h2 id="signature-modal-title"><?php echo esc_html__( 'Pridať podpis', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close">&times;</button>
        </div>
        <form id="helpdesk-signature-form" class="helpdesk-form">
            <input type="hidden" id="signature-id" name="id" value="">

            <div class="form-group">
                <label for="signature-podpis">
                    <?php echo esc_html__( 'Podpis', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                </label>
                <input type="text" id="signature-podpis" name="podpis" class="widefat" required>
                <span class="error-message" id="error-podpis"></span>
            </div>

            <div class="form-group">
                <label for="signature-text-podpisu">
                    <?php echo esc_html__( 'Text podpisu', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <textarea id="signature-text-podpisu" name="text_podpisu" class="widefat" rows="5" style="font-family: monospace;"></textarea>
                <span class="error-message" id="error-text_podpisu"></span>
            </div>

            <div class="form-group">
                <label for="signature-produkt">
                    <?php echo esc_html__( 'Produkt', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                </label>
                <select id="signature-produkt" name="produkt_id" class="widefat" required>
                    <option value=""><?php echo esc_html__( '-- Vybrať produkt --', HELPDESK_TEXT_DOMAIN ); ?></option>
                    <?php foreach ( $products as $product ) : ?>
                        <option value="<?php echo absint( $product['id'] ); ?>">
                            <?php echo esc_html( $product['nazov'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="error-message" id="error-produkt_id"></span>
            </div>

            <div class="form-group">
                <label for="signature-pracovnik">
                    <?php echo esc_html__( 'Pracovník (APHD)', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                </label>
                <select id="signature-pracovnik" name="pracovnik_id" class="widefat" required>
                    <option value=""><?php echo esc_html__( '-- Vybrať pracovníka --', HELPDESK_TEXT_DOMAIN ); ?></option>
                    <?php foreach ( $aphd_employees as $employee ) : ?>
                        <option value="<?php echo absint( $employee['id'] ); ?>">
                            <?php echo esc_html( $employee['meno_priezvisko'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="error-message" id="error-pracovnik_id"></span>
            </div>

            <div class="form-actions">
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
