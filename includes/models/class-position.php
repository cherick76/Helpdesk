<?php
/**
 * Position Model
 */

namespace HelpDesk\Models;

use HelpDesk\Utils\Database;

class Position extends BaseModel {
    /**
     * Table name
     */
    protected $table = 'hd_ciselnik_pozicie';

    /**
     * Constructor
     */
    public function __construct( $id = null ) {
        $this->table = Database::get_positions_table();
        parent::__construct( $id );
    }

    /**
     * Get all positions
     */
    public static function get_all( $args = array() ) {
        global $wpdb;
        $table = Database::get_positions_table();
        
        $results = $wpdb->get_results( "SELECT * FROM " . esc_sql( $table ) . " ORDER BY priorita ASC, profesia ASC", ARRAY_A );
        
        return $results ? $results : [];
    }

    /**
     * Get position by ID
     */
    public static function get_by_id( $id ) {
        global $wpdb;
        $table = Database::get_positions_table();
        
        $result = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . esc_sql( $table ) . " WHERE id = %d", $id ),
            ARRAY_A
        );
        
        return $result ? new self( $result['id'] ) : null;
    }

    /**
     * Create new position
     */
    public static function create( $data ) {
        global $wpdb;
        $table = Database::get_positions_table();
        
        $insert_data = [
            'profesia' => $data['profesia'] ?? '',
            'skratka' => $data['skratka'] ?? '',
            'priorita' => $data['priorita'] ?? '',
        ];
        $insert_formats = [ '%s', '%s', '%s' ];
        
        $result = $wpdb->insert( $table, $insert_data, $insert_formats );
        
        if ( $result === false ) {
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Update position
     */
    public static function update_position( $id, $data ) {
        global $wpdb;
        $table = Database::get_positions_table();
        
        $update_data = [];
        $update_formats = [];
        
        if ( isset( $data['profesia'] ) ) {
            $update_data['profesia'] = $data['profesia'];
            $update_formats[] = '%s';
        }
        
        if ( isset( $data['skratka'] ) ) {
            $update_data['skratka'] = $data['skratka'];
            $update_formats[] = '%s';
        }
        
        if ( isset( $data['priorita'] ) ) {
            $update_data['priorita'] = $data['priorita'];
            $update_formats[] = '%s';
        }
        
        if ( empty( $update_data ) ) {
            return false;
        }
        
        return $wpdb->update( 
            $table, 
            $update_data, 
            [ 'id' => $id ], 
            $update_formats, 
            [ '%d' ] 
        );
    }

    /**
     * Delete position
     */
    public static function delete_position( $id ) {
        global $wpdb;
        $table = Database::get_positions_table();
        
        return $wpdb->delete( 
            $table, 
            [ 'id' => $id ], 
            [ '%d' ] 
        );
    }

    /**
     * Check if position name already exists
     */
    public static function name_exists( $profesia, $exclude_id = null ) {
        global $wpdb;
        $table = Database::get_positions_table();
        
        if ( $exclude_id ) {
            $result = $wpdb->get_var( 
                $wpdb->prepare( 
                    "SELECT COUNT(*) FROM " . esc_sql( $table ) . " WHERE profesia = %s AND id != %d", 
                    $profesia, 
                    $exclude_id 
                ) 
            );
        } else {
            $result = $wpdb->get_var( 
                $wpdb->prepare( 
                    "SELECT COUNT(*) FROM " . esc_sql( $table ) . " WHERE profesia = %s", 
                    $profesia 
                ) 
            );
        }
        
        return $result > 0;
    }

    /**
     * Get position name by ID
     */
    public function get_name() {
        return $this->data['profesia'] ?? '';
    }

    /**
     * Get position active status
     */
    public function is_active() {
        return true; // Všetky pozície sú aktívne
    }
}
