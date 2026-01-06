<?php
/**
 * Communication Methods Admin View
 */

use HelpDesk\Models\CommunicationMethod;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$methods = CommunicationMethod::get_all();
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Spôsoby Komunikácie', HELPDESK_TEXT_DOMAIN ); ?></h1>

    <div class="helpdesk-admin-container">
        <div class="helpdesk-button-group">
            <button class="button button-primary helpdesk-btn-new-communication-method">
                <?php echo esc_html__( '+ Pridať spôsob komunikácie', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
        </div>

        <div style="margin-bottom: 20px;">
            <input type="text" id="helpdesk-communication-methods-search" class="helpdesk-search-input" placeholder="<?php echo esc_attr__( 'Vyhľadať spôsob komunikácie...', HELPDESK_TEXT_DOMAIN ); ?>">
        </div>

        <div class="helpdesk-table-wrapper">
        <table class="wp-list-table widefat fixed striped" id="helpdesk-communication-methods-table">
            <thead>
                <tr>
                    <th scope="col" class="column-nazov"><?php echo esc_html__( 'Názov', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-popis"><?php echo esc_html__( 'Popis', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-priorita"><?php echo esc_html__( 'Priorita', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-aktivny"><?php echo esc_html__( 'Aktívny', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-actions"><?php echo esc_html__( 'Akcie', HELPDESK_TEXT_DOMAIN ); ?></th>
                </tr>
            </thead>
            <tbody id="helpdesk-communication-methods-list">
                <?php if ( ! empty( $methods ) ) : ?>
                    <?php foreach ( $methods as $method ) : ?>
                        <tr data-method-id="<?php echo absint( $method['id'] ); ?>">
                            <td class="column-nazov">
                                <strong><?php echo esc_html( $method['nazov'] ); ?></strong>
                            </td>
                            <td class="column-popis">
                                <?php echo esc_html( $method['popis'] ?? '' ); ?>
                            </td>
                            <td class="column-priorita">
                                <?php echo absint( $method['priorita'] ); ?>
                            </td>
                            <td class="column-aktivny">
                                <?php if ( $method['aktivny'] ) : ?>
                                    <span style="color: #2e7d32; font-weight: 600;">✓</span>
                                <?php else : ?>
                                    <span style="color: #d32f2f; font-weight: 600;">✗</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <button class="button button-small helpdesk-btn-edit-communication-method" data-id="<?php echo absint( $method['id'] ); ?>">
                                    <?php echo esc_html__( 'Upraviť', HELPDESK_TEXT_DOMAIN ); ?>
                                </button>
                                <button class="button button-small button-link-delete helpdesk-btn-delete-communication-method" data-id="<?php echo absint( $method['id'] ); ?>">
                                    <?php echo esc_html__( 'Vymazať', HELPDESK_TEXT_DOMAIN ); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" class="center"><?php echo esc_html__( 'Žádné spôsoby komunikácie neboli nájdené.', HELPDESK_TEXT_DOMAIN ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Communication Method Modal -->
<div id="helpdesk-communication-method-modal" class="helpdesk-modal" style="display: none;">
    <div class="helpdesk-modal-content">
        <div class="helpdesk-modal-header">
            <h2 id="communication-method-modal-title"><?php echo esc_html__( 'Pridať spôsob komunikácie', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button class="helpdesk-modal-close">&times;</button>
        </div>
        <div class="helpdesk-modal-body">
            <form id="helpdesk-communication-method-form" class="helpdesk-form">
                <input type="hidden" id="communication-method-id" name="id" value="">

                <!-- Názov -->
                <div class="form-group">
                    <label for="communication-method-nazov">
                        <?php echo esc_html__( 'Názov', HELPDESK_TEXT_DOMAIN ); ?> <span class="required">*</span>
                    </label>
                    <input type="text" id="communication-method-nazov" name="nazov" required maxlength="255">
                    <span class="error-message" id="error-nazov"></span>
                </div>

                <!-- Popis -->
                <div class="form-group">
                    <label for="communication-method-popis">
                        <?php echo esc_html__( 'Popis', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <input type="text" id="communication-method-popis" name="popis" maxlength="255">
                    <span class="error-message" id="error-popis"></span>
                </div>

                <!-- Priorita -->
                <div class="form-group">
                    <label for="communication-method-priorita">
                        <?php echo esc_html__( 'Priorita', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <input type="number" id="communication-method-priorita" name="priorita" value="0" min="0">
                    <span class="error-message" id="error-priorita"></span>
                </div>

                <!-- Aktívny -->
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                        <input type="checkbox" id="communication-method-aktivny" name="aktivny" style="width: auto; margin: 0;" checked>
                        <span><?php echo esc_html__( 'Aktívny', HELPDESK_TEXT_DOMAIN ); ?></span>
                    </label>
                </div>

                <!-- Tlačidlá -->
                <div class="form-actions">
                    <button type="submit" class="button button-primary">
                        <?php echo esc_html__( 'Uložiť', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                    <button type="button" class="button helpdesk-modal-close-btn">
                        <?php echo esc_html__( 'Zrušiť', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                </div>

                <div class="error-message" style="display: none; color: #d32f2f; margin-top: 10px;"></div>
            </form>
        </div>
    </div>
</div>
