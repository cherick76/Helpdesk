<?php
/**
 * Positions Admin View
 */

use HelpDesk\Models\Position;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$positions = Position::get_all( false ); // Get all including inactive
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Pozície', HELPDESK_TEXT_DOMAIN ); ?></h1>

    <div class="helpdesk-admin-container">
        <div class="helpdesk-button-group">
            <button class="button button-primary helpdesk-btn-new-position">
                <?php echo esc_html__( '+ Pridať pozíciu', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
            <button class="button helpdesk-btn-export-positions">
                <?php echo esc_html__( '📥 Exportovať', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
            <button class="button helpdesk-btn-import-positions">
                <?php echo esc_html__( '📤 Importovať', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
            <input type="file" id="helpdesk-positions-csv-input" accept=".csv" style="display: none;">
        </div>

        <table class="wp-list-table widefat fixed striped" id="helpdesk-positions-table">
            <thead>
                <tr>
                    <th scope="col" class="column-profesia"><?php echo esc_html__( 'Profesia', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-skratka"><?php echo esc_html__( 'Skratka', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-priorita"><?php echo esc_html__( 'Priorita', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-actions"><?php echo esc_html__( 'Akcie', HELPDESK_TEXT_DOMAIN ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $positions ) ) : ?>
                    <?php foreach ( $positions as $position ) : ?>
                        <tr data-position-id="<?php echo absint( $position['id'] ); ?>">
                            <td class="column-profesia"><?php echo esc_html( $position['profesia'] ?? '--' ); ?></td>
                            <td class="column-skratka"><?php echo esc_html( $position['skratka'] ?? '--' ); ?></td>
                            <td class="column-priorita"><?php echo esc_html( $position['priorita'] ?? '--' ); ?></td>
                            <td class="column-actions" style="text-align: center; font-size: 18px;">
                                <button class="button button-small helpdesk-btn-edit-position" data-id="<?php echo absint( $position['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Upraviť', HELPDESK_TEXT_DOMAIN ); ?>">
                                    ✏️
                                </button>
                                <button class="button button-small button-link-delete helpdesk-btn-delete-position" data-id="<?php echo absint( $position['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Zmazať', HELPDESK_TEXT_DOMAIN ); ?>">
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4" class="center"><?php echo esc_html__( 'Žádné pozície neboli nájdené.', HELPDESK_TEXT_DOMAIN ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Position Modal -->
<div id="helpdesk-position-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content">
        <div class="helpdesk-modal-header">
            <h2 id="position-modal-title"><?php echo esc_html__( 'Pridať pozíciu', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close">&times;</button>
        </div>
        <form id="helpdesk-position-form" class="helpdesk-form">
            <input type="hidden" id="position-id" name="id" value="">

            <div class="form-group">
                <label for="position-profesia">
                    <?php echo esc_html__( 'Profesia', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                </label>
                <input type="text" id="position-profesia" name="profesia" class="widefat" required placeholder="napr. IT Špecialist">
                <span class="error-message" id="error-profesia"></span>
            </div>

            <div class="form-group">
                <label for="position-skratka">
                    <?php echo esc_html__( 'Skratka', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <input type="text" id="position-skratka" name="skratka" class="widefat" placeholder="napr. IT, SD, DBA">
                <span class="error-message" id="error-skratka"></span>
            </div>

            <div class="form-group">
                <label for="position-priorita">
                    <?php echo esc_html__( 'Priorita', HELPDESK_TEXT_DOMAIN ); ?>
                </label>
                <input type="text" id="position-priorita" name="priorita" class="widefat" placeholder="napr. Vysoká, Stredná, Nízka">
                <span class="error-message" id="error-priorita"></span>
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
