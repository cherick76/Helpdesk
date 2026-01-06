<?php
/**
 * Database Migrations Handler
 * Manages incremental schema updates
 */

namespace HelpDesk\Utils;

class Migrations {
    
    /**
     * Run all pending migrations
     */
    public static function run_migrations() {
        $db_version = get_option( 'helpdesk_db_version' );
        
        // Migration: Add new email fields to bugs table (2024-12-18)
        if ( ! $db_version || version_compare( $db_version, '1.0.5', '<' ) ) {
            self::migrate_bug_fields_to_email();
            update_option( 'helpdesk_db_version', '1.0.5' );
        }
    }
    
    /**
     * Migrate bug fields from riesenie/riesenie_2 to email_1/email_2
     * Keep old columns for backward compatibility
     */
    private static function migrate_bug_fields_to_email() {
        global $wpdb;
        
        $table = Database::get_bugs_table();
        
        // Check if email_1 column already exists
        $columns = $wpdb->get_col( "SHOW COLUMNS FROM $table" );
        
        if ( ! in_array( 'email_1', $columns ) ) {
            // Add new columns if they don't exist
            if ( in_array( 'riesenie', $columns ) ) {
                // Rename riesenie to email_1
                $wpdb->query( "ALTER TABLE $table CHANGE COLUMN riesenie email_1 LONGTEXT DEFAULT NULL" );
                error_log( 'Renamed riesenie column to email_1 in ' . $table );
            } else {
                // Just add the column if riesenie doesn't exist
                $wpdb->query( "ALTER TABLE $table ADD COLUMN email_1 LONGTEXT DEFAULT NULL AFTER kod_chyby" );
                error_log( 'Added email_1 column to ' . $table );
            }
        }
        
        if ( ! in_array( 'email_2', $columns ) ) {
            if ( in_array( 'riesenie_2', $columns ) ) {
                // Rename riesenie_2 to email_2
                $wpdb->query( "ALTER TABLE $table CHANGE COLUMN riesenie_2 email_2 LONGTEXT DEFAULT NULL" );
                error_log( 'Renamed riesenie_2 column to email_2 in ' . $table );
            } else {
                // Just add the column if riesenie_2 doesn't exist
                $wpdb->query( "ALTER TABLE $table ADD COLUMN email_2 LONGTEXT DEFAULT NULL AFTER email_1" );
                error_log( 'Added email_2 column to ' . $table );
            }
        }
        
        if ( ! in_array( 'popis_riesenia', $columns ) ) {
            $wpdb->query( "ALTER TABLE $table ADD COLUMN popis_riesenia LONGTEXT DEFAULT NULL AFTER email_2" );
            error_log( 'Added popis_riesenia column to ' . $table );
        }
        
        error_log( 'Bug field migration completed' );
    }
}
