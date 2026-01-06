<?php
/**
 * General Guides Admin View
 */

use HelpDesk\Models\GeneralGuide;
use HelpDesk\Models\Product;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$guides = GeneralGuide::get_all_active();
$products = Product::get_all();
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Všeobecné návody', HELPDESK_TEXT_DOMAIN ); ?></h1>

    <div class="helpdesk-admin-container">
        <div class="helpdesk-button-group">
            <button class="button button-primary helpdesk-btn-new-guide">
                <?php echo esc_html__( '+ Pridať návod', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
        </div>

        <!-- Filters -->
        <div style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px;">
                <div>
                    <label for="helpdesk-guides-search" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Vyhľadávanie', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <input type="text" id="helpdesk-guides-search" class="widefat" style="padding: 8px;" placeholder="<?php echo esc_attr__( 'Hľadať...', HELPDESK_TEXT_DOMAIN ); ?>">
                </div>

                <div>
                    <label for="helpdesk-guides-category" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Kategória', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <input type="text" id="helpdesk-guides-category" class="widefat" style="padding: 8px;" placeholder="<?php echo esc_attr__( 'Kategória...', HELPDESK_TEXT_DOMAIN ); ?>">
                </div>

                <div>
                    <label for="helpdesk-guides-product" style="display: block; font-weight: 500; margin-bottom: 5px;">
                        <?php echo esc_html__( 'Produkt', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="helpdesk-guides-product" class="widefat" style="padding: 8px;">
                        <option value=""><?php echo esc_html__( 'Všetky produkty', HELPDESK_TEXT_DOMAIN ); ?></option>
                        <?php foreach ( $products as $product ) : ?>
                            <option value="<?php echo esc_attr( $product['id'] ); ?>"><?php echo esc_html( $product['nazov'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 5px; opacity: 0;">
                        <?php echo esc_html__( 'Tlačidlá', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <button id="btn-reset-guides-filters" class="button" style="width: 100%; padding: 8px;">
                        <?php echo esc_html__( 'Vynulovať filtre', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped" id="helpdesk-guides-table">
            <thead>
                <tr>
                    <th scope="col" class="column-nazov"><?php echo esc_html__( 'Názov', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-kategoria"><?php echo esc_html__( 'Kategória', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-produkt"><?php echo esc_html__( 'Produkt', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-tagy"><?php echo esc_html__( 'Tagy', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-status"><?php echo esc_html__( 'Stav', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-actions"><?php echo esc_html__( 'Akcie', HELPDESK_TEXT_DOMAIN ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $guides ) ) : ?>
                    <?php foreach ( $guides as $guide ) : ?>
                        <tr data-guide-id="<?php echo absint( $guide->id ); ?>" data-nazov="<?php echo esc_attr( $guide->nazov ?? '' ); ?>" data-kategoria="<?php echo esc_attr( $guide->kategoria ?? '' ); ?>" data-produkt="<?php echo absint( $guide->produkt ?? 0 ); ?>">
                            <td class="column-nazov"><strong><?php echo esc_html( $guide->nazov ?? '--' ); ?></strong></td>
                            <td class="column-kategoria"><?php echo esc_html( $guide->kategoria ?? '--' ); ?></td>
                            <td class="column-produkt">
                                <?php 
                                $product_name = '--';
                                if ( ! empty( $guide->produkt ) ) {
                                    $product = array_filter( $products, fn( $p ) => $p['id'] == $guide->produkt );
                                    if ( $product ) {
                                        $product_name = reset( $product )['nazov'] ?? '--';
                                    }
                                }
                                echo esc_html( $product_name );
                                ?>
                            </td>
                            <td class="column-tagy">
                                <?php if ( ! empty( $guide->tagy ) ) : ?>
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                        <?php 
                                        $tagy = json_decode( $guide->tagy );
                                        if ( is_array( $tagy ) ) {
                                            foreach ( $tagy as $tag ) {
                                                echo '<span style="background-color: #e7f3ff; color: #0073aa; padding: 2px 6px; border-radius: 3px; font-size: 12px; white-space: nowrap;">' . esc_html( $tag ) . '</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="column-status">
                                <span class="status-badge <?php echo $guide->aktivny ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $guide->aktivny ? esc_html__( 'Aktívny', HELPDESK_TEXT_DOMAIN ) : esc_html__( 'Neaktívny', HELPDESK_TEXT_DOMAIN ); ?>
                                </span>
                            </td>
                            <td class="column-actions" style="text-align: center; font-size: 18px;">
                                <button class="button button-small helpdesk-btn-edit-guide" data-id="<?php echo absint( $guide->id ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Upraviť', HELPDESK_TEXT_DOMAIN ); ?>">
                                    ✏️
                                </button>
                                <button class="button button-small button-link-delete helpdesk-btn-delete-guide" data-id="<?php echo absint( $guide->id ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Zmazať', HELPDESK_TEXT_DOMAIN ); ?>">
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" class="center"><?php echo esc_html__( 'Žádné návody nebyly nalezeny.', HELPDESK_TEXT_DOMAIN ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Guide Modal -->
<div id="helpdesk-guide-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content" style="width: 1000px; display: flex; flex-direction: column;">
        <div class="helpdesk-modal-header">
            <h2 id="guide-modal-title"><?php echo esc_html__( 'Pridať návod', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close">&times;</button>
        </div>

        <form id="general-guide-form" style="display: flex; flex-direction: column; flex: 1; overflow: hidden;">
            <input type="hidden" id="guide-id" name="id" value="">

            <div style="padding: 20px; overflow-y: auto; flex: 1;">
                <div class="helpdesk-form-row">
                    <div class="helpdesk-form-col-full">
                        <label for="guide-nazov" style="display: block; font-weight: 500; margin-bottom: 8px;">
                            <?php echo esc_html__( 'Názov', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                        </label>
                        <input type="text" id="guide-nazov" name="nazov" required class="widefat" style="padding: 8px; box-sizing: border-box;">
                        <span class="error-message" id="error-guide_nazov"></span>
                    </div>
                </div>

                <div class="helpdesk-form-row">
                    <div class="helpdesk-form-col-half">
                        <label for="guide-kategoria" style="display: block; font-weight: 500; margin-bottom: 8px;">
                            <?php echo esc_html__( 'Kategória', HELPDESK_TEXT_DOMAIN ); ?>
                        </label>
                        <input type="text" id="guide-kategoria" name="kategoria" class="widefat" style="padding: 8px; box-sizing: border-box;">
                        <span class="error-message" id="error-guide_kategoria"></span>
                    </div>

                    <div class="helpdesk-form-col-half">
                        <label for="guide-produkt" style="display: block; font-weight: 500; margin-bottom: 8px;">
                            <?php echo esc_html__( 'Produkt', HELPDESK_TEXT_DOMAIN ); ?>
                        </label>
                        <select id="guide-produkt" name="produkt" class="widefat" style="padding: 8px; box-sizing: border-box;">
                            <option value="0">-- <?php echo esc_html__( 'Nezvolené', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                            <?php foreach ( $products as $product ) : ?>
                                <option value="<?php echo absint( $product['id'] ); ?>"><?php echo esc_html( $product['nazov'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="error-message" id="error-guide_produkt"></span>
                    </div>
                </div>

                <div class="helpdesk-form-row">
                    <div class="helpdesk-form-col-full">
                        <label for="guide-popis" style="display: block; font-weight: 500; margin-bottom: 8px;">
                            <?php echo esc_html__( 'Popis', HELPDESK_TEXT_DOMAIN ); ?>
                        </label>
                        <textarea id="guide-popis" name="popis" rows="6" class="widefat" style="padding: 8px; box-sizing: border-box;"></textarea>
                        <span class="error-message" id="error-guide_popis"></span>
                    </div>
                </div>

                <div class="helpdesk-form-row">
                    <div class="helpdesk-form-col-full">
                        <label for="guide-tagy" style="display: block; font-weight: 500; margin-bottom: 8px;">
                            <?php echo esc_html__( 'Tagy', HELPDESK_TEXT_DOMAIN ); ?>
                            <span style="color: #999; font-size: 12px;">(<?php echo esc_html__( 'oddelené čiarkami', HELPDESK_TEXT_DOMAIN ); ?>)</span>
                        </label>
                        <input type="text" id="guide-tagy" name="tagy" class="widefat" style="padding: 8px; box-sizing: border-box;">
                        <span class="error-message" id="error-guide_tagy"></span>
                    </div>
                </div>

                <div class="helpdesk-form-row">
                    <div class="helpdesk-form-col-full">
                        <label for="guide-aktivny" style="display: block; margin-bottom: 8px;">
                            <input type="checkbox" id="guide-aktivny" name="aktivny" value="1" checked>
                            <?php echo esc_html__( 'Aktívny', HELPDESK_TEXT_DOMAIN ); ?>
                        </label>
                    </div>
                </div>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <h4><?php echo esc_html__( 'Linky', HELPDESK_TEXT_DOMAIN ); ?></h4>
                    <div id="guide-links-container" style="margin-top: 10px; margin-bottom: 15px;">
                        <!-- Links will be added here -->
                    </div>
                    <button type="button" class="button" id="btn-add-guide-link">
                        <?php echo esc_html__( '+ Pridať link', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </div>

            <div style="padding: 0 20px 20px 20px; border-top: 1px solid #ddd; text-align: right;">
                <button type="button" class="button helpdesk-modal-close-btn">
                    <?php echo esc_html__( 'Zrušiť', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
                <button type="submit" class="button button-primary">
                    <?php echo esc_html__( 'Uložiť návod', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
                <button type="button" class="button button-danger" id="btn-delete-guide" style="display: none; float: left;">
                    <?php echo esc_html__( 'Vymazať', HELPDESK_TEXT_DOMAIN ); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Link Modal -->
<div id="helpdesk-guide-link-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content" style="width: 600px;">
        <div class="helpdesk-modal-header">
            <h2><?php echo esc_html__( 'Pridať/Editovať link', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close">&times;</button>
        </div>

        <form id="guide-link-form">
            <input type="hidden" id="link-id" name="id" value="">
            <input type="hidden" id="link-navod-id" name="navod_id" value="">

            <div style="padding: 20px;">
                <div class="helpdesk-form-row">
                    <div class="helpdesk-form-col-full">
                        <label for="link-nazov" style="display: block; font-weight: 500; margin-bottom: 8px;">
                            <?php echo esc_html__( 'Názov', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                        </label>
                        <input type="text" id="link-nazov" name="nazov" required class="widefat" style="padding: 8px; box-sizing: border-box;">
                        <span class="error-message" id="error-link_nazov"></span>
                    </div>
                </div>

                <div class="helpdesk-form-row">
                    <div class="helpdesk-form-col-full">
                        <label for="link-url" style="display: block; font-weight: 500; margin-bottom: 8px;">
                            <?php echo esc_html__( 'URL', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                        </label>
                        <input type="url" id="link-url" name="url" required class="widefat" style="padding: 8px; box-sizing: border-box;">
                        <span class="error-message" id="error-link_url"></span>
                    </div>
                </div>

                <div class="helpdesk-form-row">
                    <div class="helpdesk-form-col-full">
                        <label for="link-produkt" style="display: block; font-weight: 500; margin-bottom: 8px;">
                            <?php echo esc_html__( 'Produkt', HELPDESK_TEXT_DOMAIN ); ?>
                        </label>
                        <select id="link-produkt" name="produkt" class="widefat" style="padding: 8px; box-sizing: border-box;">
                            <option value="0">-- <?php echo esc_html__( 'Nezvolené', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                            <?php foreach ( $products as $product ) : ?>
                                <option value="<?php echo absint( $product['id'] ); ?>"><?php echo esc_html( $product['nazov'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="error-message" id="error-link_produkt"></span>
                    </div>
                </div>

                <div style="margin-top: 20px; text-align: right; border-top: 1px solid #ddd; padding-top: 15px;">
                    <button type="button" class="button helpdesk-modal-close-btn">
                        <?php echo esc_html__( 'Zrušiť', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <?php echo esc_html__( 'Uložiť link', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                    <button type="button" class="button button-danger" id="btn-delete-guide-link" style="display: none; float: left;">
                        <?php echo esc_html__( 'Vymazať', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.helpdesk-form-row {
    margin-bottom: 15px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.helpdesk-form-col-full {
    grid-column: 1 / -1;
}

.helpdesk-form-col-half {
    grid-column: span 1;
}

.required {
    color: #d32f2f;
    font-weight: bold;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
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

.guide-link-item {
    background: #f9f9f9;
    padding: 12px;
    margin: 8px 0;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid #ddd;
}

.guide-link-item > div:first-child {
    flex: 1;
}

.guide-link-item strong {
    display: block;
    margin-bottom: 4px;
}

.guide-link-item small {
    color: #666;
    word-break: break-all;
}

.guide-link-item .link-actions {
    margin-left: 15px;
    white-space: nowrap;
}

.guide-link-item .link-actions button {
    margin-left: 8px;
}

.error-message {
    display: block;
    color: #d32f2f;
    font-size: 12px;
    margin-top: 3px;
}

.helpdesk-search-input {
    padding: 8px 12px;
    font-size: 14px;
    width: 300px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

.helpdesk-button-group {
    margin-bottom: 15px;
    display: flex;
    gap: 10px;
}

.helpdesk-admin-container {
    background: white;
    padding: 15px;
    border-radius: 4px;
}
</style>
