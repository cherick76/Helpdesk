<?php
/**
 * Bugs Admin View
 */

use HelpDesk\Models\Bug;
use HelpDesk\Models\BugCode;
use HelpDesk\Models\Product;
use HelpDesk\Models\OperatingSystem;
use HelpDesk\Models\Signature;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$bugs = Bug::get_all();
$bug_codes = BugCode::get_all();
$products = Product::get_all();
$operating_systems = OperatingSystem::get_all();
$signatures = Signature::get_all();
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Riešenia', HELPDESK_TEXT_DOMAIN ); ?></h1>

    <div class="helpdesk-admin-container">
        <div class="helpdesk-button-group">
            <button class="button button-primary helpdesk-btn-new-bug">
                <?php echo esc_html__( '+ Pridať riešenie', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
            <button class="button helpdesk-btn-export-bugs">
                <?php echo esc_html__( 'Exportovať do CSV', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
            <button class="button helpdesk-btn-import-bugs">
                <?php echo esc_html__( 'Importovať z CSV', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
        </div>

        <div style="margin-bottom: 20px;">
            <input type="text" id="helpdesk-bugs-search" class="helpdesk-search-input" placeholder="<?php echo esc_attr__( 'Vyhľadať chybu...', HELPDESK_TEXT_DOMAIN ); ?>">
        </div>

        <!-- Filters -->
        <div style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px;">
                <div>
                    <label for="filter-bugs-search" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Vyhľadávanie', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <input type="text" id="filter-bugs-search" class="widefat" style="padding: 8px;" placeholder="<?php echo esc_attr__( 'Hľadať...', HELPDESK_TEXT_DOMAIN ); ?>">
                </div>

                <div>
                    <label for="filter-bugs-os" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Operačný systém', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="filter-bugs-os" class="widefat" style="padding: 8px;">
                        <option value=""><?php echo esc_html__( 'Všetky OS', HELPDESK_TEXT_DOMAIN ); ?></option>
                        <?php foreach ( $operating_systems as $os ) : ?>
                            <option value="<?php echo esc_attr( $os['nazov'] ); ?>"><?php echo esc_html( $os['nazov'] ); ?> <?php echo ! empty( $os['zkratka'] ) ? '(' . esc_html( $os['zkratka'] ) . ')' : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="filter-bugs-product" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Produkt', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="filter-bugs-product" class="widefat" style="padding: 8px;">
                        <option value=""><?php echo esc_html__( 'Všetky produkty', HELPDESK_TEXT_DOMAIN ); ?></option>
                        <?php foreach ( $products as $product ) : ?>
                            <option value="<?php echo esc_attr( $product['nazov'] ); ?>"><?php echo esc_html( $product['nazov'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 5px; opacity: 0;">
                        <?php echo esc_html__( 'Tlačidlá', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <button id="btn-reset-bugs-filters" class="button" style="width: 100%; padding: 8px;">
                        <?php echo esc_html__( 'Vynulovať filtre', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden file input for CSV import -->
        <input type="file" id="helpdesk-bugs-csv-input" accept=".csv" style="display: none;">

        <table class="wp-list-table widefat fixed striped" id="helpdesk-bugs-table">
            <thead>
                <tr>
                    <th scope="col" class="column-kod"><?php echo esc_html__( 'Kód', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-name"><?php echo esc_html__( 'Názov', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-produkt"><?php echo esc_html__( 'Produkt', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-tagy"><?php echo esc_html__( 'Tagy', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-datum"><?php echo esc_html__( 'Dátum', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-actions"><?php echo esc_html__( 'Akcie', HELPDESK_TEXT_DOMAIN ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $bugs ) ) : ?>
                    <?php foreach ( $bugs as $bug ) : ?>
                        <?php 
                        // Get product name
                        $product_name = '--';
                        if ( ! empty( $bug['produkt'] ) ) {
                            $product = new Product( $bug['produkt'] );
                            if ( $product->exists() ) {
                                $product_name = $product->get( 'nazov' );
                            }
                        }
                        
                        // Get OS from bug code if applicable
                        $bug_os = '--';
                        if ( ! empty( $bug['kod_chyby'] ) ) {
                            $bug_code = new BugCode( null );
                            $codes_by_kod = array_filter( $bug_codes, function( $c ) use ( $bug ) {
                                return $c['kod'] === $bug['kod_chyby'];
                            });
                            if ( ! empty( $codes_by_kod ) ) {
                                $code = reset( $codes_by_kod );
                                $bug_os = $code['operacny_system'] ?? '--';
                            }
                        }
                        
                        // Parse tags
                        $tagy = array();
                        if ( ! empty( $bug['tagy'] ) ) {
                            $decoded = json_decode( $bug['tagy'], true );
                            $tagy = is_array( $decoded ) ? $decoded : array();
                        }
                        ?>
                        <tr data-bug-id="<?php echo absint( $bug['id'] ); ?>" data-product="<?php echo esc_attr( $product_name ); ?>" data-os="<?php echo esc_attr( $bug_os ); ?>">
                            <td class="column-kod"><?php echo esc_html( $bug['kod_chyby'] ?? '--' ); ?></td>
                            <td><?php echo esc_html( $bug['nazov'] ?? '--' ); ?></td>
                            <td class="column-produkt"><?php echo esc_html( $product_name ); ?></td>
                            <td class="column-tagy">
                                <?php if ( ! empty( $tagy ) ) : ?>
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                        <?php foreach ( $tagy as $tag ) : ?>
                                            <span style="background-color: #e7f3ff; color: #0073aa; padding: 2px 6px; border-radius: 3px; font-size: 12px; white-space: nowrap;">
                                                <?php echo esc_html( $tag ); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else : ?>
                                    <span style="color: #999;">--</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-datum">
                                <?php echo esc_html( wp_date( 'd.m.Y H:i', strtotime( $bug['datum_zaznamu'] ?? '' ) ) ); ?>
                            </td>
                            <td class="column-actions" style="text-align: center; font-size: 18px;">
                                <button class="button button-small helpdesk-btn-edit-bug" data-id="<?php echo absint( $bug['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Upraviť', HELPDESK_TEXT_DOMAIN ); ?>">
                                    ✏️
                                </button>
                                <button class="button button-small button-link-delete helpdesk-btn-delete-bug" data-id="<?php echo absint( $bug['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Zmazať', HELPDESK_TEXT_DOMAIN ); ?>">
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" class="center"><?php echo esc_html__( 'Žádné chyby nebyly nalezeny.', HELPDESK_TEXT_DOMAIN ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bug Modal -->
<div id="helpdesk-bug-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content">
        <div class="helpdesk-modal-header">
            <h2 id="bug-modal-title"><?php echo esc_html__( 'Pridať riešenie', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close">&times;</button>
        </div>
        <div class="helpdesk-modal-body">
            <form id="helpdesk-bug-form" class="helpdesk-form" style="display: contents;">
                <input type="hidden" id="bug-id" name="id" value="">
                <input type="hidden" id="bug-uplny_popis" value="">

                <div class="form-group">
                    <label for="bug-nazov">
                        <?php echo esc_html__( 'Názov Riešenia', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                    </label>
                    <input type="text" id="bug-nazov" name="nazov" required>
                    <span class="error-message" id="error-nazov"></span>
                </div>

                <div class="form-group">
                    <label for="bug-kod_chyby">
                        <?php echo esc_html__( 'Kód chyby', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="bug-kod_chyby" name="kod_chyby">
                        <option value=""><?php echo esc_html__( '-- Vyberte kód --', HELPDESK_TEXT_DOMAIN ); ?></option>
                    </select>
                    <span class="error-message" id="error-kod_chyby"></span>
                </div>

                <div class="form-group">
                    <label for="bug-produkt">
                        <?php echo esc_html__( 'Produkt', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                    </label>
                    <select id="bug-produkt" name="produkt" required>
                        <option value=""><?php echo esc_html__( '-- Vyberte produkt --', HELPDESK_TEXT_DOMAIN ); ?></option>
                    </select>
                    <span class="error-message" id="error-produkt"></span>
                </div>

                <div class="form-group">
                    <label for="bug-tagy">
                        <?php echo esc_html__( 'Tagy', HELPDESK_TEXT_DOMAIN ); ?>
                        <span style="color: #999; font-size: 11px;">(<?php echo esc_html__( 'čiarkami', HELPDESK_TEXT_DOMAIN ); ?>)</span>
                    </label>
                    <input type="text" id="bug-tagy" name="tagy" placeholder="napr. kriticky, database">
                    <span class="error-message" id="error-tagy"></span>
                </div>

                <div class="form-group">
                    <label for="bug-popis">
                        <?php echo esc_html__( 'Popis problému', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <textarea id="bug-popis" name="popis" rows="3"></textarea>
                    <span class="error-message" id="error-popis"></span>
                </div>

                <div class="form-group">
                    <label for="bug-riesenie">
                        <?php echo esc_html__( 'Popis riešenia - 1. krok', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <textarea id="bug-riesenie" name="riesenie" rows="3"></textarea>
                    <button type="button" id="btn-copy-solution" class="button" style="margin-top: 5px;">
                        <?php echo esc_html__( 'Kopírovať do clipboardu', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                    <span class="error-message" id="error-riesenie"></span>
                </div>

                <div class="form-group">
                    <label for="bug-riesenie-2">
                        <?php echo esc_html__( 'Popis riešenia - 2. krok', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <textarea id="bug-riesenie-2" name="riesenie_2" rows="3"></textarea>
                    <button type="button" id="btn-copy-solution-2" class="button" style="margin-top: 5px;">
                        <?php echo esc_html__( 'Kopírovať do clipboardu', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                    <span class="error-message" id="error-riesenie_2"></span>
                </div>

                <div class="form-group">
                    <label for="bug-podpis">
                        <?php echo esc_html__( 'Podpis', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="bug-podpis" name="podpis_id" data-signatures='<?php echo json_encode( array_map( function( $sig ) { return array( 'id' => $sig['id'], 'podpis' => $sig['podpis'], 'text_podpisu' => $sig['text_podpisu'] ?? '' ); }, $signatures ) ); ?>'>
                        <option value=""><?php echo esc_html__( '-- Vyberte podpis --', HELPDESK_TEXT_DOMAIN ); ?></option>
                        <?php foreach ( $signatures as $signature ) : ?>
                            <option value="<?php echo absint( $signature['id'] ); ?>">
                                <?php echo esc_html( $signature['podpis'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-message" id="error-podpis_id"></span>
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
</div>

<style>
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-novy {
    background-color: #e7f3ff;
    color: #0073aa;
}

.status-rozpracovany {
    background-color: #fff8e5;
    color: #8b6914;
}

.status-vyrieseny {
    background-color: #d4edda;
    color: #155724;
}

.status-zavrety {
    background-color: #f8f9fa;
    color: #666;
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
}

.column-name {
    width: 250px;
}

.column-projekt, .column-stav, .column-datum, .column-actions {
    width: 150px;
}

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

.form-group textarea {
    resize: vertical;
    grid-column: 1 / -1;
    min-height: 60px;
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
</style>


