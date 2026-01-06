<?php
/**
 * Contacts Admin View
 */

use HelpDesk\Models\Contact;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$contacts = Contact::get_all( false ); // Get all including inactive
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Kontakty', HELPDESK_TEXT_DOMAIN ); ?></h1>

    <div class="helpdesk-admin-container">
        <div class="helpdesk-button-group">
            <button class="button button-primary helpdesk-btn-new-contact">
                <?php echo esc_html__( '+ Pridať kontakt', HELPDESK_TEXT_DOMAIN ); ?>
            </button>
        </div>

        <table class="wp-list-table widefat fixed striped" id="helpdesk-contacts-table">
            <thead>
                <tr>
                    <th scope="col" class="column-nazov"><?php echo esc_html__( 'Názov spoločnosti', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-kontaktna-osoba"><?php echo esc_html__( 'Kontaktná osoba', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-klapka"><?php echo esc_html__( 'Klapka', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-telefon"><?php echo esc_html__( 'Telefón', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-email"><?php echo esc_html__( 'Email', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-status"><?php echo esc_html__( 'Stav', HELPDESK_TEXT_DOMAIN ); ?></th>
                    <th scope="col" class="column-actions"><?php echo esc_html__( 'Akcie', HELPDESK_TEXT_DOMAIN ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $contacts ) ) : ?>
                    <?php foreach ( $contacts as $contact ) : ?>
                        <tr data-contact-id="<?php echo absint( $contact['id'] ); ?>">
                            <td class="column-nazov"><?php echo esc_html( $contact['nazov'] ?? '--' ); ?></td>
                            <td class="column-kontaktna-osoba"><?php echo esc_html( $contact['kontaktna_osoba'] ?? '--' ); ?></td>
                            <td class="column-klapka"><?php echo esc_html( $contact['klapka'] ?? '--' ); ?></td>
                            <td class="column-telefon"><?php echo esc_html( $contact['telefon'] ?? '--' ); ?></td>
                            <td class="column-email"><?php echo esc_html( $contact['email'] ?? '--' ); ?></td>
                            <td class="column-status">
                                <span class="status-badge <?php echo $contact['aktivny'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $contact['aktivny'] ? esc_html__( 'Aktívny', HELPDESK_TEXT_DOMAIN ) : esc_html__( 'Neaktívny', HELPDESK_TEXT_DOMAIN ); ?>
                                </span>
                            </td>
                            <td class="column-actions" style="text-align: center; font-size: 18px;">
                                <button class="button button-small helpdesk-btn-edit-contact" data-id="<?php echo absint( $contact['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Upraviť', HELPDESK_TEXT_DOMAIN ); ?>">
                                    ✏️
                                </button>
                                <button class="button button-small button-link-delete helpdesk-btn-delete-contact" data-id="<?php echo absint( $contact['id'] ); ?>" style="border: none; background: none; padding: 5px; cursor: pointer; color: #333; filter: grayscale(100%);" title="<?php echo esc_attr__( 'Zmazať', HELPDESK_TEXT_DOMAIN ); ?>">
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7"><?php echo esc_html__( 'Žiadne kontakty', HELPDESK_TEXT_DOMAIN ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for add/edit contact -->
<div id="helpdesk-contact-modal" class="helpdesk-modal" style="display:none;">
    <div class="helpdesk-modal-content">
        <div class="helpdesk-modal-header">
            <h2 id="contact-modal-title"><?php echo esc_html__( 'Pridať kontakt', HELPDESK_TEXT_DOMAIN ); ?></h2>
            <button type="button" class="helpdesk-modal-close">&times;</button>
        </div>
        <div class="helpdesk-modal-body">
            <form id="helpdesk-contact-form" style="display: contents;">
                <input type="hidden" id="helpdesk-contact-id" value="">
                
                <div class="form-group">
                    <label for="helpdesk-contact-nazov"><?php echo esc_html__( 'Názov spoločnosti', HELPDESK_TEXT_DOMAIN ); ?></label>
                    <input type="text" id="helpdesk-contact-nazov" name="nazov" placeholder="napr. ABC s.r.o.">
                </div>
                
                <div class="form-group">
                    <label for="helpdesk-contact-kontaktna-osoba"><?php echo esc_html__( 'Kontaktná osoba', HELPDESK_TEXT_DOMAIN ); ?></label>
                    <input type="text" id="helpdesk-contact-kontaktna-osoba" name="kontaktna_osoba" placeholder="napr. Ján Novák">
                </div>
                
                <div class="form-group">
                    <label for="helpdesk-contact-klapka"><?php echo esc_html__( 'Klapka', HELPDESK_TEXT_DOMAIN ); ?></label>
                    <input type="text" id="helpdesk-contact-klapka" name="klapka" maxlength="4" placeholder="napr. 1234">
                </div>
                
                <div class="form-group">
                    <label for="helpdesk-contact-telefon"><?php echo esc_html__( 'Telefón', HELPDESK_TEXT_DOMAIN ); ?></label>
                    <input type="text" id="helpdesk-contact-telefon" name="telefon" placeholder="napr. +421 2 123 456 789">
                </div>
                
                <div class="form-group">
                    <label for="helpdesk-contact-email"><?php echo esc_html__( 'Email', HELPDESK_TEXT_DOMAIN ); ?></label>
                    <input type="email" id="helpdesk-contact-email" name="email">
                </div>
                
                <div class="form-group">
                    <label for="helpdesk-contact-poznamka"><?php echo esc_html__( 'Poznámka', HELPDESK_TEXT_DOMAIN ); ?></label>
                    <textarea id="helpdesk-contact-poznamka" name="poznamka" rows="4"></textarea>
                </div>
                
                <div class="form-group form-group-checkbox">
                    <label for="helpdesk-contact-aktivny">
                        <input type="checkbox" id="helpdesk-contact-aktivny" name="aktivny" value="1">
                        <span><?php echo esc_html__( 'Aktívny', HELPDESK_TEXT_DOMAIN ); ?></span>
                    </label>
                </div>
                
                <div class="helpdesk-modal-actions">
                    <button type="submit" class="button button-primary">
                        <?php echo esc_html__( 'Uložiť', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                    <button type="button" class="button helpdesk-modal-close-btn">
                        <?php echo esc_html__( 'Zrušiť', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </form>
            <div class="error-message" style="color: red; margin-top: 5px; font-size: 12px;"></div>
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

.form-group-checkbox {
    display: flex;
    align-items: center;
    margin-bottom: 0;
}

.form-group-checkbox label {
    display: flex;
    align-items: center;
    margin-bottom: 0;
    font-weight: normal;
    cursor: pointer;
}

.form-group-checkbox input[type="checkbox"] {
    width: auto;
    height: 16px;
    width: 16px;
    margin-right: 6px;
    cursor: pointer;
    padding: 0;
}

.form-group-checkbox span {
    font-size: 12px;
}

.helpdesk-modal-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 12px;
    grid-column: 1 / -1;
}

.helpdesk-modal-actions button {
    padding: 6px 12px;
    font-size: 12px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
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
?>
