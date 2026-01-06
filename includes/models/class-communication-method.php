<?php
/**
 * Communication Method Model Class
 */

namespace HelpDesk\Models;

use HelpDesk\Utils\Database;

class CommunicationMethod extends BaseModel {
    protected $table;

    public function __construct( $id = null ) {
        $this->table = Database::get_communication_methods_table();
        parent::__construct( $id );
    }

    /**
     * Create new communication method
     */
    public function create( $data ) {
        global $wpdb;

        // Add timestamps
        $data['created_at'] = current_time( 'mysql' );
        $data['updated_at'] = current_time( 'mysql' );

        // Build format array based on actual data keys
        $formats = array();
        foreach ( $data as $key => $value ) {
            if ( in_array( $key, array( 'id', 'priorita', 'aktivny' ) ) ) {
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
     * Update communication method
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
            if ( in_array( $key, array( 'id', 'priorita', 'aktivny' ) ) ) {
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
     * Check if nazov is unique
     */
    public function is_nazov_unique( $nazov, $exclude_id = null ) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT id FROM {$this->table} WHERE nazov = %s",
            $nazov
        );

        if ( $exclude_id ) {
            $query .= $wpdb->prepare( " AND id != %d", $exclude_id );
        }

        return ! $wpdb->get_var( $query );
    }

    /**
     * Get communication method by name
     */
    public static function get_by_name( $nazov ) {
        global $wpdb;
        $instance = new self();

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$instance->table} WHERE nazov = %s",
                $nazov
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
     * Get all active communication methods
     */
    public static function get_active() {
        global $wpdb;
        $instance = new self();

        $results = $wpdb->get_results(
            "SELECT * FROM {$instance->table} WHERE aktivny = 1 ORDER BY priorita ASC, nazov ASC",
            ARRAY_A
        );

        return $results ?: array();
    }
}
