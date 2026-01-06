<?php
/**
 * Operating Systems Admin View
 */

use HelpDesk\Models\OperatingSystem;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$os_list = OperatingSystem::get_all( false ); // Get all including inactive
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Operačné Systémy', HELPDESK_TEXT_DOMAIN ); ?></h1>

    <div class="helpdesk-admin-container">
        <div class="helpdesk-button-group">
            <button class="button button-primary helpdesk-btn-new-os">
                <?php echo esc_html__( '+ Pridať OS', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
        </div>

        <table class="wp-list-table widefat fixed striped" id="helpdesk-os-table">
            <thead>
                <tr>
                    <th scope="col" class="column-nazov"><?php echo esc_html__( 'Názov', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-zkratka"><?php echo esc_html__( 'Skratka', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-popis"><?php echo esc_html__( 'Popis', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-status"><?php echo esc_html__( 'Stav', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-actions"><?php echo esc_html__( 'Akcie', HELPDESK_TEXT_DOMAIN ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $os_list ) ) : ?>
                    <?php foreach ( $os_list as $os ) : ?>
                        <tr data-os-id="<?php echo absint( $os['id'] ); ?>">
                            <td class="column-nazov"><?php echo esc_html( $os['nazov'] ?? '--' ); ?></td>
                            <td class="column-zkratka"><?php echo esc_html( $os['zkratka'] ?? '--' ); ?></td>
                            <td class="column-popis"><?php echo esc_html( $os['popis'] ?? '--' ); ?></td>
                            <td class="column-status">
                                <span class="status-badge <?php echo $os['aktivny'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $os['aktivny'] ? esc_html__( 'Aktívny', HELPDESK_TEXT_DOMAIN ) : esc_html__( 'Neaktívny', HELPDESK_TEXT_DOMAIN ); ?>
                                </span>
                            </td>
                            <td class="column-actions" style="text-align: center; font-size: 18px;">
                                <button class="button button-small helpdesk-btn-edit-os" data-id="<?php echo absint( $os['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Upraviť', HELPDESK_TEXT_DOMAIN ); ?>">
                                    ✏️
                                </button>
                                <button class="button button-small button-link-delete helpdesk-btn-delete-os" data-id="<?php echo absint( $os['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Zmazať', HELPDESK_TEXT_DOMAIN ); ?>">
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" class="center"><?php echo esc_html__( 'Žádné OS nebyly nalezeny.', HELPDESK_TEXT_DOMAIN ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- OS Modal -->
<div id="helpdesk-os-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content">
        <div class="helpdesk-modal-header">
            <h2 id="os-modal-title"><?php echo esc_html__( 'Pridať OS', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close">&times;</button>
        </div>
        <form id="helpdesk-os-form" class="helpdesk-form">
            <input type="hidden" id="os-id" name="id" value="">

            <div class="form-group">
                <label for="os-nazov">
                    <?php echo esc_html__( 'Názov', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                </label>
                <input type="text" id="os-nazov" name="nazov" class="widefat" required placeholder="napr. Windows 10">
                <span class="error-message" id="error-nazov"></span>
            </div>

            <div class="form-group">
                <label for="os-zkratka">
                    <?php echo esc_html__( 'Skratka', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <input type="text" id="os-zkratka" name="zkratka" class="widefat" placeholder="napr. W10">
                <span class="error-message" id="error-zkratka"></span>
            </div>

            <div class="form-group">
                <label for="os-popis">
                    <?php echo esc_html__( 'Popis', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <textarea id="os-popis" name="popis" class="widefat" rows="3"></textarea>
                <span class="error-message" id="error-popis"></span>
            </div>

            <div class="form-group">
                <label for="os-aktivny">
                    <input type="checkbox" id="os-aktivny" name="aktivny" value="1" checked>
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
