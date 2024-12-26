<?php
namespace OnCallDutyPlanner\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use OnCallDutyPlanner\Config\DatabaseConnection;

abstract class TestCase extends BaseTestCase {
    protected $db;

    protected function setUp(): void {
        $this->db = new DatabaseConnection();
    }

    protected function tearDown(): void {
        $this->db->closeConnection();
    }

    // Helper method to reset test database
    protected function resetDatabase() {
        $conn = $this->db->getConnection();
        $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");
        
        // Truncate all tables
        $tables = [
            'schedule_change_requests',
            'on_call_schedules',
            'user_teams',
            'database_teams',
            'users'
        ];

        foreach ($tables as $table) {
            $conn->exec("TRUNCATE TABLE $table;");
        }

        $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
}
