<?php
/**
 * Guide Category Model Class
 */

namespace HelpDesk\Models;

use HelpDesk\Utils\Database;

class GuideCategory extends BaseModel {
    protected $table;

    public function __construct( $id = null ) {
        global $wpdb;
        $this->table = $wpdb->prefix . 'hd_kategorie_navody';
        parent::__construct( $id );
    }

    /**
     * Get all categories
     */
    public static function get_all( $args = array() ) {
        global $wpdb;
        $table = $wpdb->prefix . 'hd_kategorie_navody';
        return $wpdb->get_results(
            "SELECT * FROM $table ORDER BY nazov ASC",
            ARRAY_A
        );
    }

    /**
     * Get all active categories
     */
    public static function get_all_active() {
        global $wpdb;
        $table = $wpdb->prefix . 'hd_kategorie_navody';
        return $wpdb->get_results(
            "SELECT * FROM $table WHERE aktivny = 1 ORDER BY nazov ASC",
            OBJECT
        );
    }

    /**
     * Get category by name
     */
    public static function get_by_name( $nazov ) {
        global $wpdb;
        $table = $wpdb->prefix . 'hd_kategorie_navody';
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE nazov = %s LIMIT 1",
                $nazov
            ),
            OBJECT
        );
    }

    /**
     * Create table if not exists
     */
    public static function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'hd_kategorie_navody';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            nazov VARCHAR(255) NOT NULL,
            popis TEXT,
            poradie INT(11) DEFAULT 0,
            aktivny TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_nazov (nazov),
            KEY aktivny (aktivny)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Create new category
     */
    public function create( $data ) {
        global $wpdb;

        $data['created_at'] = current_time( 'mysql' );
        $data['updated_at'] = current_time( 'mysql' );

        $result = $wpdb->insert(
            $this->table,
            $data,
            array( '%s', '%s', '%d', '%d', '%s', '%s' )
        );

        if ( $result ) {
            $this->data = array_merge( array( 'id' => $wpdb->insert_id ), $data );
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Update category
     */
    public function update( $data ) {
        if ( ! $this->exists() ) {
            return false;
        }

        global $wpdb;
        
        $data['updated_at'] = current_time( 'mysql' );
        
        $result = $wpdb->update(
            $this->table,
            $data,
            array( 'id' => $this->get( 'id' ) ),
            array( '%s', '%s', '%d', '%d', '%s' ),
            array( '%d' )
        );

        if ( $result !== false ) {
            $this->load( $this->get( 'id' ) );
            return true;
        }

        return false;
    }
}
