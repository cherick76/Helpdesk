<?php
/**
 * Frontend Dashboard View
 */

use HelpDesk\Models\Project;
use HelpDesk\Models\Bug;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if user is logged in
if ( ! is_user_logged_in() ) {
    wp_die( __( 'Musíte byť prihlásený.', HELPDESK_TEXT_DOMAIN ) );
}

$current_user = wp_get_current_user();
$projects = Project::get_all();
$bugs = Bug::get_all();
?>

<div class="wrap helpdesk-frontend-page">
    <div style="display: flex; align-items: center; margin-bottom: 30px; gap: 20px;">
        <div style="background: linear-gradient(135deg, #0073aa 0%, #005a87 100%); color: white; padding: 25px; border-radius: 8px; flex: 1;">
            <h1 style="margin: 0 0 10px 0; color: white;">📊 HelpDesk Dashboard</h1>
            <p style="margin: 0; opacity: 0.9;">Vitajte, <?php echo esc_html( $current_user->display_name ); ?>!</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <!-- Total Projects -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #0073aa;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <p style="margin: 0; color: #999; font-size: 14px;">Projektov</p>
                    <h3 style="margin: 10px 0 0 0; font-size: 32px; color: #0073aa;"><?php echo count( $projects ); ?></h3>
                </div>
                <div style="font-size: 48px;">📁</div>
            </div>
        </div>

        <!-- Total Solutions -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #4CAF50;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <p style="margin: 0; color: #999; font-size: 14px;">Riešení</p>
                    <h3 style="margin: 10px 0 0 0; font-size: 32px; color: #4CAF50;"><?php echo count( $bugs ); ?></h3>
                </div>
                <div style="font-size: 48px;">✅</div>
            </div>
        </div>

        <!-- Search Feature -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #FF9800;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <p style="margin: 0; color: #999; font-size: 14px;">Vyhľadávanie</p>
                    <p style="margin: 10px 0 0 0; font-size: 14px; color: #666;">Rýchle vyhľadávanie</p>
                </div>
                <div style="font-size: 48px;">🔍</div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div style="margin-bottom: 20px; border-bottom: 1px solid #ddd;">
        <nav style="display: flex; gap: 0; flex-wrap: wrap;">
            <button type="button" class="frontend-tab-button active" data-tab="projects" style="padding: 12px 20px; border: none; background: none; cursor: pointer; font-weight: 500; border-bottom: 3px solid #0073aa; color: #0073aa; margin-bottom: -1px;">
                📁 Projekty Helpdesk
            </button>
            <button type="button" class="frontend-tab-button" data-tab="bugs" style="padding: 12px 20px; border: none; background: none; cursor: pointer; font-weight: 500; border-bottom: 3px solid transparent; color: #666; margin-bottom: -1px;">
                ✅ Aplikačná Podpora
            </button>
        </nav>
    </div>

    <!-- Projects Tab -->
    <div id="projects-tab" class="frontend-tab-content active">
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0;">Projekty Helpdesk</h2>
            
            <div style="margin-bottom: 20px;">
                <input 
                    type="text" 
                    id="frontend-project-search" 
                    class="helpdesk-search-input" 
                    placeholder="🔍 Vyhľadať projekt (názov, popis)..."
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                >
            </div>

            <div id="projects-display" style="display: grid; gap: 15px;">
                <?php if ( ! empty( $projects ) ) : ?>
                    <?php foreach ( $projects as $project ) : ?>
                        <div class="project-card" data-project-id="<?php echo absint( $project['id'] ); ?>" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 20px; border-radius: 8px; border-left: 4px solid #0073aa; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'">
                            <h3 style="margin: 0 0 10px 0; color: #0073aa;"><?php echo esc_html( $project['nazov'] ?? '--' ); ?></h3>
                            <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;"><?php echo esc_html( $project['popis'] ?? '--' ); ?></p>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #999;">
                                <span>📅 <?php echo esc_html( wp_date( 'd.m.Y', strtotime( $project['datum_vytvorenia'] ?? '' ) ) ); ?></span>
                                <button type="button" class="button button-small button-primary" style="cursor: pointer;">Zobraz detaily</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div style="padding: 40px; text-align: center; background: #f9f9f9; border-radius: 8px;">
                        <p style="color: #999; font-size: 16px;">Žiadne projekty</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bugs/Solutions Tab -->
    <div id="bugs-tab" class="frontend-tab-content" style="display: none;">
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0;">Aplikačná Podpora - Riešenia</h2>
            
            <div style="margin-bottom: 20px;">
                <input 
                    type="text" 
                    id="frontend-bug-search" 
                    class="helpdesk-search-input" 
                    placeholder="🔍 Vyhľadať riešenie (názov, produkt, tagy)..."
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                >
            </div>

            <table class="wp-list-table widefat fixed striped" style="background: white;">
                <thead>
                    <tr style="background: #f5f5f5;">
                        <th style="padding: 15px; border-bottom: 2px solid #ddd;">🔧 Názov Riešenia</th>
                        <th style="padding: 15px; border-bottom: 2px solid #ddd;">📦 Produkt</th>
                        <th style="padding: 15px; border-bottom: 2px solid #ddd;">🏷️ Tagy</th>
                        <th style="padding: 15px; border-bottom: 2px solid #ddd; text-align: center;">⚙️ Akcie</th>
                    </tr>
                </thead>
                <tbody id="bugs-display">
                    <?php if ( ! empty( $bugs ) ) : ?>
                        <?php foreach ( $bugs as $bug ) : 
                            $product_name = '--';
                            if ( ! empty( $bug['produkt_id'] ) ) {
                                $product = \HelpDesk\Models\Product::get_by_id( absint( $bug['produkt_id'] ) );
                                if ( $product ) {
                                    $product_name = $product->get( 'nazov' );
                                }
                            }
                            
                            $tagy = array();
                            if ( ! empty( $bug['tagy'] ) ) {
                                $decoded = json_decode( $bug['tagy'], true );
                                $tagy = is_array( $decoded ) ? $decoded : array();
                            }
                        ?>
                            <tr style="border-bottom: 1px solid #ddd; transition: background 0.2s;" onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='white'">
                                <td style="padding: 15px; font-weight: 500;"><?php echo esc_html( $bug['nazov'] ?? '--' ); ?></td>
                                <td style="padding: 15px;"><?php echo esc_html( $product_name ); ?></td>
                                <td style="padding: 15px;">
                                    <?php if ( ! empty( $tagy ) ) : ?>
                                        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                                            <?php foreach ( $tagy as $tag ) : ?>
                                                <span style="background-color: #e7f3ff; color: #0073aa; padding: 3px 8px; border-radius: 3px; font-size: 12px; white-space: nowrap;">
                                                    <?php echo esc_html( $tag ); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else : ?>
                                        <span style="color: #999;">--</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <button type="button" class="button button-small button-primary frontend-view-bug" data-bug-id="<?php echo absint( $bug['id'] ); ?>" style="cursor: pointer;">
                                        👁️ Detaily
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" style="padding: 40px; text-align: center; color: #999;">Žiadne riešenia</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bug Detail Modal -->
<div id="frontend-bug-detail-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; padding: 20px;">
    <div style="background: white; border-radius: 8px; max-width: 700px; max-height: 80vh; overflow-y: auto; margin: auto; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); padding: 30px;">
        <button type="button" style="position: absolute; top: 10px; right: 15px; border: none; background: none; font-size: 28px; cursor: pointer; color: #999;">&times;</button>
        <div id="frontend-bug-detail-content">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<style>
    .helpdesk-frontend-page {
        max-width: 1400px;
        margin: 20px auto;
    }

    .frontend-tab-button {
        transition: all 0.3s ease;
    }

    .frontend-tab-button:hover {
        color: #0073aa;
    }

    .frontend-tab-button.active {
        color: #0073aa;
        border-bottom-color: #0073aa;
    }

    .frontend-tab-content {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .project-card {
        display: block;
    }

    .project-card:hover {
        transform: translateY(-2px);
    }

    .helpdesk-search-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
    }

    .helpdesk-search-input:focus {
        outline: none;
        border-color: #0073aa;
        box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
    }

    .button {
        display: inline-block;
        text-decoration: none;
        padding: 5px 10px;
        border-radius: 3px;
        border: 1px solid #ccc;
        background: #f7f7f7;
        color: #333;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .button:hover {
        background: #e7e7e7;
    }

    .button-primary {
        background: #0073aa;
        color: white;
        border-color: #0073aa;
    }

    .button-primary:hover {
        background: #005a87;
        border-color: #005a87;
    }

    .button-small {
        padding: 4px 8px;
        font-size: 12px;
    }
</style>

<?php
// Make AJAX variables available to JavaScript
?>
<script>
window.helpdesk = {
    ajaxurl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
    nonce: '<?php echo esc_attr( wp_create_nonce( 'helpdesk-nonce' ) ); ?>'
};
</script>
