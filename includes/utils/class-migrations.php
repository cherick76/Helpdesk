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
        
        // Migration: Split zakaznicke_cislo into number and name (2025-01-08)
        if ( version_compare( $db_version, '1.0.26', '<=' ) ) {
            error_log( 'Running migration 1.0.27' );
            self::migrate_split_project_name();
            update_option( 'helpdesk_db_version', '1.0.27' );
            error_log( 'Migration 1.0.27 completed' );
        }

        // Migration: Add zdroj column to standby table (2026-01-09)
        if ( version_compare( $db_version, '1.0.27', '<' ) ) {
            error_log( 'Running migration 1.0.28' );
            self::migrate_add_standby_source_column();
            update_option( 'helpdesk_db_version', '1.0.28' );
            error_log( 'Migration 1.0.28 completed' );
        }

        // Migration: Add composite index for vacations table (2026-01-09)
        if ( version_compare( $db_version, '1.0.28', '<=' ) ) {
            error_log( 'Running migration 1.0.29' );
            self::migrate_add_vacation_indexes();
            update_option( 'helpdesk_db_version', '1.0.29' );
            error_log( 'Migration 1.0.29 completed' );
        }

        // Migration: Add problem_id field to general guides table (2026-01-12)
        if ( version_compare( $db_version, '1.0.29', '<=' ) ) {
            error_log( 'Running migration 1.0.30' );
            self::migrate_add_guide_problem_field();
            update_option( 'helpdesk_db_version', '1.0.30' );
            error_log( 'Migration 1.0.30 completed' );
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
        
        // Add popis_riesenie column if it doesn't exist
        if ( ! in_array( 'popis_riesenie', $columns ) ) {
            error_log( 'Attempting to add popis_riesenie column...' );
            $sql = "ALTER TABLE `$table` ADD COLUMN `popis_riesenie` LONGTEXT DEFAULT NULL AFTER `popis_problem`";
            error_log( 'SQL: ' . $sql );
            $result = $wpdb->query( $sql );
            if ( $result !== false ) {
                error_log( '✓ Successfully added popis_riesenie column' );
            } else {
                error_log( '✗ Error: ' . $wpdb->last_error );
            }
        } else {
            error_log( 'Column popis_riesenie already exists' );
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
    
    /**
     * Migrate: Split zakaznicke_cislo into cislo and nazov
     * Example: "1234-My Project" becomes zakaznicke_cislo="1234", nazov="My Project"
     */
    private static function migrate_split_project_name() {
        global $wpdb;
        
        $table = Database::get_projects_table();
        
        // Add nazov column if it doesn't exist
        $columns = $wpdb->get_col( "SHOW COLUMNS FROM $table" );
        
        if ( ! in_array( 'nazov', $columns ) ) {
            error_log( 'Adding nazov column to ' . $table );
            $wpdb->query( "ALTER TABLE `$table` ADD COLUMN `nazov` VARCHAR(255) NULL AFTER `zakaznicke_cislo`" );
        }
        
        // Get all projects with dash in zakaznicke_cislo
        $projects = $wpdb->get_results( "SELECT id, zakaznicke_cislo FROM $table WHERE zakaznicke_cislo LIKE '%-%' AND nazov IS NULL" );
        
        error_log( 'Found ' . count( $projects ) . ' projects to migrate' );
        
        foreach ( $projects as $project ) {
            $cislo = '';
            $nazov = '';
            
            // Extract number and flags: "1234-nw-My Project" or "1234-My Project"
            if ( preg_match( '/^(\d+(?:-nw|-nwd|--?)*)-(.*?)$/i', $project->zakaznicke_cislo, $matches ) ) {
                // Pattern matches: number[flags]-name
                $cislo = trim( $matches[1] );      // "1234-nw" or "1234"
                $nazov = trim( $matches[2] );      // "My Project"
            } else {
                // Fallback: split by first dash
                $parts = explode( '-', $project->zakaznicke_cislo, 2 );
                $cislo = trim( $parts[0] );
                $nazov = isset( $parts[1] ) ? trim( $parts[1] ) : '';
            }
            
            error_log( 'Migrating project ' . $project->id . ': "' . $project->zakaznicke_cislo . '" -> cislo="' . $cislo . '", nazov="' . $nazov . '"' );
            
            // Update project
            $wpdb->query( $wpdb->prepare(
                "UPDATE `$table` SET zakaznicke_cislo = %s, nazov = %s WHERE id = %d",
                $cislo,
                $nazov,
                $project->id
            ) );
        }
        
        // Resize zakaznicke_cislo to 15 chars (to accommodate "1234-nw" or "1234-nwd")
        $wpdb->query( "ALTER TABLE `$table` MODIFY COLUMN `zakaznicke_cislo` VARCHAR(15)" );
        
        error_log( 'Project name migration completed' );
    }

    /**
     * Migrate: Add zdroj (source) column to standby table
     * IS = imported from file, MP = manually added, AG = auto generated
     */
    private static function migrate_add_standby_source_column() {
        global $wpdb;
        
        $table = Database::get_standby_table();
        
        // Check if zdroj column already exists
        $columns = $wpdb->get_col( "SHOW COLUMNS FROM {$table}" );
        
        if ( ! in_array( 'zdroj', $columns ) ) {
            // Add zdroj column with ENUM type
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN zdroj ENUM('IS', 'MP', 'AG') DEFAULT 'MP' COMMENT 'IS=Importované zo súboru, MP=Manuálne pridané, AG=Automaticky generované'" );
            
            // Add index for zdroj column
            $wpdb->query( "ALTER TABLE {$table} ADD KEY idx_zdroj (zdroj)" );
            
            error_log( 'Added zdroj column to ' . $table );
        } else {
            error_log( 'Column zdroj already exists in ' . $table );
        }
    }

    /**
     * Migrate: Add composite index for vacations duplicate detection
     * Improves performance of duplicate check queries
     */
    private static function migrate_add_vacation_indexes() {
        global $wpdb;
        
        $table = Database::get_vacations_table();
        
        // Get existing indexes
        $result = $wpdb->get_results( "SHOW INDEX FROM {$table}", ARRAY_A );
        $existing_indexes = array();
        foreach ( $result as $row ) {
            $existing_indexes[] = $row['Key_name'];
        }
        
        // Add composite index if it doesn't exist
        if ( ! in_array( 'idx_meno_rozsah', $existing_indexes ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD KEY idx_meno_rozsah (meno_pracovnika, nepritomnost_od, nepritomnost_do)" );
            error_log( 'Added idx_meno_rozsah index to ' . $table );
        } else {
            error_log( 'Index idx_meno_rozsah already exists in ' . $table );
        }
    }

    /**
     * Migrate: Add problem_id field to general guides table
     * Enables linking guides to specific problems/bug codes
     */
    private static function migrate_add_guide_problem_field() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hd_vseobecne_navody';
        
        // Check if problem_id column already exists
        $columns = $wpdb->get_col( "SHOW COLUMNS FROM $table" );
        
        if ( ! in_array( 'problem_id', $columns ) ) {
            // Add problem_id column for linking to problems/bug codes
            $wpdb->query( "ALTER TABLE $table ADD COLUMN problem_id INT(11) DEFAULT 0 AFTER produkt" );
            error_log( 'Added problem_id column to ' . $table );
            
            // Add index for performance
            $wpdb->query( "ALTER TABLE $table ADD KEY idx_problem_id (problem_id)" );
            error_log( 'Added idx_problem_id index to ' . $table );
        } else {
            error_log( 'Column problem_id already exists in ' . $table );
        }
    }
}
