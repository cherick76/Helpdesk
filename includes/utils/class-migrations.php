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
        $db_version = get_option( 'helpdesk_db_version', '0' );
        error_log( 'Current DB version: ' . $db_version );
        
        // Migration: Add new email fields to bugs table (2024-12-18)
        if ( ! $db_version || version_compare( $db_version, '1.0.5', '<' ) ) {
            error_log( 'Running migration 1.0.5' );
            self::migrate_bug_fields_to_email();
            update_option( 'helpdesk_db_version', '1.0.5' );
            error_log( 'Migration 1.0.5 completed' );
        }
        
        // Migration: Rename popis/popis_riesenia columns (2025-01-06)
        if ( ! $db_version || version_compare( $db_version, '1.0.6', '<' ) ) {
            error_log( 'Running migration 1.0.6' );
            self::migrate_rename_popis_columns();
            update_option( 'helpdesk_db_version', '1.0.6' );
            error_log( 'Migration 1.0.6 completed' );
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
        
        error_log( 'Bug field migration completed' );
    }
    
    /**
     * Migrate: Rename popis columns to popis_problem and popis_riesenie
     */
    private static function migrate_rename_popis_columns() {
        global $wpdb;
        
        $table = Database::get_bugs_table();
        
        // Get all columns
        $result = $wpdb->get_results( "DESC $table" );
        $columns = array();
        foreach ( $result as $col ) {
            $columns[] = $col->Field;
        }
        
        error_log( '=== MIGRATION DEBUG ===' );
        error_log( 'Table: ' . $table );
        error_log( 'Columns: ' . implode( ', ', $columns ) );
        
        // Rename popis to popis_problem
        if ( in_array( 'popis', $columns ) ) {
            error_log( 'Attempting to rename popis to popis_problem...' );
            $sql = "ALTER TABLE `$table` CHANGE COLUMN `popis` `popis_problem` LONGTEXT DEFAULT NULL";
            error_log( 'SQL: ' . $sql );
            $result = $wpdb->query( $sql );
            if ( $result !== false ) {
                error_log( '✓ Successfully renamed popis to popis_problem' );
            } else {
                error_log( '✗ Error: ' . $wpdb->last_error );
            }
        } else {
            error_log( 'Column popis not found' );
        }
        
        // Refresh column list
        $result = $wpdb->get_results( "DESC $table" );
        $columns = array();
        foreach ( $result as $col ) {
            $columns[] = $col->Field;
        }
        
        // Rename popis_riesenia to popis_riesenie
        if ( in_array( 'popis_riesenia', $columns ) ) {
            error_log( 'Attempting to rename popis_riesenia to popis_riesenie...' );
            $sql = "ALTER TABLE `$table` CHANGE COLUMN `popis_riesenia` `popis_riesenie` LONGTEXT DEFAULT NULL";
            error_log( 'SQL: ' . $sql );
            $result = $wpdb->query( $sql );
            if ( $result !== false ) {
                error_log( '✓ Successfully renamed popis_riesenia to popis_riesenie' );
            } else {
                error_log( '✗ Error: ' . $wpdb->last_error );
            }
        } else {
            error_log( 'Column popis_riesenia not found' );
        }
        
        // Ensure email_1 and email_2 exist
        $result = $wpdb->get_results( "DESC $table" );
        $columns = array();
        foreach ( $result as $col ) {
            $columns[] = $col->Field;
        }
        
        if ( ! in_array( 'email_1', $columns ) ) {
            error_log( 'Adding email_1 column...' );
            $wpdb->query( "ALTER TABLE `$table` ADD COLUMN `email_1` LONGTEXT DEFAULT NULL AFTER `kod_chyby`" );
        }
        
        if ( ! in_array( 'email_2', $columns ) ) {
            error_log( 'Adding email_2 column...' );
            $wpdb->query( "ALTER TABLE `$table` ADD COLUMN `email_2` LONGTEXT DEFAULT NULL AFTER `email_1`" );
        }
        
        error_log( '=== MIGRATION COMPLETE ===' );
    }
}
