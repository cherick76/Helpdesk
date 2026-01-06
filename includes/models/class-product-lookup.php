<?php
/**
 * Product Model
 */

namespace HelpDesk\Models;

use HelpDesk\Utils\Database;

class Product extends BaseModel {
    public function __construct( $id = null ) {
        $this->table = Database::get_products_table();
        parent::__construct( $id );
    }

    /**
     * Get all active products
     */
    public static function get_all( $active_only = true ) {
        global $wpdb;
        $table = Database::get_products_table();
        
        if ( $active_only ) {
            $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE aktivny = %d ORDER BY nazov ASC", 1 ), ARRAY_A );
        } else {
            $results = $wpdb->get_results( "SELECT * FROM " . esc_sql( $table ) . " ORDER BY nazov ASC", ARRAY_A );
        }
        
        return $results ? $results : [];
    }

    /**
     * Get product by nazov
     */
    public static function get_by_nazov( $nazov ) {
        global $wpdb;
        $table = Database::get_products_table();
        
        $result = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . esc_sql( $table ) . " WHERE nazov = %s", $nazov ),
            ARRAY_A
        );
        
        return $result ? new self( $result['id'] ) : null;
    }

    /**
     * Create new product
     */
    public static function create( $data ) {
        global $wpdb;
        $table = Database::get_products_table();
        
        $insert_data = [
            'nazov' => $data['nazov'] ?? '',
            'popis' => $data['popis'] ?? '',
            'link' => $data['link'] ?? '',
            'aktivny' => isset( $data['aktivny'] ) ? (int) $data['aktivny'] : 1,
        ];
        $insert_formats = [ '%s', '%s', '%s', '%d' ];
        
        $wpdb->insert( $table, $insert_data, $insert_formats );
        return $wpdb->insert_id;
    }

    /**
     * Update product
     */
    public static function update_product( $id, $data ) {
        global $wpdb;
        $table = Database::get_products_table();
        
        $update_data = [
            'nazov' => $data['nazov'] ?? '',
            'popis' => $data['popis'] ?? '',
            'link' => $data['link'] ?? '',
            'aktivny' => isset( $data['aktivny'] ) ? (int) $data['aktivny'] : 1,
        ];
        $update_formats = [ '%s', '%s', '%s', '%d' ];
        
        return $wpdb->update( $table, $update_data, [ 'id' => $id ], $update_formats, [ '%d' ] );
    }

    /**
     * Delete product
     */
    public static function delete_product( $id ) {
        global $wpdb;
        $table = Database::get_products_table();
        
        return $wpdb->delete( $table, [ 'id' => $id ] );
    }
}
