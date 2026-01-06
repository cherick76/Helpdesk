<?php
/**
 * HelpDesk Query Cache Utility
 * 
 * Implementuje caching pre časté SQL dotazy s configurable TTL
 */

namespace HelpDesk\Utils;

class QueryCache {
    /**
     * Default cache TTL (5 minút)
     */
    const DEFAULT_TTL = 300;

    /**
     * Cache prefix
     */
    const CACHE_PREFIX = 'helpdesk_query_';

    /**
     * Get cached query result or execute query if not cached
     * 
     * @param string $query SQL query
     * @param string $cache_key Cache key identifier
     * @param int $ttl Time to live in seconds
     * @return mixed Query result
     */
    public static function get( $query, $cache_key, $ttl = self::DEFAULT_TTL ) {
        global $wpdb;

        // Generate cache key
        $transient_key = self::CACHE_PREFIX . $cache_key;

        // Try to get from cache
        $cached = get_transient( $transient_key );
        if ( $cached !== false ) {
            // Return cached result
            return $cached;
        }

        // Execute query
        $result = $wpdb->get_results( $query, ARRAY_A );

        // Cache the result
        if ( $result !== null ) {
            set_transient( $transient_key, $result, $ttl );
        }

        return $result;
    }

    /**
     * Clear cache for specific key
     * 
     * @param string $cache_key Cache key identifier
     */
    public static function clear( $cache_key ) {
        $transient_key = self::CACHE_PREFIX . $cache_key;
        delete_transient( $transient_key );
    }

    /**
     * Clear all HelpDesk caches
     */
    public static function clear_all() {
        global $wpdb;

        // Get all helpdesk transients
        $results = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE '%transient%" . self::CACHE_PREFIX . "%'",
            ARRAY_A
        );

        foreach ( $results as $row ) {
            $transient_key = str_replace( '_transient_', '', $row['option_name'] );
            delete_transient( $transient_key );
        }
    }

    /**
     * Remember pattern - cache result s auto-expiration
     * 
     * @param string $key Unique cache key
     * @param callable $callback Function that returns the value to cache
     * @param int $ttl Time to live in seconds
     * @return mixed
     */
    public static function remember( $key, $callback, $ttl = self::DEFAULT_TTL ) {
        $transient_key = self::CACHE_PREFIX . $key;
        $value = get_transient( $transient_key );

        if ( $value === false ) {
            $value = call_user_func( $callback );
            if ( $value !== null ) {
                set_transient( $transient_key, $value, $ttl );
            }
        }

        return $value;
    }

    /**
     * Forget pattern - clear cache na demand
     * 
     * @param string $key Cache key
     */
    public static function forget( $key ) {
        $transient_key = self::CACHE_PREFIX . $key;
        delete_transient( $transient_key );
    }

    /**
     * Get cache key for employees
     */
    public static function get_employees_key() {
        return 'employees_all_' . get_option( 'helpdesk_db_version' );
    }

    /**
     * Get cache key pre projects
     */
    public static function get_projects_key() {
        return 'projects_all_' . get_option( 'helpdesk_db_version' );
    }

    /**
     * Get cache key for project employees
     */
    public static function get_project_employees_key( $project_id ) {
        return 'project_' . $project_id . '_employees';
    }

    /**
     * Get cache key for employee positions
     */
    public static function get_positions_key() {
        return 'positions_all_' . get_option( 'helpdesk_db_version' );
    }
}
