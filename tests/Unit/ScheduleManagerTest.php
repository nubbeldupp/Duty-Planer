<?php
namespace OnCallDutyPlanner\Tests\Unit;

use OnCallDutyPlanner\Tests\TestCase;
use OnCallDutyPlanner\Scheduling\ScheduleManager;
use OnCallDutyPlanner\Classes\Authentication;
use PDO;

class ScheduleManagerTest extends TestCase {
    private $scheduleManager;
    private $auth;
    private $userId;
    private $teamId;

    protected function setUp(): void {
        parent::setUp();
        $this->resetDatabase();
        $this->scheduleManager = new ScheduleManager();
        $this->auth = new Authentication();

        // Create a test user
        $this->auth->register(
            'scheduleuser', 
            'schedule@example.com', 
            'password123', 
            'Schedule', 
            'User', 
            'ADMIN'
        );
        
        // Get the user ID
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = 'scheduleuser'");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->userId = $user['user_id'];

        // Create a test team
        $stmt = $conn->prepare("INSERT INTO database_teams (team_name, description) VALUES ('TestTeam', 'Test Team Description')");
        $stmt->execute();
        $this->teamId = $conn->lastInsertId();
    }

    public function testCreateSchedule() {
        $scheduleData = [
            'user_id' => $this->userId,
            'team_id' => $this->teamId,
            'start_datetime' => '2024-01-01 09:00:00',
            'end_datetime' => '2024-01-02 09:00:00',
            'duty_type' => 'PERMANENT',
            'created_by' => $this->userId
        ];

        $scheduleId = $this->scheduleManager->createSchedule($scheduleData);
        $this->assertNotFalse($scheduleId, 'Schedule creation should succeed');
    }

    public function testScheduleConflict() {
        $scheduleData1 = [
            'user_id' => $this->userId,
            'team_id' => $this->teamId,
            'start_datetime' => '2024-02-01 09:00:00',
            'end_datetime' => '2024-02-02 09:00:00',
            'duty_type' => 'PERMANENT',
            'created_by' => $this->userId
        ];

        $scheduleData2 = [
            'user_id' => $this->userId,
            'team_id' => $this->teamId,
            'start_datetime' => '2024-02-01 10:00:00',
            'end_datetime' => '2024-02-02 11:00:00',
            'duty_type' => 'PERMANENT',
            'created_by' => $this->userId
        ];

        $scheduleId1 = $this->scheduleManager->createSchedule($scheduleData1);
        
        // This should return false due to conflict
        $scheduleId2 = $this->scheduleManager->createSchedule($scheduleData2);
        
        $this->assertFalse($scheduleId2, 'Conflicting schedule should not be created');
    }

    public function testUpdateSchedule() {
        $scheduleData = [
            'user_id' => $this->userId,
            'team_id' => $this->teamId,
            'start_datetime' => '2024-03-01 09:00:00',
            'end_datetime' => '2024-03-02 09:00:00',
            'duty_type' => 'PERMANENT',
            'created_by' => $this->userId
        ];

        $scheduleId = $this->scheduleManager->createSchedule($scheduleData);

        $updateData = [
            'start_datetime' => '2024-03-01 10:00:00',
            'end_datetime' => '2024-03-02 10:00:00'
        ];

        $updateResult = $this->scheduleManager->updateSchedule($scheduleId, $updateData);
        $this->assertTrue($updateResult, 'Schedule update should succeed');
    }
}
