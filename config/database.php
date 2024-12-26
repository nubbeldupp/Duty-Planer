<?php
class DatabaseConnection {
    private $conn;
    private $host = 'database';
    private $db_name = 'on_call_duty_planner';
    private $username = 'app_user';
    private $password = 'secure_password';
    private $port = 3306;

    public function getConnection() {
        $this->conn = null;

        try {
            // Establish MySQL PDO connection
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $exception) {
            error_log("Connection Error: " . $exception->getMessage());
            die("Could not connect to the database. Please try again later.");
        }

        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null;
    }

    // Utility method for executing prepared statements
    public function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            return false;
        }
    }
}

// Security and configuration constants
define('APP_SECRET_KEY', bin2hex(random_bytes(32)));
define('PASSWORD_HASH_ALGO', PASSWORD_ARGON2ID);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 15 * 60); // 15 minutes

// Email configuration
define('SMTP_HOST', 'smtp.yourcompany.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@yourcompany.com');
define('SMTP_PASSWORD', 'secure_email_password');
