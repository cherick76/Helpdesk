<?php
/**
 * Guide Link Model Class
 */

namespace HelpDesk\Models;

use HelpDesk\Utils\Database;

class GuideLink extends BaseModel {
    protected $table;

    public function __construct( $id = null ) {
        global $wpdb;
        $this->table = $wpdb->prefix . 'hd_navody_linky';
        parent::__construct( $id );
    }

    /**
     * Create new link
     */
    public function create( $data ) {
        global $wpdb;

        // Add default values if not provided
        $insert_data = array(
            'navod_id' => $data['navod_id'] ?? 0,
            'nazov' => $data['nazov'] ?? '',
            'url' => $data['url'] ?? '',
            'produkt' => $data['produkt'] ?? 0,
        );

        // Proper format array
        $formats = array( '%d', '%s', '%s', '%d' );

        $result = $wpdb->insert(
            $this->table,
            $insert_data,
            $formats
        );

        if ( $result ) {
            $this->data = array_merge( array( 'id' => $wpdb->insert_id ), $insert_data );
            return $wpdb->insert_id;
        }

        error_log( 'GuideLink create error: ' . $wpdb->last_error );
        return false;
    }

    /**
     * Update link
     */
    public function update( $data ) {
        if ( ! $this->exists() ) {
            return false;
        }

        global $wpdb;
        
        // Prepare update data
        $update_data = array(
            'nazov' => $data['nazov'] ?? '',
            'url' => $data['url'] ?? '',
            'produkt' => $data['produkt'] ?? 0,
        );

        // Proper format array
        $formats = array( '%s', '%s', '%d' );

        $result = $wpdb->update(
            $this->table,
            $update_data,
            array( 'id' => $this->get( 'id' ) ),
            $formats,
            array( '%d' )
        );

        if ( $result !== false ) {
            $this->data = array_merge( $this->data, $update_data );
            return true;
        }

        return false;
    }

    /**
     * Get links by guide
     */
    public static function get_by_guide( $navod_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'hd_navody_linky';
        
        $results = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE navod_id = %d ORDER BY nazov ASC", $navod_id ),
            OBJECT
        );

        return $results ?: array();
    }

    /**
     * Get links by product
     */
    public static function get_by_product( $produkt_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'hd_navody_linky';
        
        $results = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE produkt = %d ORDER BY nazov ASC", $produkt_id ),
            OBJECT
        );

        return $results ?: array();
    }

    /**
     * Delete all links for a guide
     */
    public static function delete_by_guide( $navod_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'hd_navody_linky';
        
        return $wpdb->delete(
            $table,
            array( 'navod_id' => $navod_id ),
            array( '%d' )
        );
    }
}
