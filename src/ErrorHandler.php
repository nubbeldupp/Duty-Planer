<?php
namespace OnCallDutyPlanner;

use OnCallDutyPlanner\Exceptions\ScheduleException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ErrorHandler {
    private static $logger;

    public static function initialize() {
        // Setup global error handling
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);

        // Initialize logging
        self::$logger = new Logger('on-call-duty-planner');
        self::$logger->pushHandler(
            new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG)
        );
    }

    public static function handleError(
        int $errno, 
        string $errstr, 
        string $errfile, 
        int $errline
    ) {
        // Convert error to exception
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public static function handleException(\Throwable $exception) {
        // Log the full exception
        self::$logger->error('Unhandled Exception', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Handle specific exception types
        if ($exception instanceof ScheduleException) {
            self::handleScheduleException($exception);
        } else {
            self::handleGenericException($exception);
        }
    }

    private static function handleScheduleException(ScheduleException $exception) {
        // Log the specific schedule exception
        $exception->logError();

        // Send response
        http_response_code($exception->getHttpStatusCode());
        header('Content-Type: application/json');
        echo json_encode($exception->toArray());
        exit;
    }

    private static function handleGenericException(\Throwable $exception) {
        // Generic error handling
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => 'Internal Server Error',
            'details' => $exception->getMessage()
        ]);
        exit;
    }

    public static function logAuditTrail(
        string $action, 
        array $details, 
        ?int $userId = null
    ) {
        // Audit logging
        self::$logger->info('Audit Trail', [
            'action' => $action,
            'user_id' => $userId,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
