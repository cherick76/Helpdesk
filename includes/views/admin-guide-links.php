<?php
/**
 * Guide Resources (Links) Admin View
 */

use HelpDesk\Models\GuideResource;
use HelpDesk\Models\GuideCategory;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$resources = GuideResource::get_all();
$categories = GuideCategory::get_all();
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Linky návodov', HELPDESK_TEXT_DOMAIN ); ?></h1>

    <div class="helpdesk-admin-container">
        <div class="helpdesk-button-group">
            <button class="button button-primary helpdesk-btn-new-link">
                <?php echo esc_html__( '+ Pridať linku', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="column-nazov"><?php echo esc_html__( 'Názov', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-url"><?php echo esc_html__( 'URL', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-typ"><?php echo esc_html__( 'Typ', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-status"><?php echo esc_html__( 'Stav', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-actions"><?php echo esc_html__( 'Akcie', HELPDESK_TEXT_DOMAIN ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $resources ) ) : ?>
                    <?php foreach ( $resources as $resource ) : ?>
                        <tr data-link-id="<?php echo absint( $resource['id'] ); ?>">
                            <td class="column-nazov"><strong><?php echo esc_html( $resource['nazov'] ?? '--' ); ?></strong></td>
                            <td class="column-url">
                                <a href="<?php echo esc_url( $resource['url'] ?? '#' ); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo esc_html( substr( $resource['url'] ?? '--', 0, 50 ) ); ?>
                                </a>
                            </td>
                            <td class="column-typ"><?php echo esc_html( $resource['typ'] ?? '--' ); ?></td>
                            <td class="column-status">
                                <span class="status-badge <?php echo $resource['aktivny'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $resource['aktivny'] ? esc_html__( 'Aktívny', HELPDESK_TEXT_DOMAIN ) : esc_html__( 'Neaktívny', HELPDESK_TEXT_DOMAIN ); ?>
                                </span>
                            </td>
                            <td class="column-actions" style="text-align: center; font-size: 18px;">
                                <button class="button button-small helpdesk-btn-edit-link" data-id="<?php echo absint( $resource['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Upraviť', HELPDESK_TEXT_DOMAIN ); ?>">
                                    ✏️
                                </button>
                                <button class="button button-small button-link-delete helpdesk-btn-delete-link" data-id="<?php echo absint( $resource['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Zmazať', HELPDESK_TEXT_DOMAIN ); ?>">
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" class="center"><?php echo esc_html__( 'Žiadne linky nie sú dostupné.', HELPDESK_TEXT_DOMAIN ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Link Modal -->
<div id="helpdesk-link-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content" style="width: 600px;">
        <div class="helpdesk-modal-header">
            <h2 id="link-modal-title"><?php echo esc_html__( 'Pridať linku', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close">&times;</button>
        </div>

        <form id="helpdesk-link-form">
            <input type="hidden" id="link-id" name="id" value="">

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
                    <div class="helpdesk-form-col-half">
                        <label for="link-typ" style="display: block; font-weight: 500; margin-bottom: 8px;">
                            <?php echo esc_html__( 'Typ', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                        </label>
                        <select id="link-typ" name="typ" required class="widefat" style="padding: 8px; box-sizing: border-box;">
                            <option value="">-- <?php echo esc_html__( 'Vyberte typ', HELPDESK_TEXT_DOMAIN ); ?> --</option>
                            <?php if ( ! empty( $categories ) ) : ?>
                                <?php foreach ( $categories as $category ) : ?>
                                    <option value="<?php echo esc_attr( $category['kategoria'] ); ?>">
                                        <?php echo esc_html( $category['kategoria'] ); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <span class="error-message" id="error-link_typ"></span>
                    </div>

                    <div class="helpdesk-form-col-half">
                        <label for="link-aktivny" style="display: block; margin-bottom: 8px;">
                            <input type="checkbox" id="link-aktivny" name="aktivny" value="1" checked>
                            <?php echo esc_html__( 'Aktívny', HELPDESK_TEXT_DOMAIN ); ?>
                        </label>
                    </div>
                </div>

                <div style="margin-top: 20px; text-align: right; border-top: 1px solid #ddd; padding-top: 15px;">
                    <button type="button" class="button helpdesk-modal-close-btn">
                        <?php echo esc_html__( 'Zrušiť', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <?php echo esc_html__( 'Uložiť linku', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                    <button type="button" class="button button-danger" id="btn-delete-link" style="display: none; float: left;">
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

.error-message {
    display: block;
    color: #d32f2f;
    font-size: 12px;
    margin-top: 3px;
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
