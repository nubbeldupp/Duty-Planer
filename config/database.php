<?php
class DatabaseConnection {
    private $conn;
    private $host = 'localhost';
    private $db_name = 'on_call_duty_planner';
    private $username = 'admin_user';
    private $password = 'secure_password_here';

    public function getConnection() {
        $this->conn = null;

        try {
            // Establish OCI8 connection
            $this->conn = oci_connect(
                $this->username, 
                $this->password, 
                "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$this->host})(PORT=1521))(CONNECT_DATA=(SERVICE_NAME={$this->db_name})))"
            );

            if (!$this->conn) {
                $e = oci_error();
                throw new Exception("Database connection failed: " . $e['message']);
            }
        } catch (Exception $exception) {
            error_log("Connection Error: " . $exception->getMessage());
            die("Could not connect to the database. Please try again later.");
        }

        return $this->conn;
    }

    public function closeConnection() {
        if ($this->conn) {
            oci_close($this->conn);
        }
    }

    // Utility method for executing prepared statements
    public function executeQuery($sql, $params = []) {
        $stmt = oci_parse($this->conn, $sql);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, ":$key", $params[$key]);
        }

        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            error_log("Query Error: " . $e['message']);
            return false;
        }

        return $stmt;
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
