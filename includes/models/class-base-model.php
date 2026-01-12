<?php
/**
 * Base Model Class
 */

namespace HelpDesk\Models;

abstract class BaseModel {
    /**
     * Table name
     */
    protected $table = '';

    /**
     * Model data
     */
    protected $data = array();

    /**
     * Constructor
     */
    public function __construct( $id = null ) {
        if ( $id ) {
            $this->load( $id );
        }
    }

    /**
     * Get table name with prefix
     */
    protected function get_table() {
        global $wpdb;
        // Check if table name needs prefix
        if ( strpos( $this->table, $wpdb->prefix ) === false ) {
            return $wpdb->prefix . $this->table;
        }
        return $this->table;
    }

    /**
     * Load model data from database
     */
    public function load( $id ) {
        global $wpdb;
        $table = $this->get_table();
        $result = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . $table . " WHERE id = %d", $id ),
            ARRAY_A
        );

        if ( $result ) {
            $this->data = $result;
            return true;
        }

        return false;
    }

    /**
     * Get all records (with caching support)
     */
    public static function get_all( $args = array() ) {
        global $wpdb;
        $instance = new static();
        $table = $instance->get_table();

        // Skip cache if searching or using pagination
        $use_cache = empty( $args['search'] ) && empty( $args['offset'] ) && empty( $args['limit'] );

        // Generate cache key
        if ( $use_cache ) {
            $class_name = explode( '\\', get_class( $instance ) );
            $class_name = strtolower( end( $class_name ) );
            $cache_key = 'model_' . $class_name . '_all_' . wp_hash( json_encode( $args ) );
            $cached = get_transient( 'helpdesk_cache_' . $cache_key );

            if ( $cached !== false ) {
                return $cached;
            }
        }

        $query = "SELECT * FROM " . $table;

        if ( ! empty( $args['search'] ) ) {
            $search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $query .= $wpdb->prepare( " WHERE meno_priezvisko LIKE %s OR nazov LIKE %s", $search, $search );
        }

        if ( ! empty( $args['orderby'] ) ) {
            $query .= " ORDER BY " . sanitize_sql_orderby( $args['orderby'] . ' ' . ( $args['order'] ?? 'ASC' ) );
        }

        if ( ! empty( $args['limit'] ) ) {
            $query .= $wpdb->prepare( " LIMIT %d", $args['limit'] );
        }

        if ( ! empty( $args['offset'] ) ) {
            $query .= $wpdb->prepare( " OFFSET %d", $args['offset'] );
        }

        $result = $wpdb->get_results( $query, ARRAY_A );

        // Cache result (5 minutes = 300 seconds)
        if ( $use_cache ) {
            set_transient( 'helpdesk_cache_' . $cache_key, $result, 300 );
        }

        return $result;
    }

    /**
     * Get attribute
     */
    public function get( $key ) {
        return $this->data[ $key ] ?? null;
    }

    /**
     * Set attribute
     */
    public function set( $key, $value ) {
        $this->data[ $key ] = $value;
        return $this;
    }

    /**
     * Get all data
     */
    public function get_all_data() {
        return $this->data;
    }

    /**
     * Check if data exists
     */
    public function exists() {
        return ! empty( $this->data );
    }

    /**
     * Delete record
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

        // Invalidate cache
        if ( $result ) {
            $this->invalidate_cache();
        }

        return $result;
    }

    /**
     * Invalidate cache for this model type
     */
    protected function invalidate_cache() {
        global $wpdb;
        $class_name = explode( '\\', get_class( $this ) );
        $model_name = strtolower( end( $class_name ) );
        $prefix = $wpdb->esc_like( 'helpdesk_cache_model_' . $model_name );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '%transient_' . $prefix . '%'
            )
        );
    }
}
