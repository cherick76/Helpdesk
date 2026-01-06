<?php
/**
 * Employee Model Class
 */

namespace HelpDesk\Models;

use HelpDesk\Utils\Database;

class Employee extends BaseModel {
    protected $table;

    public function __construct( $id = null ) {
        $this->table = Database::get_employees_table();
        parent::__construct( $id );
    }

    /**
     * Create new employee
     */
    public function create( $data ) {
        global $wpdb;

        // Build format array based on data fields
        $formats = array();
        foreach ( $data as $key => $value ) {
            switch( $key ) {
                case 'pozicia_id':
                case 'je_v_pohotovosti':
                    $formats[] = '%d';
                    break;
                default:
                    $formats[] = '%s';
                    break;
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
     * Update employee
     */
    public function update( $data ) {
        if ( ! $this->exists() ) {
            return false;
        }

        global $wpdb;
        
        // Build format array based on data fields
        $formats = array();
        foreach ( $data as $key => $value ) {
            switch( $key ) {
                case 'pozicia_id':
                case 'je_v_pohotovosti':
                    $formats[] = '%d';
                    break;
                default:
                    $formats[] = '%s';
                    break;
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
     * Check if klapka is unique
     */
    public function is_klapka_unique( $klapka, $exclude_id = null ) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT id FROM {$this->table} WHERE klapka = %s",
            $klapka
        );

        if ( $exclude_id ) {
            $query .= $wpdb->prepare( " AND id != %d", $exclude_id );
        }

        return ! $wpdb->get_var( $query );
    }

    /**
     * Get employee by klapka
     */
    public static function get_by_klapka( $klapka ) {
        global $wpdb;
        $instance = new self();

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$instance->table} WHERE klapka = %s",
                $klapka
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
     * Get employee by name (meno a priezvisko)
     */
    public static function get_by_name( $meno_priezvisko ) {
        global $wpdb;
        $instance = new self();

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$instance->table} WHERE meno_priezvisko = %s",
                $meno_priezvisko
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
     * Get projects for employee
     */
    public function get_projects() {
        global $wpdb;
        $employee_id = $this->get( 'id' );

        if ( ! $employee_id ) {
            return array();
        }

        $table = Database::get_project_employee_table();
        $projects_table = Database::get_projects_table();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.* FROM {$projects_table} p
                INNER JOIN {$table} pp ON p.id = pp.projekt_id
                WHERE pp.pracovnik_id = %d",
                $employee_id
            ),
            ARRAY_A
        );
    }

    /**
     * Assign to project
     */
    public function assign_to_project( $project_id, $is_main = false ) {
        global $wpdb;

        $table = Database::get_project_employee_table();

        // Check if already assigned
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, is_hlavny FROM {$table} WHERE projekt_id = %d AND pracovnik_id = %d",
                $project_id,
                $this->get( 'id' )
            )
        );

        if ( $existing ) {
            // Employee already assigned - preserve is_hlavny setting unless explicitly overriding
            // Only update if is_main is explicitly true (new assignment with main flag)
            if ( $is_main ) {
                return $wpdb->update(
                    $table,
                    array( 'is_hlavny' => 1 ),
                    array( 'id' => $existing->id ),
                    array( '%d' ),
                    array( '%d' )
                );
            }
            // Otherwise, keep existing is_hlavny value
            return true;
        }

        return $wpdb->insert(
            $table,
            array(
                'projekt_id' => $project_id,
                'pracovnik_id' => $this->get( 'id' ),
                'is_hlavny' => $is_main ? 1 : 0,
            ),
            array( '%d', '%d', '%d' )
        );
    }

    /**
     * Remove from project
     */
    public function remove_from_project( $project_id ) {
        global $wpdb;

        $table = Database::get_project_employee_table();

        return $wpdb->delete(
            $table,
            array(
                'projekt_id' => $project_id,
                'pracovnik_id' => $this->get( 'id' ),
            ),
            array( '%d', '%d' )
        );
    }

    /**
     * Get main project
     */
    public function get_main_project( $project_id ) {
        global $wpdb;

        $table = Database::get_project_employee_table();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE projekt_id = %d AND pracovnik_id = %d AND is_hlavny = 1",
                $project_id,
                $this->get( 'id' )
            ),
            ARRAY_A
        );
    }

    /**
     * Get standby periods for employee
     */
    public function get_standby_periods() {
        global $wpdb;

        $employee_id = $this->get( 'id' );
        if ( ! $employee_id ) {
            return array();
        }

        $standby_table = Database::get_standby_table();
        $projects_table = Database::get_projects_table();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT sp.*, p.nazov, p.projektove_cislo
                FROM {$standby_table} sp
                LEFT JOIN {$projects_table} p ON sp.projekt_id = p.id
                WHERE sp.pracovnik_id = %d AND sp.je_aktivna = 1
                ORDER BY sp.pohotovost_od DESC",
                $employee_id
            ),
            ARRAY_A
        );
    }

    /**
     * Get active standby project for today
     */
    public function get_active_standby_project() {
        global $wpdb;

        $employee_id = $this->get( 'id' );
        if ( ! $employee_id ) {
            return null;
        }

        $table = Database::get_standby_table();
        $today = date( 'Y-m-d' );

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT projekt_id FROM {$table}
                WHERE pracovnik_id = %d
                AND je_aktivna = 1
                AND pohotovost_od <= %s
                AND pohotovost_do >= %s
                LIMIT 1",
                $employee_id,
                $today,
                $today
            ),
            ARRAY_A
        );
    }

    /**
     * Add standby period
     */
    public function add_standby_period( $project_id, $od, $do ) {
        global $wpdb;

        $table = Database::get_standby_table();

        return $wpdb->insert(
            $table,
            array(
                'pracovnik_id' => $this->get( 'id' ),
                'projekt_id' => $project_id,
                'pohotovost_od' => $od,
                'pohotovost_do' => $do,
                'je_aktivna' => 1,
            ),
            array( '%d', '%d', '%s', '%s', '%d' )
        );
    }

    /**
     * Update standby period
     */
    public function update_standby_period( $standby_id, $project_id, $od, $do ) {
        global $wpdb;

        $table = Database::get_standby_table();

        return $wpdb->update(
            $table,
            array(
                'projekt_id' => $project_id,
                'pohotovost_od' => $od,
                'pohotovost_do' => $do,
            ),
            array( 'id' => $standby_id ),
            array( '%d', '%s', '%s' ),
            array( '%d' )
        );
    }

    /**
     * Delete standby period
     */
    public function delete_standby_period( $standby_id ) {
        global $wpdb;

        $table = Database::get_standby_table();

        return $wpdb->delete(
            $table,
            array( 'id' => $standby_id ),
            array( '%d' )
        );
    }

    /**
     * Get standby periods count
     */
    public function get_standby_periods_count() {
        global $wpdb;

        $table = Database::get_standby_table();
        $employee_id = $this->get( 'id' );

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE pracovnik_id = %d",
                $employee_id
            )
        );

        return intval( $count );
    }

    /**
     * Check if employee is assigned to project
     */
    public function is_assigned_to_project( $project_id ) {
        $projects = $this->get_projects();
        foreach ( $projects as $proj ) {
            if ( $proj['id'] == $project_id ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Alias for assign_to_project
     */
    public function add_project( $project_id, $is_main = false ) {
        return $this->assign_to_project( $project_id, $is_main );
    }

    /**
     * Get employees by position (skratka)
     */
    public static function get_by_position( $position_skratka ) {
        global $wpdb;
        $instance = new self();
        $employees_table = $instance->table;
        $positions_table = Database::get_positions_table();

        $query = $wpdb->prepare(
            "SELECT e.*, p.profesia, p.priorita, p.skratka
             FROM {$employees_table} e 
             LEFT JOIN {$positions_table} p ON e.pozicia_id = p.id
             WHERE p.skratka = %s
             ORDER BY e.meno_priezvisko ASC",
            $position_skratka
        );

        return $wpdb->get_results( $query, ARRAY_A );
    }

    /**
     * Get all employees with position and priority information
     */
    public static function get_all( $args = array() ) {
        global $wpdb;
        $instance = new self();
        $employees_table = $instance->table;
        $positions_table = Database::get_positions_table();

        $query = "SELECT e.*, p.profesia, p.priorita 
                  FROM {$employees_table} e 
                  LEFT JOIN {$positions_table} p ON e.pozicia_id = p.id
                  ORDER BY e.meno_priezvisko ASC";

        if ( ! empty( $args['search'] ) ) {
            $search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $query = $wpdb->prepare( 
                "SELECT e.*, p.profesia, p.priorita 
                 FROM {$employees_table} e 
                 LEFT JOIN {$positions_table} p ON e.pozicia_id = p.id
                 WHERE e.meno_priezvisko LIKE %s OR e.klapka LIKE %s
                 ORDER BY e.meno_priezvisko ASC",
                $search, $search 
            );
        }

        if ( ! empty( $args['limit'] ) ) {
            $query .= $wpdb->prepare( " LIMIT %d", $args['limit'] );
        }

        if ( ! empty( $args['offset'] ) ) {
            $query .= $wpdb->prepare( " OFFSET %d", $args['offset'] );
        }

        return $wpdb->get_results( $query, ARRAY_A );
    }
}
