<?php
namespace OnCallDutyPlanner\Exceptions;

class ScheduleException extends \Exception {
    // Error codes for specific schedule-related errors
    public const 
        CONFLICT_ERROR = 1001,
        VALIDATION_ERROR = 1002,
        PERMISSION_DENIED = 1003,
        RESOURCE_NOT_FOUND = 1004,
        DUPLICATE_SCHEDULE = 1005;

    private $errorContext;

    public function __construct(
        string $message, 
        int $code = 0, 
        ?\Throwable $previous = null, 
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorContext = $context;
    }

    public function getErrorContext(): array {
        return $this->errorContext;
    }

    public function logError() {
        // Log error with context
        error_log(sprintf(
            "Schedule Error [%d]: %s\nContext: %s", 
            $this->getCode(), 
            $this->getMessage(), 
            json_encode($this->errorContext)
        ));
    }

    public function getHttpStatusCode(): int {
        return match($this->getCode()) {
            self::CONFLICT_ERROR => 409,
            self::VALIDATION_ERROR => 400,
            self::PERMISSION_DENIED => 403,
            self::RESOURCE_NOT_FOUND => 404,
            self::DUPLICATE_SCHEDULE => 409,
            default => 500
        };
    }

    public function toArray(): array {
        return [
            'error' => true,
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
            'context' => $this->getErrorContext()
        ];
    }
}
