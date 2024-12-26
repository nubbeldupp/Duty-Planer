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
            
            $teams_stmt = oci_parse($conn, $teams_sql);
            oci_bind_by_name($teams_stmt, ":user_id", $user_id);
            oci_execute($teams_stmt);

            $user_teams = [];
            while ($row = oci_fetch_assoc($teams_stmt)) {
                $user_teams[] = [
                    'team_id' => $row['TEAM_ID'],
                    'team_name' => $row['TEAM_NAME']
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
