<?php
/**
 * Direct database migration - standalone script
 * Access: yoursite.com/wp-content/plugins/helpdesk/migrate_now.php
 */

// Load WordPress
require_once( '../../../../wp-load.php' );

// Security check
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Unauthorized', 'Access Denied', array( 'response' => 403 ) );
}

global $wpdb;

$table = $wpdb->prefix . 'hd_riesenia';

?>
<!DOCTYPE html>
<html>
<head>
    <title>HelpDesk Database Migration</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f1f1f1; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .step { margin: 20px 0; padding: 15px; background: #f5f5f5; border-left: 4px solid #0073aa; }
        .step.success { background: #d4edda; border-left-color: #28a745; }
        .step.error { background: #f8d7da; border-left-color: #dc3545; }
        .step strong { color: #0073aa; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; font-size: 14px; }
        .button:hover { background: #005a87; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f5f5f5; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 HelpDesk Database Migration</h1>
    
    <?php
    
    // Get current columns
    $columns_result = $wpdb->get_results( "DESC $table" );
    $columns = array();
    foreach ( $columns_result as $col ) {
        $columns[] = $col->Field;
    }
    
    echo '<div class="step">';
    echo '<strong>Tabuľka:</strong> <code>' . esc_html( $table ) . '</code><br>';
    echo '<strong>Počet stĺpcov:</strong> ' . count( $columns );
    echo '</div>';
    
    echo '<h2>Aktuálne stĺpce</h2>';
    echo '<table>';
    echo '<tr><th>Poradové číslo</th><th>Názov stĺpca</th></tr>';
    foreach ( $columns as $i => $col ) {
        echo '<tr><td>' . ($i + 1) . '</td><td><code>' . esc_html( $col ) . '</code></td></tr>';
    }
    echo '</table>';
    
    echo '<h2>Vykonávanie zmien</h2>';
    
    // 1. Rename popis to popis_problem
    echo '<div class="step">';
    if ( in_array( 'popis', $columns ) ) {
        $sql = "ALTER TABLE `$table` CHANGE COLUMN `popis` `popis_problem` LONGTEXT DEFAULT NULL";
        $result = $wpdb->query( $sql );
        if ( $result !== false ) {
            echo '<strong style="color: green;">✓</strong> Premenovaný stĺpec <code>popis</code> → <code>popis_problem</code>';
            $columns[] = 'popis_problem';
            $columns = array_diff( $columns, array( 'popis' ) );
        } else {
            echo '<strong style="color: red;">✗</strong> Chyba pri premenovaní <code>popis</code>: ' . esc_html( $wpdb->last_error );
        }
    } else {
        echo '<strong style="color: orange;">⊘</strong> Stĺpec <code>popis</code> neexistuje';
    }
    echo '</div>';
    
    // Refresh columns
    $columns_result = $wpdb->get_results( "DESC $table" );
    $columns = array();
    foreach ( $columns_result as $col ) {
        $columns[] = $col->Field;
    }
    
    // 2. Rename popis_riesenia to popis_riesenie
    echo '<div class="step">';
    if ( in_array( 'popis_riesenia', $columns ) ) {
        $sql = "ALTER TABLE `$table` CHANGE COLUMN `popis_riesenia` `popis_riesenie` LONGTEXT DEFAULT NULL";
        $result = $wpdb->query( $sql );
        if ( $result !== false ) {
            echo '<strong style="color: green;">✓</strong> Premenovaný stĺpec <code>popis_riesenia</code> → <code>popis_riesenie</code>';
        } else {
            echo '<strong style="color: red;">✗</strong> Chyba pri premenovaní <code>popis_riesenia</code>: ' . esc_html( $wpdb->last_error );
        }
    } else {
        echo '<strong style="color: orange;">⊘</strong> Stĺpec <code>popis_riesenia</code> neexistuje';
    }
    echo '</div>';
    
    // Refresh columns
    $columns_result = $wpdb->get_results( "DESC $table" );
    $columns = array();
    foreach ( $columns_result as $col ) {
        $columns[] = $col->Field;
    }
    
    // 3. Add email_1 if missing
    echo '<div class="step">';
    if ( ! in_array( 'email_1', $columns ) ) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `email_1` LONGTEXT DEFAULT NULL AFTER `kod_chyby`";
        $result = $wpdb->query( $sql );
        if ( $result !== false ) {
            echo '<strong style="color: green;">✓</strong> Pridaný stĺpec <code>email_1</code>';
        } else {
            echo '<strong style="color: red;">✗</strong> Chyba pri pridaní <code>email_1</code>: ' . esc_html( $wpdb->last_error );
        }
    } else {
        echo '<strong style="color: blue;">ℹ</strong> Stĺpec <code>email_1</code> už existuje';
    }
    echo '</div>';
    
    // 4. Add email_2 if missing
    echo '<div class="step">';
    if ( ! in_array( 'email_2', $columns ) ) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `email_2` LONGTEXT DEFAULT NULL AFTER `email_1`";
        $result = $wpdb->query( $sql );
        if ( $result !== false ) {
            echo '<strong style="color: green;">✓</strong> Pridaný stĺpec <code>email_2</code>';
        } else {
            echo '<strong style="color: red;">✗</strong> Chyba pri pridaní <code>email_2</code>: ' . esc_html( $wpdb->last_error );
        }
    } else {
        echo '<strong style="color: blue;">ℹ</strong> Stĺpec <code>email_2</code> už existuje';
    }
    echo '</div>';
    
    // Update version
    update_option( 'helpdesk_db_version', '1.0.6' );
    
    // Final status
    $columns_result = $wpdb->get_results( "DESC $table" );
    $new_columns = array();
    foreach ( $columns_result as $col ) {
        $new_columns[] = $col->Field;
    }
    
    echo '<div class="step success">';
    echo '<h3 style="margin-top: 0; color: #155724;">✓ Migrácia Dokončená</h3>';
    echo '<p><strong>Databázová verzia:</strong> 1.0.6</p>';
    echo '<p><strong>Nové stĺpce:</strong> ' . count( $new_columns ) . '</p>';
    echo '</div>';
    
    echo '<h2>Nové stĺpce</h2>';
    echo '<table>';
    echo '<tr><th>Poradové číslo</th><th>Názov stĺpca</th></tr>';
    foreach ( $new_columns as $i => $col ) {
        echo '<tr><td>' . ($i + 1) . '</td><td><code>' . esc_html( $col ) . '</code></td></tr>';
    }
    echo '</table>';
    
    echo '<p><a href="' . admin_url() . '" class="button">← Vrátiť sa do admin panelu</a></p>';
    
    ?>
</div>
</body>
</html>
