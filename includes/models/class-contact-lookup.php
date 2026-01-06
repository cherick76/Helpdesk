<?php
/**
 * Contact Model
 */

namespace HelpDesk\Models;

use HelpDesk\Utils\Database;

class Contact extends BaseModel {
    /**
     * Table name
     */
    protected $table = 'hd_ciselnik_kontakty';

    /**
     * Constructor
     */
    public function __construct( $id = null ) {
        $this->table = Database::get_contacts_table();
        parent::__construct( $id );
    }

    /**
     * Get all active contacts
     */
    public static function get_all( $active_only = true ) {
        global $wpdb;
        $table = Database::get_contacts_table();
        
        if ( $active_only ) {
            $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE aktivny = %d ORDER BY nazov ASC", 1 ), ARRAY_A );
        } else {
            $results = $wpdb->get_results( "SELECT * FROM " . esc_sql( $table ) . " ORDER BY nazov ASC", ARRAY_A );
        }
        
        return $results ? $results : [];
    }

    /**
     * Get contact by nazov
     */
    public static function get_by_nazov( $nazov ) {
        global $wpdb;
        $table = Database::get_contacts_table();
        
        $result = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . esc_sql( $table ) . " WHERE nazov = %s", $nazov ),
            ARRAY_A
        );
        
        return $result ? new self( $result['id'] ) : null;
    }

    /**
     * Create new contact
     */
    public static function create( $data ) {
        global $wpdb;
        $table = Database::get_contacts_table();
        
        // First try with aktivny
        $insert_data = [
            'nazov' => $data['nazov'] ?? '',
            'kontaktna_osoba' => $data['kontaktna_osoba'] ?? '',
            'klapka' => $data['klapka'] ?? '',
            'telefon' => $data['telefon'] ?? '',
            'email' => $data['email'] ?? '',
            'poznamka' => $data['poznamka'] ?? '',
            'aktivny' => isset( $data['aktivny'] ) ? (int) $data['aktivny'] : 1,
        ];
        $insert_formats = [ '%s', '%s', '%s', '%s', '%s', '%s', '%d' ];
        
        try {
            $wpdb->insert( $table, $insert_data, $insert_formats );
            return $wpdb->insert_id;
        } catch ( Exception $e ) {
            // If aktivny column doesn't exist, try without it
            $insert_data = [
                'nazov' => $data['nazov'] ?? '',
                'kontaktna_osoba' => $data['kontaktna_osoba'] ?? '',
                'klapka' => $data['klapka'] ?? '',
                'telefon' => $data['telefon'] ?? '',
                'email' => $data['email'] ?? '',
                'poznamka' => $data['poznamka'] ?? '',
            ];
            $insert_formats = [ '%s', '%s', '%s', '%s', '%s', '%s' ];
            
            $wpdb->insert( $table, $insert_data, $insert_formats );
            return $wpdb->insert_id;
        }
    }

    /**
     * Update contact
     */
    public static function update_contact( $id, $data ) {
        global $wpdb;
        $table = Database::get_contacts_table();
        
        // First try with aktivny
        $update_data = [
            'nazov' => $data['nazov'] ?? '',
            'kontaktna_osoba' => $data['kontaktna_osoba'] ?? '',
            'klapka' => $data['klapka'] ?? '',
            'telefon' => $data['telefon'] ?? '',
            'email' => $data['email'] ?? '',
            'poznamka' => $data['poznamka'] ?? '',
            'aktivny' => isset( $data['aktivny'] ) ? (int) $data['aktivny'] : 1,
        ];
        $update_formats = [ '%s', '%s', '%s', '%s', '%s', '%s', '%d' ];
        
        try {
            return $wpdb->update( $table, $update_data, [ 'id' => $id ], $update_formats, [ '%d' ] );
        } catch ( Exception $e ) {
            // If aktivny column doesn't exist, try without it
            $update_data = [
                'nazov' => $data['nazov'] ?? '',
                'kontaktna_osoba' => $data['kontaktna_osoba'] ?? '',
                'klapka' => $data['klapka'] ?? '',
                'telefon' => $data['telefon'] ?? '',
                'email' => $data['email'] ?? '',
                'poznamka' => $data['poznamka'] ?? '',
            ];
            $update_formats = [ '%s', '%s', '%s', '%s', '%s', '%s' ];
            
            return $wpdb->update( $table, $update_data, [ 'id' => $id ], $update_formats, [ '%d' ] );
        }
    }

    /**
     * Delete contact
     */
    public static function delete_contact( $id ) {
        global $wpdb;
        $table = Database::get_contacts_table();
        
        return $wpdb->delete( $table, [ 'id' => $id ] );
    }
}
?>
