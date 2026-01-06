<?php
/**
 * Bug Model Class
 */

namespace HelpDesk\Models;

use HelpDesk\Utils\Database;

class Bug extends BaseModel {
    protected $table;

    public function __construct( $id = null ) {
        $this->table = Database::get_bugs_table();
        parent::__construct( $id );
    }

    /**
     * Create new bug
     */
    public function create( $data ) {
        global $wpdb;

        // Add default values if not provided
        $insert_data = array(
            'nazov' => $data['nazov'] ?? '',
            'popis' => $data['popis'] ?? '',
            'kod_chyby' => $data['kod_chyby'] ?? '',
            'produkt' => $data['produkt'] ?? 0,
            'email_1' => $data['email_1'] ?? $data['email_riesenia'] ?? $data['riesenie'] ?? '',
            'email_2' => $data['email_2'] ?? $data['popis_riesenia'] ?? $data['riesenie_2'] ?? '',
            'popis_riesenia' => $data['popis_riesenia'] ?? '',
            'podpis_id' => $data['podpis_id'] ?? null,
            'stav' => $data['stav'] ?? 'novy',
            'tagy' => $this->prepare_tags( $data['tagy'] ?? '' ),
        );

        // Proper format array - produkt a podpis_id su integery
        $formats = array( '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s' );

        $result = $wpdb->insert(
            $this->table,
            $insert_data,
            $formats
        );

        if ( $result ) {
            $this->data = array_merge( array( 'id' => $wpdb->insert_id ), $insert_data );
            return $wpdb->insert_id;
        }

        // Log error for debugging
        error_log( 'Bug create error: ' . $wpdb->last_error );
        error_log( 'Insert data: ' . print_r( $insert_data, true ) );
        return false;
    }

    /**
     * Update bug
     */
    public function update( $data ) {
        if ( ! $this->exists() ) {
            return false;
        }

        global $wpdb;
        
        // Prepare update data
        $update_data = array(
            'nazov' => $data['nazov'] ?? '',
            'popis' => $data['popis'] ?? '',
            'kod_chyby' => $data['kod_chyby'] ?? '',
            'produkt' => $data['produkt'] ?? 0,
            'email_1' => $data['email_1'] ?? $data['email_riesenia'] ?? $data['riesenie'] ?? '',
            'email_2' => $data['email_2'] ?? $data['popis_riesenia'] ?? $data['riesenie_2'] ?? '',
            'popis_riesenia' => $data['popis_riesenia'] ?? '',
            'podpis_id' => $data['podpis_id'] ?? null,
            'tagy' => $this->prepare_tags( $data['tagy'] ?? '' ),
        );

        // Proper format array - produkt a podpis_id su integery
        $formats = array( '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s' );

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
     * Get bugs by status
     */
    public static function get_by_status( $status ) {
        global $wpdb;
        $instance = new self();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$instance->table} WHERE stav = %s ORDER BY datum_zaznamu DESC",
                $status
            ),
            ARRAY_A
        );    
    }

    /**
     * Prepare tags for storage (convert array/string to JSON)
     */
    private function prepare_tags( $tags ) {
        if ( empty( $tags ) ) {
            return '';
        }
        
        if ( is_array( $tags ) ) {
            // Filter empty values and sanitize
            $tags = array_filter( array_map( 'trim', $tags ) );
            $tags = array_map( 'sanitize_text_field', $tags );
            return wp_json_encode( $tags );
        }
        
        // If string, split by comma and process
        if ( is_string( $tags ) ) {
            $tag_array = array_filter( array_map( 'trim', explode( ',', $tags ) ) );
            $tag_array = array_map( 'sanitize_text_field', $tag_array );
            return wp_json_encode( $tag_array );
        }
        
        return '';
    }
    
    /**
     * Parse tags from storage (convert JSON to array)
     */
    public function get_tags() {
        $tagy = $this->get( 'tagy' );
        if ( empty( $tagy ) ) {
            return array();
        }
        
        $decoded = json_decode( $tagy, true );
        return is_array( $decoded ) ? $decoded : array();
    }
}