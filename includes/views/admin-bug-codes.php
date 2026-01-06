<?php
/**
 * Bug Codes Admin View
 */

use HelpDesk\Models\BugCode;
use HelpDesk\Models\Product;
use HelpDesk\Models\OperatingSystem;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$codes = BugCode::get_all( false ); // Get all including inactive
$products = Product::get_all();
$operating_systems = OperatingSystem::get_all();
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Problémy', HELPDESK_TEXT_DOMAIN ); ?></h1>

    <div class="helpdesk-admin-container">
        <div class="helpdesk-button-group">
            <button class="button button-primary helpdesk-btn-new-code">
                <?php echo esc_html__( '+ Pridať problém', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
        </div>

        <!-- Filters -->
        <div style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px;">
                <div>
                    <label for="filter-search" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Hľadať', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <input type="text" id="filter-search" class="widefat" placeholder="<?php echo esc_attr__( 'Kód, Popis...', HELPDESK_TEXT_DOMAIN ); ?>" style="padding: 8px;">
                </div>
                <div>
                    <label for="filter-os" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Operačný systém', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="filter-os" class="widefat" style="padding: 8px;">
                        <option value=""><?php echo esc_html__( 'Všetky OS', HELPDESK_TEXT_DOMAIN ); ?></option>
                        <?php foreach ( $operating_systems as $os ) : ?>
                            <option value="<?php echo esc_attr( $os['nazov'] ); ?>"><?php echo esc_html( $os['nazov'] ); ?> <?php echo ! empty( $os['zkratka'] ) ? '(' . esc_html( $os['zkratka'] ) . ')' : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter-product" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Produkt', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="filter-product" class="widefat" style="padding: 8px;">
                        <option value=""><?php echo esc_html__( 'Všetky produkty', HELPDESK_TEXT_DOMAIN ); ?></option>
                        <?php foreach ( $products as $product ) : ?>
                            <option value="<?php echo absint( $product['id'] ); ?>"><?php echo esc_html( $product['nazov'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter-status" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Stav', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="filter-status" class="widefat" style="padding: 8px;">
                        <option value=""><?php echo esc_html__( 'Všetky', HELPDESK_TEXT_DOMAIN ); ?></option>
                        <option value="1"><?php echo esc_html__( 'Aktívne', HELPDESK_TEXT_DOMAIN ); ?></option>
                        <option value="0"><?php echo esc_html__( 'Neaktívne', HELPDESK_TEXT_DOMAIN ); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped" id="helpdesk-codes-table">
            <thead>
                <tr>
                    <th scope="col" class="column-kod"><?php echo esc_html__( 'Kód', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-popis"><?php echo esc_html__( 'Popis', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-os"><?php echo esc_html__( 'OS', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-product"><?php echo esc_html__( 'Produkt', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-status"><?php echo esc_html__( 'Stav', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-actions"><?php echo esc_html__( 'Akcie', HELPDESK_TEXT_DOMAIN ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $codes ) ) : ?>
                    <?php foreach ( $codes as $code ) : ?>
                        <tr data-code-id="<?php echo absint( $code['id'] ); ?>" data-os="<?php echo esc_attr( $code['operacny_system'] ?? '' ); ?>" data-product="<?php echo esc_attr( $code['produkt'] ?? '' ); ?>" data-status="<?php echo esc_attr( $code['aktivny'] ?? 1 ); ?>">
                            <td class="column-kod"><?php echo esc_html( $code['kod'] ?? '--' ); ?></td>
                            <td class="column-popis"><?php echo esc_html( $code['popis'] ?? '--' ); ?></td>
                            <td class="column-os"><?php echo esc_html( $code['operacny_system'] ?? '--' ); ?></td>
                            <td class="column-product">
                                <?php 
                                if ( ! empty( $code['produkt'] ) ) {
                                    foreach ( $products as $product ) {
                                        if ( $product['id'] == $code['produkt'] ) {
                                            echo esc_html( $product['nazov'] );
                                            break;
                                        }
                                    }
                                } else {
                                    echo '--';
                                }
                                ?>
                            </td>
                            <td class="column-status">
                                <span class="status-badge <?php echo $code['aktivny'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $code['aktivny'] ? esc_html__( 'Aktívny', HELPDESK_TEXT_DOMAIN ) : esc_html__( 'Neaktívny', HELPDESK_TEXT_DOMAIN ); ?>
                                </span>
                            </td>
                            <td class="column-actions" style="text-align: center; font-size: 18px;">
                                <button class="button button-small helpdesk-btn-edit-code" data-id="<?php echo absint( $code['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Upraviť', HELPDESK_TEXT_DOMAIN ); ?>">
                                    ✏️
                                </button>
                                <button class="button button-small button-link-delete helpdesk-btn-delete-code" data-id="<?php echo absint( $code['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Zmazať', HELPDESK_TEXT_DOMAIN ); ?>">
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" class="center"><?php echo esc_html__( 'Žádné kódy nebyly nalezeny.', HELPDESK_TEXT_DOMAIN ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bug Code Modal -->
<div id="helpdesk-code-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content">
        <div class="helpdesk-modal-header">
            <h2 id="code-modal-title"><?php echo esc_html__( 'Pridať problém', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close">&times;</button>
        </div>
        <form id="helpdesk-code-form" class="helpdesk-form">
            <input type="hidden" id="code-id" name="id" value="">

            <div class="form-group">
                <label for="code-kod">
                    <?php echo esc_html__( 'Kód', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                </label>
                <input type="text" id="code-kod" name="kod" class="widefat" required placeholder="napr. ERR-001">
                <span class="error-message" id="error-kod"></span>
            </div>

            <div class="form-group">
                <label for="code-popis">
                    <?php echo esc_html__( 'Popis', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <textarea id="code-popis" name="popis" class="widefat" rows="3"></textarea>
                <span class="error-message" id="error-popis"></span>
            </div>

            <div class="form-group">
                <label for="code-uplny_popis">
                    <?php echo esc_html__( 'Úplný popis chyby', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <textarea id="code-uplny_popis" name="uplny_popis" class="widefat" rows="5" placeholder="Detailný popis chyby, príčiny a riešenia..."></textarea>
                <span class="error-message" id="error-uplny_popis"></span>
            </div>

            <div class="form-group">
                <label for="code-operacny_system">
                    <?php echo esc_html__( 'Operačný systém (OS)', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <select id="code-operacny_system" name="operacny_system" class="widefat">
                    <option value=""><?php echo esc_html__( 'Vybrať OS...', HELPDESK_TEXT_DOMAIN ); ?></option>
                    <?php foreach ( $operating_systems as $os ) : ?>
                        <option value="<?php echo esc_attr( $os['nazov'] ); ?>"><?php echo esc_html( $os['nazov'] ); ?> <?php echo ! empty( $os['zkratka'] ) ? '(' . esc_html( $os['zkratka'] ) . ')' : ''; ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="error-message" id="error-operacny_system"></span>
            </div>

            <div class="form-group">
                <label for="code-produkt">
                    <?php echo esc_html__( 'Produkt', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <select id="code-produkt" name="produkt" class="widefat">
                    <option value=""><?php echo esc_html__( 'Vybrať produkt...', HELPDESK_TEXT_DOMAIN ); ?></option>
                    <?php foreach ( $products as $product ) : ?>
                        <option value="<?php echo absint( $product['id'] ); ?>"><?php echo esc_html( $product['nazov'] ); ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="error-message" id="error-produkt"></span>
            </div>

            <div class="form-group">
                <label for="code-aktivny">
                    <input type="checkbox" id="code-aktivny" name="aktivny" value="1" checked>
                    <?php echo esc_html__( 'Aktívny', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
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

<style>
    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 500;
    }
    .status-active {
        background-color: #d4edda;
        color: #155724;
    }
    .status-inactive {
        background-color: #f8d7da;
        color: #721c24;
    }
</style>
