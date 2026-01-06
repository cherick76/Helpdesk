<?php
/**
 * Operating System Model
 */

namespace HelpDesk\Models;

use HelpDesk\Utils\Database;

class OperatingSystem extends BaseModel {
    /**
     * Table name
     */
    protected $table = 'hd_ciselnik_os';

    /**
     * Constructor
     */
    public function __construct( $id = null ) {
        $this->table = Database::get_os_table();
        parent::__construct( $id );
    }

    /**
     * Get all active operating systems
     */
    public static function get_all( $active_only = true ) {
        global $wpdb;
        $table = Database::get_os_table();
        
        if ( $active_only ) {
            $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE aktivny = %d ORDER BY nazov ASC", 1 ), ARRAY_A );
        } else {
            $results = $wpdb->get_results( "SELECT * FROM " . esc_sql( $table ) . " ORDER BY nazov ASC", ARRAY_A );
        }
        
        return $results ? $results : [];
    }

    /**
     * Get OS by ID
     */
    public static function get_by_id( $id ) {
        global $wpdb;
        $table = Database::get_os_table();
        
        $result = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . esc_sql( $table ) . " WHERE id = %d", $id ),
            ARRAY_A
        );
        
        return $result;
    }

    /**
     * Create new OS
     */
    public static function create( $data ) {
        global $wpdb;
        $table = Database::get_os_table();

        $data['created_at'] = current_time( 'mysql' );
        $data['updated_at'] = current_time( 'mysql' );

        $result = $wpdb->insert( $table, $data );

        if ( $result ) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Update OS
     */
    public static function update_os( $id, $data ) {
        global $wpdb;
        $table = Database::get_os_table();

        $data['updated_at'] = current_time( 'mysql' );

        $result = $wpdb->update(
            $table,
            $data,
            array( 'id' => $id ),
            array( '%s', '%s', '%s', '%d', '%s' )
        );

        return $result;
    }

    /**
     * Delete OS
     */
    public static function delete_os( $id ) {
        global $wpdb;
        $table = Database::get_os_table();

        return $wpdb->delete( $table, array( 'id' => $id ) );
    }
}
