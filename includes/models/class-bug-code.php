<?php
/**
 * Bug Code Model
 */

namespace HelpDesk\Models;

use HelpDesk\Utils\Database;

class BugCode extends BaseModel {
    /**
     * Table name
     */
    protected $table = 'hd_ciselnik_kody_chyb';

    /**
     * Constructor
     */
    public function __construct( $id = null ) {
        $this->table = Database::get_bug_codes_table();
        parent::__construct( $id );
    }

    /**
     * Get all active bug codes
     */
    public static function get_all( $active_only = true ) {
        global $wpdb;
        $table = Database::get_bug_codes_table();
        
        if ( $active_only ) {
            $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE aktivny = %d ORDER BY id DESC", 1 ), ARRAY_A );
        } else {
            $results = $wpdb->get_results( "SELECT * FROM " . esc_sql( $table ) . " ORDER BY id DESC", ARRAY_A );
        }
        
        return $results ? $results : [];
    }

    /**
     * Get bug code by kod
     */
    public static function get_by_kod( $kod ) {
        global $wpdb;
        $table = Database::get_bug_codes_table();
        
        $result = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . esc_sql( $table ) . " WHERE kod = %s", $kod ),
            ARRAY_A
        );
        
        return $result ? new self( $result['id'] ) : null;
    }

    /**
     * Create new bug code
     */
    public static function create( $data ) {
        global $wpdb;
        $table = Database::get_bug_codes_table();
        
        $insert_data = [
            'kod' => $data['kod'] ?? '',
            'popis' => $data['popis'] ?? '',
            'uplny_popis' => $data['uplny_popis'] ?? '',
            'operacny_system' => $data['operacny_system'] ?? '',
            'produkt' => isset( $data['produkt'] ) ? ( $data['produkt'] > 0 ? $data['produkt'] : null ) : null,
            'aktivny' => isset( $data['aktivny'] ) ? (int) $data['aktivny'] : 1,
        ];
        $insert_formats = [ '%s', '%s', '%s', '%s', '%d', '%d' ];
        
        $result = $wpdb->insert( $table, $insert_data, $insert_formats );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update bug code
     */
    public static function update_code( $id, $data ) {
        global $wpdb;
        $table = Database::get_bug_codes_table();
        
        $update_data = [
            'kod' => $data['kod'] ?? '',
            'popis' => $data['popis'] ?? '',
            'uplny_popis' => $data['uplny_popis'] ?? '',
            'operacny_system' => $data['operacny_system'] ?? '',
            'produkt' => isset( $data['produkt'] ) ? ( $data['produkt'] > 0 ? $data['produkt'] : null ) : null,
            'aktivny' => isset( $data['aktivny'] ) ? (int) $data['aktivny'] : 1,
        ];
        $update_formats = [ '%s', '%s', '%s', '%s', '%d', '%d' ];
        
        $result = $wpdb->update( $table, $update_data, [ 'id' => $id ], $update_formats, [ '%d' ] );
        
        return $result !== false;
    }

    /**
     * Delete bug code
     */
    public static function delete_code( $id ) {
        global $wpdb;
        $table = Database::get_bug_codes_table();
        
        return $wpdb->delete( $table, [ 'id' => $id ] );
    }
}
