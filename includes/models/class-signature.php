<?php
/**
 * Signature Model Class
 */

namespace HelpDesk\Models;

use HelpDesk\Utils\Database;

class Signature extends BaseModel {
    protected $table;

    public function __construct( $id = null ) {
        $this->table = Database::get_signatures_table();
        parent::__construct( $id );
    }

    /**
     * Create new signature
     */
    public function create( $data ) {
        global $wpdb;

        // Add timestamps
        $data['created_at'] = current_time( 'mysql' );
        $data['updated_at'] = current_time( 'mysql' );

        // Build format array based on actual data keys
        $formats = array();
        foreach ( $data as $key => $value ) {
            if ( in_array( $key, array( 'id', 'produkt_id', 'pracovnik_id' ) ) ) {
                $formats[] = '%d';
            } else {
                $formats[] = '%s';
            }
        }

        $result = $wpdb->insert(
            $this->table,
            $data,
            $formats
        );

        if ( $result ) {
            $this->data = array_merge( array( 'id' => $wpdb->insert_id ), $data );
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Update signature
     */
    public function update( $data ) {
        if ( ! $this->exists() ) {
            return false;
        }

        global $wpdb;
        
        // Add updated_at timestamp
        $data['updated_at'] = current_time( 'mysql' );
        
        // Build format array based on actual data keys
        $formats = array();
        foreach ( $data as $key => $value ) {
            if ( in_array( $key, array( 'produkt_id', 'pracovnik_id', 'id' ) ) ) {
                $formats[] = '%d';
            } else {
                $formats[] = '%s';
            }
        }
        
        $result = $wpdb->update(
            $this->table,
            $data,
            array( 'id' => $this->get( 'id' ) ),
            $formats,
            array( '%d' )
        );

        if ( $result !== false ) {
            $this->data = array_merge( $this->data, $data );
            return true;
        }

        return false;
    }

    /**
     * Get all signatures with product and employee names
     */
    public static function get_all( $args = array() ) {
        global $wpdb;
        $instance = new self();
        $table = Database::get_signatures_table();
        $products_table = Database::get_products_table();
        $employees_table = Database::get_employees_table();

        $results = $wpdb->get_results(
            "SELECT 
                s.id, 
                s.podpis, 
                s.produkt_id, 
                p.nazov as produkt_nazov,
                s.pracovnik_id, 
                e.meno_priezvisko,
                s.created_at,
                s.updated_at
            FROM {$table} s
            LEFT JOIN {$products_table} p ON s.produkt_id = p.id
            LEFT JOIN {$employees_table} e ON s.pracovnik_id = e.id
            ORDER BY s.podpis ASC",
            ARRAY_A
        );

        return $results ? $results : array();
    }

    /**
     * Get signature by employee and product
     */
    public static function get_by_employee_and_product( $pracovnik_id, $produkt_id ) {
        global $wpdb;
        $instance = new self();
        $table = Database::get_signatures_table();
        $products_table = Database::get_products_table();
        $employees_table = Database::get_employees_table();

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    s.id, 
                    s.podpis, 
                    s.text_podpisu,
                    s.produkt_id, 
                    p.nazov as produkt_nazov,
                    s.pracovnik_id, 
                    e.meno_priezvisko,
                    s.created_at,
                    s.updated_at
                FROM {$table} s
                LEFT JOIN {$products_table} p ON s.produkt_id = p.id
                LEFT JOIN {$employees_table} e ON s.pracovnik_id = e.id
                WHERE s.pracovnik_id = %d AND s.produkt_id = %d
                LIMIT 1",
                $pracovnik_id,
                $produkt_id
            ),
            ARRAY_A
        );

        if ( $result ) {
            $instance->data = $result;
            return $instance;
        }

        return null;
    }

    /**
     * Get signature by ID
     */
    public static function get_by_id( $id ) {
        global $wpdb;
        $instance = new self();
        $table = Database::get_signatures_table();
        $products_table = Database::get_products_table();
        $employees_table = Database::get_employees_table();

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    s.id, 
                    s.podpis, 
                    s.text_podpisu,
                    s.produkt_id, 
                    p.nazov as produkt_nazov,
                    s.pracovnik_id, 
                    e.meno_priezvisko,
                    s.created_at,
                    s.updated_at
                FROM {$table} s
                LEFT JOIN {$products_table} p ON s.produkt_id = p.id
                LEFT JOIN {$employees_table} e ON s.pracovnik_id = e.id
                WHERE s.id = %d",
                $id
            ),
            ARRAY_A
        );

        if ( $result ) {
            $instance->data = $result;
            return $instance;
        }

        return null;
    }

    /**
     * Delete signature
     */
    public function delete() {
        if ( ! $this->exists() ) {
            return false;
        }

        global $wpdb;

        $result = $wpdb->delete(
            $this->table,
            array( 'id' => $this->get( 'id' ) ),
            array( '%d' )
        );

        if ( $result ) {
            $this->data = array();
            return true;
        }

        return false;
    }

    /**
     * Get all data from model
     */
    public function get_data() {
        return $this->data;
    }
}
