<?php
namespace OnCallDutyPlanner\Scheduling;

use DateTime;
use Exception;
use PDO;
use OnCallDutyPlanner\Exceptions\ScheduleException;
use OnCallDutyPlanner\ErrorHandler;

class ScheduleManager {
    private $db;
    private $emailService;

    public function __construct($db, $emailService) {
        $this->db = $db;
        $this->emailService = $emailService;
    }

    public function createSchedule(array $scheduleData) {
        try {
            // Validate schedule data
            $this->validateScheduleCreation($scheduleData);

            // Check for scheduling conflicts
            $conflicts = $this->checkScheduleConflicts(
                $scheduleData['user_id'], 
                new DateTime($scheduleData['start_datetime']), 
                new DateTime($scheduleData['end_datetime'])
            );

            if (!empty($conflicts)) {
                throw new ScheduleException(
                    "Schedule conflicts exist for the specified time range", 
                    ScheduleException::CONFLICT_ERROR,
                    null,
                    ['conflicts' => $conflicts]
                );
            }

            // Insert schedule
            $sql = "INSERT INTO on_call_schedules 
                    (user_id, team_id, start_datetime, end_datetime, duty_type, status) 
                    VALUES (:user_id, :team_id, :start, :end, :duty_type, :status)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $scheduleData['user_id'],
                ':team_id' => $scheduleData['team_id'],
                ':start' => $scheduleData['start_datetime'],
                ':end' => $scheduleData['end_datetime'],
                ':duty_type' => $scheduleData['duty_type'],
                ':status' => 'ACTIVE'
            ]);

            $scheduleId = $this->db->lastInsertId();

            // Log audit trail
            ErrorHandler::logAuditTrail(
                'schedule_created', 
                [
                    'schedule_id' => $scheduleId,
                    'user_id' => $scheduleData['user_id'],
                    'team_id' => $scheduleData['team_id']
                ],
                $scheduleData['user_id']
            );

            return $scheduleId;

        } catch (ScheduleException $e) {
            // Re-throw to be caught by global handler
            throw $e;
        } catch (\PDOException $e) {
            // Handle database-specific errors
            throw new ScheduleException(
                "Database error while creating schedule", 
                ScheduleException::VALIDATION_ERROR,
                $e,
                ['sql_error' => $e->getMessage()]
            );
        } catch (\Exception $e) {
            // Catch any other unexpected errors
            throw new ScheduleException(
                "Unexpected error during schedule creation", 
                ScheduleException::VALIDATION_ERROR,
                $e
            );
        }
    }

    public function checkScheduleConflicts(int $userId, DateTime $startTime, DateTime $endTime) {
        try {
            $sql = "SELECT * FROM on_call_schedules 
                    WHERE user_id = :user_id 
                    AND status = 'ACTIVE'
                    AND (
                        (start_datetime BETWEEN :start AND :end)
                        OR (end_datetime BETWEEN :start AND :end)
                        OR (:start BETWEEN start_datetime AND end_datetime)
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':start' => $startTime->format('Y-m-d H:i:s'),
                ':end' => $endTime->format('Y-m-d H:i:s')
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // Handle database-specific errors
            throw new ScheduleException(
                "Database error while checking schedule conflicts", 
                ScheduleException::VALIDATION_ERROR,
                $e,
                ['sql_error' => $e->getMessage()]
            );
        } catch (\Exception $e) {
            // Catch any other unexpected errors
            throw new ScheduleException(
                "Unexpected error during schedule conflict check", 
                ScheduleException::VALIDATION_ERROR,
                $e
            );
        }
    }

    private function validateScheduleCreation(array $scheduleData) {
        try {
            $requiredFields = [
                'user_id', 'team_id', 'start_datetime', 
                'end_datetime', 'duty_type'
            ];

            foreach ($requiredFields as $field) {
                if (!isset($scheduleData[$field]) || empty($scheduleData[$field])) {
                    throw new ScheduleException(
                        "Missing required field: $field", 
                        ScheduleException::VALIDATION_ERROR,
                        null,
                        ['field' => $field]
                    );
                }
            }

            // Validate datetime
            $startTime = new DateTime($scheduleData['start_datetime']);
            $endTime = new DateTime($scheduleData['end_datetime']);

            if ($startTime >= $endTime) {
                throw new ScheduleException(
                    "Invalid time range: start time must be before end time", 
                    ScheduleException::VALIDATION_ERROR,
                    null,
                    ['start_time' => $startTime, 'end_time' => $endTime]
                );
            }

            // Validate duty type
            $validDutyTypes = ['PERMANENT', 'OCCASIONALLY'];
            if (!in_array($scheduleData['duty_type'], $validDutyTypes)) {
                throw new ScheduleException(
                    "Invalid duty type", 
                    ScheduleException::VALIDATION_ERROR,
                    null,
                    ['duty_type' => $scheduleData['duty_type']]
                );
            }
        } catch (\Exception $e) {
            // Catch any other unexpected errors
            throw new ScheduleException(
                "Unexpected error during schedule validation", 
                ScheduleException::VALIDATION_ERROR,
                $e
            );
        }
    }

    public function generateTeamScheduleReport(int $teamId, DateTime $startPeriod, DateTime $endPeriod) {
        try {
            $sql = "SELECT u.first_name, u.last_name, s.start_datetime, s.end_datetime, s.duty_type
                    FROM on_call_schedules s
                    JOIN users u ON s.user_id = u.user_id
                    WHERE s.team_id = :team_id 
                    AND s.start_datetime BETWEEN :start AND :end
                    ORDER BY s.start_datetime";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':team_id' => $teamId,
                ':start' => $startPeriod->format('Y-m-d H:i:s'),
                ':end' => $endPeriod->format('Y-m-d H:i:s')
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // Handle database-specific errors
            throw new ScheduleException(
                "Database error while generating team schedule report", 
                ScheduleException::VALIDATION_ERROR,
                $e,
                ['sql_error' => $e->getMessage()]
            );
        } catch (\Exception $e) {
            // Catch any other unexpected errors
            throw new ScheduleException(
                "Unexpected error during team schedule report generation", 
                ScheduleException::VALIDATION_ERROR,
                $e
            );
        }
    }
}
