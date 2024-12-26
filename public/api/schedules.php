<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../classes/Authentication.php';
require_once __DIR__ . '/../../config/database.php';

class ScheduleAPI {
    private $db;
    private $auth;

    public function __construct() {
        $this->db = new DatabaseConnection();
        $this->auth = new Authentication();
    }

    public function getSchedules() {
        // Ensure user is logged in
        session_start();
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role'];

        try {
            $conn = $this->db->getConnection();
            
            // Different query based on user role
            if ($user_role === 'ADMIN') {
                // Admins see all schedules
                $sql = "SELECT 
                            s.schedule_id, 
                            u.first_name || ' ' || u.last_name AS title,
                            s.start_datetime AS start, 
                            s.end_datetime AS end,
                            dt.team_name AS team,
                            s.duty_type
                        FROM on_call_schedules s
                        JOIN users u ON s.user_id = u.user_id
                        JOIN database_teams dt ON s.team_id = dt.team_id
                        WHERE s.status = 'ACTIVE'";
            } elseif ($user_role === 'TEAM_LEAD') {
                // Team leads see schedules for their teams
                $sql = "SELECT 
                            s.schedule_id, 
                            u.first_name || ' ' || u.last_name AS title,
                            s.start_datetime AS start, 
                            s.end_datetime AS end,
                            dt.team_name AS team,
                            s.duty_type
                        FROM on_call_schedules s
                        JOIN users u ON s.user_id = u.user_id
                        JOIN database_teams dt ON s.team_id = dt.team_id
                        JOIN user_teams ut ON dt.team_id = ut.team_id
                        WHERE s.status = 'ACTIVE' AND ut.user_id = :user_id";
            } else {
                // Regular users see only their own schedules
                $sql = "SELECT 
                            s.schedule_id, 
                            u.first_name || ' ' || u.last_name AS title,
                            s.start_datetime AS start, 
                            s.end_datetime AS end,
                            dt.team_name AS team,
                            s.duty_type
                        FROM on_call_schedules s
                        JOIN users u ON s.user_id = u.user_id
                        JOIN database_teams dt ON s.team_id = dt.team_id
                        WHERE s.user_id = :user_id AND s.status = 'ACTIVE'";
            }

            $stmt = $conn->prepare($sql);
            
            if ($user_role !== 'ADMIN') {
                $stmt->execute([':user_id' => $user_id]);
            } else {
                $stmt->execute();
            }

            $schedules = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $schedules[] = [
                    'id' => $row['schedule_id'],
                    'title' => $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['team_name'] . ')',
                    'start' => $row['start_datetime'],
                    'end' => $row['end_datetime'],
                    'extendedProps' => [
                        'team' => $row['team_name'],
                        'dutyType' => $row['duty_type']
                    ]
                ];
            }

            echo json_encode($schedules);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error', 'details' => $e->getMessage()]);
        }
    }
}

// Handle the API request
$scheduleAPI = new ScheduleAPI();
$scheduleAPI->getSchedules();
