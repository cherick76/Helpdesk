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
            // Add new columns
            $wpdb->query( "ALTER TABLE $table ADD COLUMN email_1 LONGTEXT DEFAULT NULL AFTER kod_chyby" );
            error_log( 'Added email_1 column to ' . $table );
        }
        
        if ( ! in_array( 'email_2', $columns ) ) {
            $wpdb->query( "ALTER TABLE $table ADD COLUMN email_2 LONGTEXT DEFAULT NULL AFTER email_1" );
            error_log( 'Added email_2 column to ' . $table );
        }
        
        if ( ! in_array( 'popis_riesenia', $columns ) ) {
            $wpdb->query( "ALTER TABLE $table ADD COLUMN popis_riesenia LONGTEXT DEFAULT NULL AFTER email_2" );
            error_log( 'Added popis_riesenia column to ' . $table );
        }
        
        // Migrate data from old columns to new ones
        $wpdb->query( "UPDATE $table SET email_1 = riesenie WHERE email_1 IS NULL AND riesenie IS NOT NULL" );
        $wpdb->query( "UPDATE $table SET email_2 = riesenie_2 WHERE email_2 IS NULL AND riesenie_2 IS NOT NULL" );
        
        error_log( 'Bug field migration completed' );
    }
}
