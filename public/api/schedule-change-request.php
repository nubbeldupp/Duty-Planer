<?php
namespace OnCallDutyPlanner\API;

use OnCallDutyPlanner\Classes\Authentication;
use OnCallDutyPlanner\Database\DatabaseConnection;
use OnCallDutyPlanner\Scheduling\ScheduleManager;
use OnCallDutyPlanner\Services\EmailService;
use Exception;
use PDO;
use PDOException;

header('Content-Type: application/json');

class ScheduleChangeRequestAPI {
    private $db;
    private $auth;
    private $scheduleManager;
    private $emailService;

    public function __construct() {
        $this->db = new DatabaseConnection();
        $this->auth = new Authentication();
        $this->emailService = new EmailService();
        $this->scheduleManager = new ScheduleManager($this->db, $this->emailService);
    }

    public function handleRequest() {
        // Ensure user is logged in
        session_start();
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        // Determine request method
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'POST':
                $this->createChangeRequest();
                break;
            case 'GET':
                $this->getChangeRequests();
                break;
            case 'PUT':
                $this->updateChangeRequestStatus();
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method Not Allowed']);
                break;
        }
    }

    private function createChangeRequest() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $requester_id = $_SESSION['user_id'];
        $original_schedule_id = $input['schedule_id'];
        $target_user_id = $input['target_user_id'];
        $new_start = $input['new_start'];
        $new_end = $input['new_end'];
        $reason = $input['reason'] ?? 'Schedule change request';

        try {
            $conn = $this->db->getConnection();
            
            // Validate that the requester has permission to request change
            $permission_check_sql = "
                SELECT 1 
                FROM on_call_schedules s
                JOIN user_teams ut ON s.team_id = ut.team_id
                WHERE s.schedule_id = :schedule_id AND ut.user_id = :requester_id
            ";
            $stmt = $conn->prepare($permission_check_sql);
            $stmt->execute([
                ':schedule_id' => $original_schedule_id,
                ':requester_id' => $requester_id
            ]);

            if ($stmt->rowCount() === 0) {
                http_response_code(403);
                echo json_encode(['error' => 'Insufficient permissions']);
                exit;
            }

            // Insert change request
            $sql = "
                INSERT INTO schedule_change_requests (
                    original_schedule_id, 
                    requested_user_id, 
                    target_user_id, 
                    new_start_datetime, 
                    new_end_datetime, 
                    request_reason
                ) VALUES (
                    :original_schedule_id, 
                    :requested_user_id, 
                    :target_user_id, 
                    :new_start,
                    :new_end,
                    :request_reason
                )
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':original_schedule_id' => $original_schedule_id,
                ':requested_user_id' => $requester_id,
                ':target_user_id' => $target_user_id,
                ':new_start' => $new_start,
                ':new_end' => $new_end,
                ':request_reason' => $reason
            ]);

            if ($stmt->rowCount() > 0) {
                // Send notification email to target user
                $this->sendChangeRequestNotification($target_user_id, $reason);

                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Schedule change request submitted'
                ]);
            } else {
                throw new Exception("Database error: unable to insert change request");
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error', 'details' => $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error', 'details' => $e->getMessage()]);
        }
    }

    private function sendChangeRequestNotification($target_user_id, $reason) {
        // Fetch target user's email
        $sql = "SELECT email, first_name FROM users WHERE user_id = :user_id";
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute([':user_id' => $target_user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Use PHPMailer to send email (configuration in a separate file)
            require_once __DIR__ . '/../../config/email.php';
            
            $mailer = new EmailService();
            $mailer->sendScheduleChangeNotification($user['email'], "Hello {$user['first_name']},\n\nA schedule change request has been submitted. Reason: {$reason}\n\nPlease log in to review and approve/reject.");
        }
    }

    private function getChangeRequests() {
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role'];

        try {
            $conn = $this->db->getConnection();
            
            // Different query based on user role
            if ($user_role === 'ADMIN') {
                $sql = "
                    SELECT scr.*, 
                           CONCAT(u1.first_name, ' ', u1.last_name) AS requester_name,
                           CONCAT(u2.first_name, ' ', u2.last_name) AS target_name,
                           os.start_datetime AS original_start,
                           os.end_datetime AS original_end
                    FROM schedule_change_requests scr
                    JOIN users u1 ON scr.requested_user_id = u1.user_id
                    JOIN users u2 ON scr.target_user_id = u2.user_id
                    JOIN on_call_schedules os ON scr.original_schedule_id = os.schedule_id
                    WHERE scr.status = 'PENDING'
                ";
            } elseif ($user_role === 'TEAM_LEAD') {
                $sql = "
                    SELECT scr.*, 
                           CONCAT(u1.first_name, ' ', u1.last_name) AS requester_name,
                           CONCAT(u2.first_name, ' ', u2.last_name) AS target_name,
                           os.start_datetime AS original_start,
                           os.end_datetime AS original_end
                    FROM schedule_change_requests scr
                    JOIN users u1 ON scr.requested_user_id = u1.user_id
                    JOIN users u2 ON scr.target_user_id = u2.user_id
                    JOIN on_call_schedules os ON scr.original_schedule_id = os.schedule_id
                    JOIN user_teams ut ON os.team_id = ut.team_id
                    WHERE scr.status = 'PENDING' AND ut.user_id = :user_id
                ";
            } else {
                $sql = "
                    SELECT scr.*, 
                           CONCAT(u1.first_name, ' ', u1.last_name) AS requester_name,
                           CONCAT(u2.first_name, ' ', u2.last_name) AS target_name,
                           os.start_datetime AS original_start,
                           os.end_datetime AS original_end
                    FROM schedule_change_requests scr
                    JOIN users u1 ON scr.requested_user_id = u1.user_id
                    JOIN users u2 ON scr.target_user_id = u2.user_id
                    JOIN on_call_schedules os ON scr.original_schedule_id = os.schedule_id
                    WHERE scr.target_user_id = :user_id
                ";
            }

            $stmt = $conn->prepare($sql);
            
            if ($user_role !== 'ADMIN') {
                $stmt->bindParam(':user_id', $user_id);
            }

            $stmt->execute();

            $requests = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $requests[] = [
                    'request_id' => $row['request_id'],
                    'requester_name' => $row['requester_name'],
                    'target_name' => $row['target_name'],
                    'new_start' => $row['new_start_datetime'],
                    'new_end' => $row['new_end_datetime'],
                    'original_start' => $row['original_start'],
                    'original_end' => $row['original_end'],
                    'status' => $row['status'],
                    'reason' => $row['request_reason']
                ];
            }

            echo json_encode($requests);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error', 'details' => $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error', 'details' => $e->getMessage()]);
        }
    }

    private function updateChangeRequestStatus() {
        $user_id = $_SESSION['user_id'];
        $input = json_decode(file_get_contents('php://input'), true);
        
        $request_id = $input['request_id'];
        $status = $input['status']; // 'APPROVED' or 'REJECTED'

        try {
            $conn = $this->db->getConnection();
            
            // Validate that the user can approve/reject this request
            $permission_check_sql = "
                SELECT 1 
                FROM schedule_change_requests scr
                WHERE scr.request_id = :request_id AND scr.target_user_id = :user_id
            ";
            $stmt = $conn->prepare($permission_check_sql);
            $stmt->execute([
                ':request_id' => $request_id,
                ':user_id' => $user_id
            ]);

            if ($stmt->rowCount() === 0) {
                http_response_code(403);
                echo json_encode(['error' => 'Not authorized to approve/reject this request']);
                exit;
            }

            // Update request status
            $sql = "
                UPDATE schedule_change_requests 
                SET status = :status 
                WHERE request_id = :request_id
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':status' => $status,
                ':request_id' => $request_id
            ]);

            if ($stmt->rowCount() > 0 && $status === 'APPROVED') {
                // If approved, update the actual schedule
                $update_schedule_sql = "
                    UPDATE on_call_schedules os
                    SET start_datetime = (
                        SELECT new_start_datetime 
                        FROM schedule_change_requests scr 
                        WHERE scr.original_schedule_id = os.schedule_id 
                        AND scr.request_id = :request_id
                    ),
                    end_datetime = (
                        SELECT new_end_datetime 
                        FROM schedule_change_requests scr 
                        WHERE scr.original_schedule_id = os.schedule_id 
                        AND scr.request_id = :request_id
                    )
                    WHERE schedule_id IN (
                        SELECT original_schedule_id 
                        FROM schedule_change_requests 
                        WHERE request_id = :request_id
                    )
                ";
                
                $update_stmt = $conn->prepare($update_schedule_sql);
                $update_stmt->execute([':request_id' => $request_id]);
            }

            echo json_encode([
                'status' => 'success', 
                'message' => "Schedule change request {$status}"
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error', 'details' => $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error', 'details' => $e->getMessage()]);
        }
    }
}

// Require Composer's autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Handle the API request
$scheduleChangeAPI = new ScheduleChangeRequestAPI();
$scheduleChangeAPI->handleRequest();
