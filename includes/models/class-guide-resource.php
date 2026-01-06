<?php
/**
 * Guide Resource Model Class
 * Represents links/resources for guides (Linky návodov)
 */

namespace HelpDesk\Models;

use HelpDesk\Utils\Database;

class GuideResource extends BaseModel {
    protected $table;

    public function __construct( $id = null ) {
        global $wpdb;
        $this->table = $wpdb->prefix . 'hd_guide_resources';
        parent::__construct( $id );
    }

    /**
     * Create new resource/link
     */
    public function create( $data ) {
        global $wpdb;

        $insert_data = array(
            'nazov' => $data['nazov'] ?? '',
            'url' => $data['url'] ?? '',
            'typ' => $data['typ'] ?? 'externe',
            'aktivny' => $data['aktivny'] ?? 1,
        );

        $formats = array( '%s', '%s', '%s', '%d' );

        $result = $wpdb->insert( $this->table, $insert_data, $formats );

        if ( $result ) {
            $this->data = array_merge( array( 'id' => $wpdb->insert_id ), $insert_data );
            return $wpdb->insert_id;
        }

        error_log( 'GuideResource create error: ' . $wpdb->last_error );
        return false;
    }

    /**
     * Update resource/link
     */
    public function update( $data ) {
        if ( ! $this->exists() ) {
            return false;
        }

        global $wpdb;
        
        $update_data = array(
            'nazov' => $data['nazov'] ?? '',
            'url' => $data['url'] ?? '',
            'typ' => $data['typ'] ?? 'externe',
            'aktivny' => $data['aktivny'] ?? 1,
        );

        $formats = array( '%s', '%s', '%s', '%d' );

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
     * Get all resources
     */
    public static function get_all( $args = array() ) {
        global $wpdb;
        $table = $wpdb->prefix . 'hd_guide_resources';
        
        $results = $wpdb->get_results(
            "SELECT * FROM {$table} ORDER BY nazov ASC",
            ARRAY_A
        );

        return $results ?: array();
    }

    /**
     * Get resources by type
     */
    public static function get_by_type( $typ ) {
        global $wpdb;
        $table = $wpdb->prefix . 'hd_guide_resources';
        
        $results = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE typ = %s AND aktivny = 1 ORDER BY nazov ASC", $typ ),
            ARRAY_A
        );

        return $results ?: array();
    }

    /**
     * Get all data for this resource
     */
    public function get_all_data() {
        return $this->data;
    }
}
?>
