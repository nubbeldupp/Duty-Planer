<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../classes/Authentication.php';
require_once __DIR__ . '/../../config/database.php';

class UserPermissionsAPI {
    private $db;
    private $auth;

    public function __construct() {
        $this->db = new DatabaseConnection();
        $this->auth = new Authentication();
    }

    public function getUserPermissions() {
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
            
            // Get user's teams
            $teams_sql = "
                SELECT dt.team_id, dt.team_name 
                FROM database_teams dt
                JOIN user_teams ut ON dt.team_id = ut.team_id
                WHERE ut.user_id = :user_id
            ";
            
            $stmt = $conn->prepare($teams_sql);
            $stmt->execute([':user_id' => $user_id]);

            $user_teams = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $user_teams[] = [
                    'team_id' => $row['team_id'],
                    'team_name' => $row['team_name']
                ];
            }

            // Prepare permissions response
            $permissions = [
                'userId' => $user_id,
                'role' => $user_role,
                'isAdmin' => $user_role === 'ADMIN',
                'isTeamLead' => $user_role === 'TEAM_LEAD',
                'teams' => $user_teams,
                'canCreateSchedule' => in_array($user_role, ['ADMIN', 'TEAM_LEAD']),
                'canEditAllSchedules' => $user_role === 'ADMIN',
                'canEditTeamSchedules' => in_array($user_role, ['ADMIN', 'TEAM_LEAD'])
            ];

            echo json_encode($permissions);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error', 'details' => $e->getMessage()]);
        }
    }
}

// Handle the API request
$userPermissionsAPI = new UserPermissionsAPI();
$userPermissionsAPI->getUserPermissions();
