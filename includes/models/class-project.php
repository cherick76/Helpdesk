<?php
/**
 * Project Model Class
 */

namespace HelpDesk\Models;

use HelpDesk\Utils\Database;

class Project extends BaseModel {
    protected $table;

    public function __construct( $id = null ) {
        $this->table = Database::get_projects_table();
        parent::__construct( $id );
    }

    /**
     * Create new project
     */
    public function create( $data ) {
        global $wpdb;

        // Add timestamps
        $data['created_at'] = current_time( 'mysql' );
        $data['updated_at'] = current_time( 'mysql' );

        // Build format array based on actual data keys
        $formats = array();
        foreach ( $data as $key => $value ) {
            if ( $key === 'id' || $key === 'pm_manazer_id' || $key === 'sla_manazer_id' ) {
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
     * Update project
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
            if ( $key === 'pm_manazer_id' || $key === 'sla_manazer_id' || $key === 'id' ) {
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
     * Check if projektove_cislo is unique
     */
    public function is_projektove_cislo_unique( $cislo, $exclude_id = null ) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT id FROM {$this->table} WHERE projektove_cislo = %s",
            $cislo
        );

        if ( $exclude_id ) {
            $query .= $wpdb->prepare( " AND id != %d", $exclude_id );
        }

        return ! $wpdb->get_var( $query );
    }

    /**
     * Get project by projektove_cislo
     */
    public static function get_by_cislo( $cislo ) {
        global $wpdb;
        $instance = new self();

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$instance->table} WHERE projektove_cislo = %s",
                $cislo
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
     * Get employees for project
     */
    public function get_employees() {
        global $wpdb;
        $project_id = $this->get( 'id' );

        if ( ! $project_id ) {
            return array();
        }

        $table = Database::get_project_employee_table();
        $employees_table = Database::get_employees_table();
        $positions_table = Database::get_positions_table();

        // Check if nemenit column exists in the table
        $nemenit_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'nemenit'",
                DB_NAME,
                $table
            )
        );

        // Build query with or without nemenit column depending on if it exists
        if ( $nemenit_exists ) {
            $query = $wpdb->prepare(
                "SELECT e.*, pp.is_hlavny, pp.nemenit, pos.profesia, pos.skratka, pos.priorita FROM {$employees_table} e
                INNER JOIN {$table} pp ON e.id = pp.pracovnik_id
                LEFT JOIN {$positions_table} pos ON e.pozicia_id = pos.id
                WHERE pp.projekt_id = %d",
                $project_id
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT e.*, pp.is_hlavny, 0 as nemenit, pos.profesia, pos.skratka, pos.priorita FROM {$employees_table} e
                INNER JOIN {$table} pp ON e.id = pp.pracovnik_id
                LEFT JOIN {$positions_table} pos ON e.pozicia_id = pos.id
                WHERE pp.projekt_id = %d",
                $project_id
            );
        }

        $results = $wpdb->get_results( $query, ARRAY_A );
        
        // Ensure is_hlavny is integer
        if ( $results ) {
            foreach ( $results as &$emp ) {
                $emp['is_hlavny'] = absint( $emp['is_hlavny'] );
            }
        }
        
        return $results;
    }

    /**
     * Get main employee
     */
    public function get_main_employee() {
        global $wpdb;
        $project_id = $this->get( 'id' );

        if ( ! $project_id ) {
            return null;
        }

        $table = Database::get_project_employee_table();
        $employees_table = Database::get_employees_table();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT e.* FROM {$employees_table} e
                INNER JOIN {$table} pp ON e.id = pp.pracovnik_id
                WHERE pp.projekt_id = %d AND pp.is_hlavny = 1",
                $project_id
            ),
            ARRAY_A
        );
    }

    /**
     * Add employee to project
     */
    public function add_employee( $employee_id, $is_main = false, $nemenit = false ) {
        global $wpdb;
        $project_id = $this->get( 'id' );

        // Note: is_main can now be 1 for multiple employees (not exclusive anymore)
        // The is_main flag simply marks which employees are primary contacts

        $employee = new Employee( $employee_id );
        return $employee->assign_to_project( $project_id, $is_main, $nemenit );
    }

    /**
     * Remove employee from project
     */
    public function remove_employee( $employee_id ) {
        $employee = new Employee( $employee_id );
        return $employee->remove_from_project( $this->get( 'id' ) );
    }

    /**
     * Get PM manager
     */
    public function get_pm_manager() {
        $pm_id = $this->get( 'pm_manazer_id' );

        if ( $pm_id ) {
            return new Employee( $pm_id );
        }

        return null;
    }

    /**
     * Set PM manager
     */
    public function set_pm_manager( $employee_id ) {
        $this->data['pm_manazer_id'] = $employee_id;
        return $this->update( array( 'pm_manazer_id' => $employee_id ) );
    }

    /**
     * Get all projects by PM manager
     */
    public static function get_by_pm_manager( $employee_id ) {
        global $wpdb;
        $instance = new self();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$instance->table} WHERE pm_manazer_id = %d",
                $employee_id
            ),
            ARRAY_A
        );
    }

    /**
     * Get employees with standby consideration
     */
    public function get_employees_with_standby() {
        $employees = $this->get_employees();
        $project_id = $this->get( 'id' );

        if ( ! $project_id || empty( $employees ) ) {
            return $employees;
        }

        $today = date( 'Y-m-d' );
        error_log( 'DEBUG Project::get_employees_with_standby - project_id: ' . $project_id . ', today: ' . $today );

        foreach ( $employees as &$emp ) {
            // Get ALL active standby records for this employee and project (not just today's)
            global $wpdb;
            $standby_table = Database::get_standby_table();
            $vacations_table = Database::get_vacations_table();
            
            // First, check if employee has standby TODAY (for is_active status)
            $standby_today = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id FROM {$standby_table}
                    WHERE pracovnik_id = %d
                    AND projekt_id = %d
                    AND je_aktivna = 1
                    AND pohotovost_od <= %s
                    AND pohotovost_do >= %s
                    LIMIT 1",
                    $emp['id'],
                    $project_id,
                    $today,
                    $today
                )
            );

            $emp['has_standby'] = ! empty( $standby_today );
            error_log( 'DEBUG Project::get_employees_with_standby - emp ' . $emp['meno_priezvisko'] . ' (id=' . $emp['id'] . '): has_standby=' . ( $emp['has_standby'] ? 'YES' : 'NO' ) );
            $emp['should_be_main'] = ! empty( $standby_today );
            
            // Get the latest/current standby record (active ones)
            $current_standby = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, pohotovost_od, pohotovost_do FROM {$standby_table}
                    WHERE pracovnik_id = %d
                    AND projekt_id = %d
                    AND je_aktivna = 1
                    ORDER BY pohotovost_od DESC
                    LIMIT 1",
                    $emp['id'],
                    $project_id
                )
            );

            if ( $current_standby ) {
                $emp['pohotovost_od'] = $current_standby->pohotovost_od;
                $emp['pohotovost_do'] = $current_standby->pohotovost_do;
            }
            
            // Get vacation/absence data for today (not project-specific, global for employee)
            $vacation_today = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT nepritomnost_od, nepritomnost_do FROM {$vacations_table}
                    WHERE pracovnik_id = %d
                    AND nepritomnost_od <= %s
                    AND nepritomnost_do >= %s
                    LIMIT 1",
                    $emp['id'],
                    $today,
                    $today
                ),
                ARRAY_A
            );
            
            if ( $vacation_today ) {
                $emp['nepritomnost_od'] = $vacation_today['nepritomnost_od'];
                $emp['nepritomnost_do'] = $vacation_today['nepritomnost_do'];
            }
            
            // Ensure is_hlavny is integer
            $emp['is_hlavny'] = absint( $emp['is_hlavny'] );
        }

        return $employees;
    }
}
