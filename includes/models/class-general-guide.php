<?php
/**
 * General Guide Model Class
 */

namespace HelpDesk\Models;

use HelpDesk\Utils\Database;

class GeneralGuide extends BaseModel {
    protected $table;

    public function __construct( $id = null ) {
        global $wpdb;
        $this->table = $wpdb->prefix . 'hd_vseobecne_navody';
        parent::__construct( $id );
    }

    /**
     * Create new guide
     */
    public function create( $data ) {
        global $wpdb;

        // Add default values if not provided
        $insert_data = array(
            'nazov' => $data['nazov'] ?? '',
            'kategoria' => $data['kategoria'] ?? '',
            'produkt' => $data['produkt'] ?? 0,
            'problem_id' => $data['problem_id'] ?? 0,
            'popis' => $data['popis'] ?? '',
            'tagy' => $data['tagy'] ?? '',
            'aktivny' => $data['aktivny'] ?? 1,
        );

        // Proper format array
        $formats = array( '%s', '%s', '%d', '%d', '%s', '%s', '%d' );

        $result = $wpdb->insert(
            $this->table,
            $insert_data,
            $formats
        );

        if ( $result ) {
            $this->data = array_merge( array( 'id' => $wpdb->insert_id ), $insert_data );
            return $wpdb->insert_id;
        }

        error_log( 'GeneralGuide create error: ' . $wpdb->last_error );
        return false;
    }

    /**
     * Update guide
     */
    public function update( $data ) {
        if ( ! $this->exists() ) {
            return false;
        }

        global $wpdb;
        
        // Prepare update data
        $update_data = array(
            'nazov' => $data['nazov'] ?? '',
            'kategoria' => $data['kategoria'] ?? '',
            'produkt' => $data['produkt'] ?? 0,
            'problem_id' => $data['problem_id'] ?? 0,
            'popis' => $data['popis'] ?? '',
            'tagy' => $data['tagy'] ?? '',
            'aktivny' => $data['aktivny'] ?? 1,
        );

        // Proper format array
        $formats = array( '%s', '%s', '%d', '%d', '%s', '%s', '%d' );

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
     * Get guides by category
     */
    public static function get_by_category( $kategoria ) {
        global $wpdb;
        $table = $wpdb->prefix . 'hd_vseobecne_navody';
        
        $results = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE kategoria = %s AND aktivny = 1 ORDER BY nazov ASC", $kategoria ),
            OBJECT
        );

        return $results ?: array();
    }

    /**
     * Get guides by product
     */
    public static function get_by_product( $produkt_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'hd_vseobecne_navody';
        
        $results = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE produkt = %d AND aktivny = 1 ORDER BY nazov ASC", $produkt_id ),
            OBJECT
        );

        return $results ?: array();
    }

    /**
     * Get all active guides
     */
    public static function get_all_active() {
        global $wpdb;
        $table = $wpdb->prefix . 'hd_vseobecne_navody';
        
        $results = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE aktivny = 1 ORDER BY nazov ASC",
            OBJECT
        );

        return $results ?: array();
    }

    /**
     * Search guides
     */
    public static function search( $search_term ) {
        global $wpdb;
        $table = $wpdb->prefix . 'hd_vseobecne_navody';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE (nazov LIKE %s OR popis LIKE %s OR kategoria LIKE %s) AND aktivny = 1 ORDER BY nazov ASC",
                '%' . $wpdb->esc_like( $search_term ) . '%',
                '%' . $wpdb->esc_like( $search_term ) . '%',
                '%' . $wpdb->esc_like( $search_term ) . '%'
            ),
            OBJECT
        );

        return $results ?: array();
    }

    /**
     * Get guides by problem/bug code
     */
    public static function get_by_problem( $problem_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'hd_vseobecne_navody';
        
        $results = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE problem_id = %d AND aktivny = 1 ORDER BY nazov ASC", $problem_id ),
            OBJECT
        );

        return $results ?: array();
    }

    /**
     * Search guides by product and problem
     */
    public static function search_by_filters( $filters ) {
        global $wpdb;
        $table = $wpdb->prefix . 'hd_vseobecne_navody';
        
        $where = array( 'aktivny = 1' );
        $params = array();
        
        if ( ! empty( $filters['search'] ) ) {
            $where[] = "(nazov LIKE %s OR popis LIKE %s OR kategoria LIKE %s)";
            $search_term = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
            $params = array( $search_term, $search_term, $search_term );
        }
        
        if ( ! empty( $filters['produkt'] ) && $filters['produkt'] != 0 ) {
            $where[] = "produkt = %d";
            $params[] = intval( $filters['produkt'] );
        }
        
        if ( ! empty( $filters['problem_id'] ) && $filters['problem_id'] != 0 ) {
            $where[] = "problem_id = %d";
            $params[] = intval( $filters['problem_id'] );
        }
        
        if ( ! empty( $filters['kategoria'] ) ) {
            $where[] = "kategoria = %s";
            $params[] = $filters['kategoria'];
        }
        
        $query = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . " ORDER BY nazov ASC";
        
        if ( ! empty( $params ) ) {
            $results = $wpdb->get_results( $wpdb->prepare( $query, $params ), OBJECT );
        } else {
            $results = $wpdb->get_results( $query, OBJECT );
        }
        
        return $results ?: array();
    }
}
