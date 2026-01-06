<?php
/**
 * Products Admin View
 */

use HelpDesk\Models\Product;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$products = Product::get_all( false ); // Get all including inactive
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Produkty', HELPDESK_TEXT_DOMAIN ); ?></h1>

    <div class="helpdesk-admin-container">
        <div class="helpdesk-button-group">
            <button class="button button-primary helpdesk-btn-new-product">
                <?php echo esc_html__( '+ Pridať produkt', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
        </div>

        <table class="wp-list-table widefat fixed striped" id="helpdesk-products-table">
            <thead>
                <tr>
                    <th scope="col" class="column-nazov"><?php echo esc_html__( 'Názov', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-popis"><?php echo esc_html__( 'Popis', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-link"><?php echo esc_html__( 'Link', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-status"><?php echo esc_html__( 'Stav', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-actions"><?php echo esc_html__( 'Akcie', HELPDESK_TEXT_DOMAIN ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $products ) ) : ?>
                    <?php foreach ( $products as $product ) : ?>
                        <tr data-product-id="<?php echo absint( $product['id'] ); ?>">
                            <td class="column-nazov"><?php echo esc_html( $product['nazov'] ?? '--' ); ?></td>
                            <td class="column-popis"><?php echo esc_html( $product['popis'] ?? '--' ); ?></td>
                            <td class="column-link">
                                <?php if ( ! empty( $product['link'] ) ) : ?>
                                    <a href="<?php echo esc_url( $product['link'] ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( $product['link'] ); ?>">
                                        🔗 <?php echo esc_html( substr( $product['link'], 0, 30 ) ); ?><?php echo strlen( $product['link'] ) > 30 ? '...' : ''; ?>
                                    </a>
                                <?php else : ?>
                                    <span style="color: #999;">--</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-status">
                                <span class="status-badge <?php echo $product['aktivny'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $product['aktivny'] ? esc_html__( 'Aktívny', HELPDESK_TEXT_DOMAIN ) : esc_html__( 'Neaktívny', HELPDESK_TEXT_DOMAIN ); ?>
                                </span>
                            </td>
                            <td class="column-actions" style="text-align: center; font-size: 18px;">
                                <button class="button button-small helpdesk-btn-edit-product" data-id="<?php echo absint( $product['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Upraviť', HELPDESK_TEXT_DOMAIN ); ?>">
                                    ✏️
                                </button>
                                <button class="button button-small button-link-delete helpdesk-btn-delete-product" data-id="<?php echo absint( $product['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Zmazať', HELPDESK_TEXT_DOMAIN ); ?>">
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" class="center"><?php echo esc_html__( 'Žádné produkty nebyly nalezeny.', HELPDESK_TEXT_DOMAIN ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Product Modal -->
<div id="helpdesk-product-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content">
        <div class="helpdesk-modal-header">
            <h2 id="product-modal-title"><?php echo esc_html__( 'Pridať produkt', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close">&times;</button>
        </div>
        <form id="helpdesk-product-form" class="helpdesk-form">
            <input type="hidden" id="product-id" name="id" value="">

            <div class="form-group">
                <label for="product-nazov">
                    <?php echo esc_html__( 'Názov', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                </label>
                <input type="text" id="product-nazov" name="nazov" class="widefat" required>
                <span class="error-message" id="error-nazov"></span>
            </div>

            <div class="form-group">
                <label for="product-popis">
                    <?php echo esc_html__( 'Popis', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <textarea id="product-popis" name="popis" class="widefat" rows="3"></textarea>
                <span class="error-message" id="error-popis"></span>
            </div>

            <div class="form-group">
                <label for="product-link">
                    <?php echo esc_html__( 'Link', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <input type="url" id="product-link" name="link" class="widefat" placeholder="https://example.com">
                <span class="error-message" id="error-link"></span>
            </div>

            <div class="form-group">
                <label for="product-aktivny">
                    <input type="checkbox" id="product-aktivny" name="aktivny" value="1" checked>
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
