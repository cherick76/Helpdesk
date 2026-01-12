<?php
/**
 * Database Batch Operations Helper
 * Optimizes bulk inserts, updates, and prepared statement usage
 */

namespace HelpDesk\Utils;

class DatabaseBatch {
    /**
     * Batch insert multiple rows (single query)
     *
     * @param string $table Table name
     * @param array $rows Array of row data
     * @param array $columns Column names
     * @return int Number of rows inserted
     */
    public static function insert_batch( $table, $rows, $columns ) {
        if ( empty( $rows ) || empty( $columns ) ) {
            return 0;
        }

        global $wpdb;

        // Build placeholders
        $num_columns = count( $columns );
        $num_rows = count( $rows );
        $placeholders = array_fill( 0, $num_columns, '%s' );
        $row_placeholder = '(' . implode( ',', $placeholders ) . ')';
        $all_placeholders = implode( ',', array_fill( 0, $num_rows, $row_placeholder ) );

        // Build values array
        $values = array();
        foreach ( $rows as $row ) {
            foreach ( $columns as $col ) {
                $values[] = $row[ $col ] ?? null;
            }
        }

        // Build query
        $column_str = implode( ',', $columns );
        $query = "INSERT INTO {$table} ({$column_str}) VALUES {$all_placeholders}";

        // Execute
        $prepared = $wpdb->prepare( $query, $values );
        $result = $wpdb->query( $prepared );

        return $result ? $num_rows : 0;
    }

    /**
     * Batch update rows (transaction)
     *
     * @param string $table Table name
     * @param array $updates Array of [ id => data ]
     * @return int Number of rows updated
     */
    public static function update_batch( $table, $updates ) {
        if ( empty( $updates ) ) {
            return 0;
        }

        global $wpdb;
        $updated = 0;

        // Start transaction
        $wpdb->query( 'START TRANSACTION' );

        foreach ( $updates as $id => $data ) {
            $result = $wpdb->update(
                $table,
                $data,
                array( 'id' => $id ),
                null,
                array( '%d' )
            );

            if ( $result ) {
                $updated++;
            }
        }

        // Commit transaction
        $wpdb->query( 'COMMIT' );

        return $updated;
    }

    /**
     * Batch delete rows
     *
     * @param string $table Table name
     * @param array $ids Array of IDs to delete
     * @return int Number of rows deleted
     */
    public static function delete_batch( $table, $ids ) {
        if ( empty( $ids ) ) {
            return 0;
        }

        global $wpdb;

        // Create IN clause
        $ids_array = array_map( 'intval', $ids );
        $ids_str = implode( ',', $ids_array );

        $query = "DELETE FROM {$table} WHERE id IN ({$ids_str})";
        return $wpdb->query( $query );
    }

    /**
     * Batch select with prepared statement
     *
     * @param string $table Table name
     * @param array $ids Array of IDs
     * @param array $fields Fields to select (empty = all)
     * @return array Results
     */
    public static function get_batch( $table, $ids, $fields = array() ) {
        if ( empty( $ids ) ) {
            return array();
        }

        global $wpdb;

        $field_str = empty( $fields ) ? '*' : implode( ',', $fields );
        $ids_array = array_map( 'intval', $ids );
        $ids_str = implode( ',', $ids_array );

        $query = "SELECT {$field_str} FROM {$table} WHERE id IN ({$ids_str})";
        return $wpdb->get_results( $query, ARRAY_A );
    }

    /**
     * Execute prepared statement with parameter binding
     *
     * @param string $query Query with placeholders
     * @param array $params Parameters
     * @param string $output_type ARRAY_A or OBJECT
     * @return mixed Results
     */
    public static function query_prepared( $query, $params = array(), $output_type = ARRAY_A ) {
        global $wpdb;

        if ( empty( $params ) ) {
            return $wpdb->get_results( $query, $output_type );
        }

        $prepared = $wpdb->prepare( $query, $params );
        return $wpdb->get_results( $prepared, $output_type );
    }

    /**
     * Count rows with prepared statement
     *
     * @param string $table Table name
     * @param array $where Conditions
     * @return int Count
     */
    public static function count( $table, $where = array() ) {
        global $wpdb;

        $query = "SELECT COUNT(*) as cnt FROM {$table}";

        if ( ! empty( $where ) ) {
            $conditions = array();
            foreach ( $where as $column => $value ) {
                $conditions[] = "{$column} = %s";
            }
            $query .= " WHERE " . implode( " AND ", $conditions );

            return $wpdb->get_var(
                $wpdb->prepare( $query, array_values( $where ) )
            );
        }

        return $wpdb->get_var( $query );
    }
}
