<?php
/**
 * Database utilities and table creation
 */

namespace HelpDesk\Utils;

class Database {
    /**
     * Create plugin tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Table for employees
        $employees_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_pracovnici (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            meno_priezvisko VARCHAR(255) NOT NULL,
            klapka CHAR(4),
            mobil VARCHAR(20),
            poznamka LONGTEXT,
            je_v_pohotovosti TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_klapka (klapka)
        ) $charset_collate;";

        // Table for projects
        $projects_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_projekty (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            zakaznicke_cislo CHAR(4) NOT NULL,
            servisna_sluzba VARCHAR(255),
            nazov VARCHAR(255) NOT NULL,
            podnazov VARCHAR(255),
            projektove_cislo VARCHAR(255) NOT NULL,
            sla VARCHAR(50),
            servisny_kontrakt VARCHAR(255),
            zakaznik VARCHAR(255),
            pm_manazer_id BIGINT UNSIGNED DEFAULT NULL,
            hd_kontakt VARCHAR(255),
            poznamka TEXT,
            nemenit BOOLEAN NOT NULL DEFAULT FALSE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_projektove_cislo (projektove_cislo),
            KEY idx_pm_manazer_id (pm_manazer_id),
            CONSTRAINT fk_pm_manazer_id FOREIGN KEY (pm_manazer_id)
                REFERENCES {$wpdb->prefix}hd_pracovnici(id)
                ON DELETE SET NULL
                ON UPDATE CASCADE
        ) $charset_collate;";

        // M:N relationship table for projects and employees
        $project_employee_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_projekt_pracovnik (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            projekt_id BIGINT UNSIGNED NOT NULL,
            pracovnik_id BIGINT UNSIGNED NOT NULL,
            is_hlavny BOOLEAN NOT NULL DEFAULT FALSE,
            nemenit BOOLEAN NOT NULL DEFAULT FALSE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_projekt_pracovnik (projekt_id, pracovnik_id),
            KEY idx_projekt_id (projekt_id),
            KEY idx_pracovnik_id (pracovnik_id),
            CONSTRAINT fk_pp_projekt FOREIGN KEY (projekt_id)
                REFERENCES {$wpdb->prefix}hd_projekty(id)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            CONSTRAINT fk_pp_pracovnik FOREIGN KEY (pracovnik_id)
                REFERENCES {$wpdb->prefix}hd_pracovnici(id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ) $charset_collate;";

        // Table for bugs
        $bugs_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_riesenia (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nazov VARCHAR(255) DEFAULT NULL,
            popis_problem LONGTEXT DEFAULT NULL,
            kod_chyby VARCHAR(50) DEFAULT NULL,
            email_1 LONGTEXT DEFAULT NULL,
            email_2 LONGTEXT DEFAULT NULL,
            popis_riesenie LONGTEXT DEFAULT NULL,
            produkt BIGINT UNSIGNED DEFAULT NULL,
            podpis_id BIGINT UNSIGNED DEFAULT NULL,
            stav VARCHAR(50) DEFAULT 'novy',
            tagy TEXT DEFAULT NULL,
            datum_zaznamu DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_stav (stav),
            KEY idx_kod_chyby (kod_chyby),
            KEY idx_produkt (produkt),
            KEY idx_podpis_id (podpis_id),
            KEY idx_datum_zaznamu (datum_zaznamu),
            KEY idx_kod_produkt (kod_chyby, produkt),
            FULLTEXT KEY idx_tagy (tagy)
        ) $charset_collate;";

        // Table for employee standby/readiness
        $standby_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_pracovnik_pohotovost (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            pracovnik_id BIGINT UNSIGNED NOT NULL,
            projekt_id BIGINT UNSIGNED NOT NULL,
            pohotovost_od DATE NOT NULL,
            pohotovost_do DATE NOT NULL,
            je_aktivna TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_pracovnik_id (pracovnik_id),
            KEY idx_projekt_id (projekt_id),
            KEY idx_pohotovost_od (pohotovost_od),
            KEY idx_pohotovost_do (pohotovost_do),
            KEY idx_pohotovost_aktualny (pohotovost_od, pohotovost_do, je_aktivna),
            CONSTRAINT fk_standby_pracovnik FOREIGN KEY (pracovnik_id)
                REFERENCES {$wpdb->prefix}hd_pracovnici(id)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            CONSTRAINT fk_standby_projekt FOREIGN KEY (projekt_id)
                REFERENCES {$wpdb->prefix}hd_projekty(id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ) $charset_collate;";

        // Table for bug codes (číselník kódov)
        $bug_codes_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_ciselnik_kody_chyb (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            kod VARCHAR(50) NOT NULL UNIQUE,
            popis VARCHAR(255),
            uplny_popis LONGTEXT,
            operacny_system VARCHAR(255),
            produkt BIGINT UNSIGNED DEFAULT NULL,
            aktivny TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_kod (kod),
            KEY idx_aktivny (aktivny),
            KEY idx_produkt (produkt)
        ) $charset_collate;";

        // Table for products (číselník produktov)
        $products_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_ciselnik_produkty (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nazov VARCHAR(255) NOT NULL UNIQUE,
            popis VARCHAR(255),
            link VARCHAR(500),
            aktivny TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_nazov (nazov),
            KEY idx_aktivny (aktivny)
        ) $charset_collate;";

        // Table for operating systems (číselník operačných systémov)
        $os_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_ciselnik_os (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nazov VARCHAR(255) NOT NULL UNIQUE,
            zkratka VARCHAR(50),
            popis VARCHAR(255),
            aktivny TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_nazov (nazov),
            KEY idx_aktivny (aktivny)
        ) $charset_collate;";

        // Table for contacts (číselník kontaktov)
        $contacts_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_ciselnik_kontakty (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nazov VARCHAR(255),
            kontaktna_osoba VARCHAR(255),
            klapka CHAR(4),
            telefon VARCHAR(20),
            email VARCHAR(255),
            poznamka LONGTEXT,
            aktivny TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_nazov (nazov),
            KEY idx_klapka (klapka),
            KEY idx_aktivny (aktivny)
        ) $charset_collate;";

        // Table for positions (číselník pozícií)
        $positions_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_ciselnik_pozicie (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            profesia VARCHAR(255) NOT NULL UNIQUE,
            skratka VARCHAR(50),
            priorita INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_profesia (profesia),
            KEY idx_priorita (priorita)
        ) $charset_collate;";

        // Table for communication methods (číselník spôsobov komunikácie)
        $communication_methods_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_ciselnik_sposob_komunikacie (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nazov VARCHAR(255) NOT NULL UNIQUE,
            popis VARCHAR(255),
            priorita INT DEFAULT 0,
            aktivny TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_nazov (nazov),
            KEY idx_aktivny (aktivny),
            KEY idx_priorita (priorita)
        ) $charset_collate;";

        // Table for absences (nepritomnosti)
        $vacations_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_nepritomnosti (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            pracovnik_id BIGINT UNSIGNED DEFAULT NULL,
            meno_pracovnika VARCHAR(255) NOT NULL,
            nepritomnost_od DATE NOT NULL,
            nepritomnost_do DATE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_pracovnik_id (pracovnik_id),
            KEY idx_meno_pracovnika (meno_pracovnika),
            KEY idx_nepritomnost_od (nepritomnost_od),
            KEY idx_nepritomnost_do (nepritomnost_do),
            KEY idx_pracovnik_rozsah (pracovnik_id, nepritomnost_od, nepritomnost_do),
            CONSTRAINT fk_absence_pracovnik FOREIGN KEY (pracovnik_id)
                REFERENCES {$wpdb->prefix}hd_pracovnici(id)
                ON DELETE SET NULL
                ON UPDATE CASCADE
        ) $charset_collate;";

        // Table for signatures (Application Support)
        $signatures_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_podpisy (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            podpis VARCHAR(255) NOT NULL,
            text_podpisu LONGTEXT,
            produkt_id BIGINT UNSIGNED NOT NULL,
            pracovnik_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_produkt_id (produkt_id),
            KEY idx_pracovnik_id (pracovnik_id),
            CONSTRAINT fk_signature_produkt FOREIGN KEY (produkt_id)
                REFERENCES {$wpdb->prefix}hd_ciselnik_produkty(id)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            CONSTRAINT fk_signature_pracovnik FOREIGN KEY (pracovnik_id)
                REFERENCES {$wpdb->prefix}hd_pracovnici(id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ) $charset_collate;";

        // Table for guide categories (Kategórie návodov)
        $guide_categories_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_kategorie_navody (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nazov VARCHAR(255) NOT NULL,
            popis LONGTEXT DEFAULT NULL,
            poradie INT(11) DEFAULT 0,
            aktivny TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_nazov (nazov),
            KEY idx_aktivny (aktivny),
            KEY idx_poradie (poradie)
        ) $charset_collate;";

        // Table for general guides (Všeobecné návody)
        $general_guides_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_vseobecne_navody (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nazov VARCHAR(255) NOT NULL,
            kategoria VARCHAR(255) DEFAULT NULL,
            produkt BIGINT UNSIGNED DEFAULT NULL,
            popis LONGTEXT DEFAULT NULL,
            tagy TEXT DEFAULT NULL,
            aktivny TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_nazov (nazov),
            KEY idx_kategoria (kategoria),
            KEY idx_produkt (produkt),
            KEY idx_aktivny (aktivny),
            KEY idx_kategoria_aktiv (kategoria, aktivny),
            FULLTEXT KEY idx_tagy (tagy)
        ) $charset_collate;";

        // Table for guide links (Linky v návodoch)
        $guide_links_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_navody_linky (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            navod_id BIGINT UNSIGNED NOT NULL,
            nazov VARCHAR(255) NOT NULL,
            url VARCHAR(500) NOT NULL,
            produkt BIGINT UNSIGNED DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_navod_id (navod_id),
            KEY idx_nazov (nazov),
            KEY idx_produkt (produkt),
            CONSTRAINT fk_navody_link_navod FOREIGN KEY (navod_id)
                REFERENCES {$wpdb->prefix}hd_vseobecne_navody(id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ) $charset_collate;";

        // Table for guide resources (Linky návodov - standalone)
        $guide_resources_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hd_guide_resources (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nazov VARCHAR(255) NOT NULL,
            url VARCHAR(500) NOT NULL,
            typ VARCHAR(50) NOT NULL DEFAULT 'externe',
            aktivny TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_nazov (nazov),
            KEY idx_typ (typ),
            KEY idx_aktivny (aktivny)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // FIRST: Migration from old hd_chyby table to new hd_riesenia table (run BEFORE dbDelta)
        $old_bugs_table = $wpdb->prefix . 'hd_chyby';
        $new_bugs_table = $wpdb->prefix . 'hd_riesenia';
        
        $old_table_exists = $wpdb->get_results( $wpdb->prepare(
            "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
            DB_NAME,
            $old_bugs_table
        ) );
        
        if ( ! empty( $old_table_exists ) ) {
            // Check if new table exists
            $new_table_exists = $wpdb->get_results( $wpdb->prepare(
                "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
                DB_NAME,
                $new_bugs_table
            ) );
            
            // If new table doesn't exist, rename the old one
            if ( empty( $new_table_exists ) ) {
                @$wpdb->query( "RENAME TABLE {$old_bugs_table} TO {$new_bugs_table}" );
            } else {
                // Both tables exist - copy data from old to new if new is empty
                $old_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$old_bugs_table}" );
                $new_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$new_bugs_table}" );
                
                if ( $old_count > 0 && $new_count == 0 ) {
                    // Copy data from old table to new table
                    @$wpdb->query( "INSERT INTO {$new_bugs_table} (nazov, popis, kod_chyby, riesenie, riesenie_2, produkt, podpis_id, stav, tagy, datum_zaznamu, created_at, updated_at) 
                                   SELECT nazov, popis, kod_chyby, riesenie, COALESCE(riesenie_2, ''), produkt, podpis_id, stav, tagy, datum_zaznamu, created_at, updated_at FROM {$old_bugs_table}" );
                }
            }
        }

        dbDelta( $employees_table );
        dbDelta( $projects_table );
        dbDelta( $project_employee_table );
        dbDelta( $bugs_table );
        dbDelta( $standby_table );
        dbDelta( $bug_codes_table );
        dbDelta( $products_table );
        dbDelta( $os_table );
        dbDelta( $contacts_table );
        dbDelta( $positions_table );
        dbDelta( $communication_methods_table );
        dbDelta( $vacations_table );
        dbDelta( $signatures_table );
        dbDelta( $guide_categories_table );
        dbDelta( $general_guides_table );
        dbDelta( $guide_links_table );
        dbDelta( $guide_resources_table );

        // Add pm_manazer_id column if it doesn't exist
        $projects_table_name = $wpdb->prefix . 'hd_projekty';
        $pm_column_exists = $wpdb->get_results( $wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'pm_manazer_id'",
            DB_NAME,
            $projects_table_name
        ) );

        if ( empty( $pm_column_exists ) ) {
            // Add the pm_manazer_id column
            $wpdb->query( "ALTER TABLE {$projects_table_name} ADD COLUMN pm_manazer_id BIGINT UNSIGNED DEFAULT NULL AFTER zakaznik" );
            // Add the foreign key and index
            $wpdb->query( "ALTER TABLE {$projects_table_name} ADD KEY idx_pm_manazer_id (pm_manazer_id)" );
            $wpdb->query( "ALTER TABLE {$projects_table_name} ADD CONSTRAINT fk_pm_manazer_id FOREIGN KEY (pm_manazer_id) REFERENCES {$wpdb->prefix}hd_pracovnici(id) ON DELETE SET NULL ON UPDATE CASCADE" );
        }

        // Migrate data from old dovolenky table to new nepritomnosti table (copy, not move)
        $old_table = $wpdb->prefix . 'hd_dovolenky';
        $new_table = $wpdb->prefix . 'hd_nepritomnosti';
        
        $table_exists = $wpdb->get_results( $wpdb->prepare(
            "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
            DB_NAME,
            $old_table
        ) );
        
        if ( ! empty( $table_exists ) ) {
            // Check if migration already done (to avoid duplicates)
            $new_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$new_table}" );
            
            if ( $new_count == 0 ) {
                // Copy data from old table to new table
                $wpdb->query( "
                    INSERT INTO {$new_table} (pracovnik_id, meno_pracovnika, nepritomnost_od, nepritomnost_do, created_at, updated_at)
                    SELECT pracovnik_id, meno_pracovnika, dovolenka_od, dovolenka_do, created_at, updated_at
                    FROM {$old_table}
                " );
            }
        }

        // Fix projektove_cislo column size (alter existing table)
        $projects_table_name = $wpdb->prefix . 'hd_projekty';
        $column_exists = $wpdb->get_results( $wpdb->prepare(
            "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'projektove_cislo'",
            DB_NAME,
            $projects_table_name
        ) );

        if ( ! empty( $column_exists ) ) {
            $column_type = $column_exists[0]->COLUMN_TYPE;
            // If column is still VARCHAR(50), update it to VARCHAR(255)
            if ( 'varchar(50)' === strtolower( $column_type ) ) {
                $wpdb->query( $wpdb->prepare( "ALTER TABLE %i MODIFY COLUMN projektove_cislo VARCHAR(255) NOT NULL", $projects_table_name ) );
            }
        }

        // Fix klapka column to be nullable (make it optional)
        $employees_table_name = $wpdb->prefix . 'hd_pracovnici';
        $klapka_column = $wpdb->get_results( $wpdb->prepare(
            "SELECT COLUMN_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'klapka'",
            DB_NAME,
            $employees_table_name
        ) );

        if ( ! empty( $klapka_column ) ) {
            $is_nullable = $klapka_column[0]->IS_NULLABLE;
            // If klapka is NOT NULL, change it to nullable
            if ( 'NO' === $is_nullable ) {
                $wpdb->query( $wpdb->prepare( "ALTER TABLE %i MODIFY COLUMN klapka CHAR(4)", $employees_table_name ) );
            }
        }

        // Add pozicia_id column if it doesn't exist
        $pozicia_column = $wpdb->get_results( $wpdb->prepare(
            "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'pozicia_id'",
            DB_NAME,
            $employees_table_name
        ) );

        if ( empty( $pozicia_column ) ) {
            $wpdb->query( $wpdb->prepare( 
                "ALTER TABLE %i ADD COLUMN pozicia_id BIGINT UNSIGNED DEFAULT NULL",
                $employees_table_name 
            ) );
        }

        // Add je_v_pohotovosti column if it doesn't exist
        $pohotovosti_column = $wpdb->get_results( $wpdb->prepare(
            "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'je_v_pohotovosti'",
            DB_NAME,
            $employees_table_name
        ) );

        if ( empty( $pohotovosti_column ) ) {
            $wpdb->query( $wpdb->prepare( 
                "ALTER TABLE %i ADD COLUMN je_v_pohotovosti TINYINT(1) DEFAULT 0",
                $employees_table_name 
            ) );
        }

        // Add riesenie_2 column to bugs table if it doesn't exist
        $bugs_table_name = $wpdb->prefix . 'hd_riesenia';
        $riesenie_2_column = $wpdb->get_results( $wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'riesenie_2'",
            DB_NAME,
            $bugs_table_name
        ) );
        
        if ( empty( $riesenie_2_column ) ) {
            // Add the column - use @ to suppress errors
            @$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD COLUMN riesenie_2 LONGTEXT DEFAULT NULL AFTER riesenie", $bugs_table_name ) );
        }

        $bug_codes_table_name = $wpdb->prefix . 'hd_ciselnik_kody_chyb';
        $bug_codes_exists = $wpdb->get_results( $wpdb->prepare(
            "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
            DB_NAME,
            $bug_codes_table_name
        ) );
        
        if ( ! empty( $bug_codes_exists ) ) {
            $bug_codes_aktivny = $wpdb->get_results( $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'aktivny'",
                DB_NAME,
                $bug_codes_table_name
            ) );
            if ( empty( $bug_codes_aktivny ) ) {
                // Add the column - use @ to suppress errors
                @$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD COLUMN aktivny TINYINT(1) DEFAULT 1", $bug_codes_table_name ) );
            }

            // Ensure uplny_popis column exists in bug codes table
            $bug_codes_uplny_popis = $wpdb->get_results( $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'uplny_popis'",
                DB_NAME,
                $bug_codes_table_name
            ) );
            if ( empty( $bug_codes_uplny_popis ) ) {
                // Add the column - use @ to suppress errors
                @$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD COLUMN uplny_popis LONGTEXT AFTER popis", $bug_codes_table_name ) );
            }
        }

        // Ensure aktivny column exists in products table
        $products_table_name = $wpdb->prefix . 'hd_ciselnik_produkty';
        $products_exists = $wpdb->get_results( $wpdb->prepare(
            "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
            DB_NAME,
            $products_table_name
        ) );
        
        if ( ! empty( $products_exists ) ) {
            $products_aktivny = $wpdb->get_results( $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'aktivny'",
                DB_NAME,
                $products_table_name
            ) );
            if ( empty( $products_aktivny ) ) {
                // Add the column - use @ to suppress errors
                @$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD COLUMN aktivny TINYINT(1) DEFAULT 1", $products_table_name ) );
            }
            
            // Ensure link column exists in products table
            $products_link = $wpdb->get_results( $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'link'",
                DB_NAME,
                $products_table_name
            ) );
            if ( empty( $products_link ) ) {
                // Add the column - use @ to suppress errors
                @$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD COLUMN link VARCHAR(500) DEFAULT NULL", $products_table_name ) );
            }
        }

        // Ensure tagy column exists in bugs table
        $bugs_table_name = $wpdb->prefix . 'hd_riesenia';
        $bugs_exists = $wpdb->get_results( $wpdb->prepare(
            "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
            DB_NAME,
            $bugs_table_name
        ) );
        
        if ( ! empty( $bugs_exists ) ) {
            $bugs_tagy = $wpdb->get_results( $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'tagy'",
                DB_NAME,
                $bugs_table_name
            ) );
            if ( empty( $bugs_tagy ) ) {
                // Add the column - use @ to suppress errors
                @$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD COLUMN tagy TEXT DEFAULT NULL", $bugs_table_name ) );
                // Add FULLTEXT index
                @$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD FULLTEXT KEY idx_tagy (tagy)", $bugs_table_name ) );
            }
        }

        // Add nemenit column to project_employee table if it doesn't exist
        $pe_table_name = $wpdb->prefix . 'hd_projekt_pracovnik';
        $nemenit_column = $wpdb->get_results( $wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'nemenit'",
            DB_NAME,
            $pe_table_name
        ) );
        
        if ( empty( $nemenit_column ) ) {
            @$wpdb->query( $wpdb->prepare( 
                "ALTER TABLE %i ADD COLUMN nemenit BOOLEAN NOT NULL DEFAULT FALSE",
                $pe_table_name 
            ) );
        }

        // Add nemenit column to projects table if it doesn't exist
        $projects_table_nemenit = $wpdb->prefix . 'hd_projekty';
        $projects_nemenit_column = $wpdb->get_results( $wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'nemenit'",
            DB_NAME,
            $projects_table_nemenit
        ) );
        
        if ( empty( $projects_nemenit_column ) ) {
            @$wpdb->query( $wpdb->prepare( 
                "ALTER TABLE %i ADD COLUMN nemenit BOOLEAN NOT NULL DEFAULT FALSE",
                $projects_table_nemenit 
            ) );
        }

        // Add skratka column to positions table if it doesn't exist
        $positions_table_name = $wpdb->prefix . 'hd_ciselnik_pozicie';
        $positions_skratka = $wpdb->get_results( $wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'skratka'",
            DB_NAME,
            $positions_table_name
        ) );
        
        if ( empty( $positions_skratka ) ) {
            @$wpdb->query( $wpdb->prepare( 
                "ALTER TABLE %i ADD COLUMN skratka VARCHAR(50) DEFAULT NULL",
                $positions_table_name 
            ) );
        }

        // Add hd_kontakt column to projects table if it doesn't exist
        $projects_kontakt_column = $wpdb->get_results( $wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'hd_kontakt'",
            DB_NAME,
            $projects_table_nemenit
        ) );
        
        if ( empty( $projects_kontakt_column ) ) {
            @$wpdb->query( $wpdb->prepare( 
                "ALTER TABLE %i ADD COLUMN hd_kontakt VARCHAR(255) DEFAULT NULL",
                $projects_table_nemenit 
            ) );
        }

        // Add poznamka column to projects table if it doesn't exist
        $projects_poznamka_column = $wpdb->get_results( $wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'poznamka'",
            DB_NAME,
            $projects_table_nemenit
        ) );
        
        if ( empty( $projects_poznamka_column ) ) {
            @$wpdb->query( $wpdb->prepare( 
                "ALTER TABLE %i ADD COLUMN poznamka TEXT DEFAULT NULL",
                $projects_table_nemenit 
            ) );
        }

        // Add nepritomnost_od column to employees table if it doesn't exist
        $nepritomnost_od_column = $wpdb->get_results( $wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'nepritomnost_od'",
            DB_NAME,
            $employees_table_name
        ) );
        
        if ( empty( $nepritomnost_od_column ) ) {
            @$wpdb->query( $wpdb->prepare( 
                "ALTER TABLE %i ADD COLUMN nepritomnost_od DATE DEFAULT NULL",
                $employees_table_name 
            ) );
        }

        // Add nepritomnost_do column to employees table if it doesn't exist
        $nepritomnost_do_column = $wpdb->get_results( $wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'nepritomnost_do'",
            DB_NAME,
            $employees_table_name
        ) );
        
        if ( empty( $nepritomnost_do_column ) ) {
            @$wpdb->query( $wpdb->prepare( 
                "ALTER TABLE %i ADD COLUMN nepritomnost_do DATE DEFAULT NULL",
                $employees_table_name 
            ) );
        }

        // Add operacny_system column to bug codes table if it doesn't exist
        $operacny_system_column = $wpdb->get_results( 
            $wpdb->prepare( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = %s AND COLUMN_NAME = %s", 
                            substr( $bug_codes_table_name, strlen( $wpdb->prefix ) ), 'operacny_system' ) 
        );
        
        if ( empty( $operacny_system_column ) ) {
            @$wpdb->query( $wpdb->prepare( 
                "ALTER TABLE %i ADD COLUMN operacny_system VARCHAR(255) DEFAULT NULL AFTER uplny_popis",
                $bug_codes_table_name 
            ) );
        }

        // Add produkt column to bug codes table if it doesn't exist
        $produkt_column = $wpdb->get_results( 
            $wpdb->prepare( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = %s AND COLUMN_NAME = %s", 
                            substr( $bug_codes_table_name, strlen( $wpdb->prefix ) ), 'produkt' ) 
        );
        
        if ( empty( $produkt_column ) ) {
            @$wpdb->query( $wpdb->prepare( 
                "ALTER TABLE %i ADD COLUMN produkt BIGINT UNSIGNED DEFAULT NULL AFTER operacny_system",
                $bug_codes_table_name 
            ) );
        }
    }

    /**
     * Get employees table name
     */
    public static function get_employees_table() {
        global $wpdb;
        return $wpdb->prefix . 'hd_pracovnici';
    }

    /**
     * Get projects table name
     */
    public static function get_projects_table() {
        global $wpdb;
        return $wpdb->prefix . 'hd_projekty';
    }

    /**
     * Get project employee relationship table name
     */
    public static function get_project_employee_table() {
        global $wpdb;
        return $wpdb->prefix . 'hd_projekt_pracovnik';
    }

    /**
     * Get bugs table name
     */
    public static function get_bugs_table() {
        global $wpdb;
        return $wpdb->prefix . 'hd_riesenia';
    }

    /**
     * Get standby table name
     */
    public static function get_standby_table() {
        global $wpdb;
        return $wpdb->prefix . 'hd_pracovnik_pohotovost';
    }

    /**
     * Get bug codes lookup table name
     */
    public static function get_bug_codes_table() {
        global $wpdb;
        return $wpdb->prefix . 'hd_ciselnik_kody_chyb';
    }

    /**
     * Get products lookup table name
     */
    public static function get_products_table() {
        global $wpdb;
        return $wpdb->prefix . 'hd_ciselnik_produkty';
    }

    /**
     * Get operating systems lookup table name
     */
    public static function get_os_table() {
        global $wpdb;
        return $wpdb->prefix . 'hd_ciselnik_os';
    }

    /**
     * Get contacts lookup table name
     */
    public static function get_contacts_table() {
        global $wpdb;
        return $wpdb->prefix . 'hd_ciselnik_kontakty';
    }

    /**
     * Get positions lookup table name
     */
    public static function get_positions_table() {
        global $wpdb;
        return $wpdb->prefix . 'hd_ciselnik_pozicie';
    }

    /**
     * Get communication methods lookup table name
     */
    public static function get_communication_methods_table() {
        global $wpdb;
        return $wpdb->prefix . 'hd_ciselnik_sposob_komunikacie';
    }

    /**
     * Get vacations table name
     */
    public static function get_vacations_table() {
        global $wpdb;
        return $wpdb->prefix . 'hd_nepritomnosti';
    }

    /**
     * Get signatures table name
     */
    public static function get_signatures_table() {
        global $wpdb;
        return $wpdb->prefix . 'hd_podpisy';
    }
}
