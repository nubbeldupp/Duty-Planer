<?php
require_once __DIR__ . '/../config/database.php';

class Authentication {
    private $db;

    public function __construct() {
        $this->db = new DatabaseConnection();
    }

    public function register($username, $email, $password, $first_name, $last_name, $role = 'USER') {
        // Validate input
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Hash password
        $password_hash = password_hash($password, PASSWORD_HASH_ALGO);

        // Prepare SQL
        $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, role) 
                VALUES (:username, :email, :password_hash, :first_name, :last_name, :role)";
        
        $params = [
            'username' => $username,
            'email' => $email,
            'password_hash' => $password_hash,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => $role
        ];

        try {
            $conn = $this->db->getConnection();
            $stmt = $this->db->executeQuery($sql, $params);
            return $stmt !== false;
        } catch (Exception $e) {
            error_log("Registration Error: " . $e->getMessage());
            return false;
        }
    }

    public function login($username, $password) {
        $sql = "SELECT user_id, username, password_hash, role, last_login 
                FROM users 
                WHERE username = :username";
        
        try {
            $conn = $this->db->getConnection();
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ":username", $username);
            
            if (!oci_execute($stmt)) {
                throw new Exception("Login query failed");
            }

            $user = oci_fetch_assoc($stmt);

            if (!$user || !password_verify($password, $user['PASSWORD_HASH'])) {
                return false;
            }

            // Update last login
            $update_sql = "UPDATE users SET last_login = SYSTIMESTAMP WHERE username = :username";
            $update_stmt = oci_parse($conn, $update_sql);
            oci_bind_by_name($update_stmt, ":username", $username);
            oci_execute($update_stmt);

            // Start session
            session_start();
            $_SESSION['user_id'] = $user['USER_ID'];
            $_SESSION['username'] = $user['USERNAME'];
            $_SESSION['role'] = $user['ROLE'];

            return true;
        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            return false;
        }
    }

    public function logout() {
        session_start();
        session_destroy();
        return true;
    }

    public function getCurrentUserRole() {
        session_start();
        return $_SESSION['role'] ?? null;
    }

    public function requireRole($allowed_roles) {
        $current_role = $this->getCurrentUserRole();
        if (!in_array($current_role, $allowed_roles)) {
            http_response_code(403);
            die("Access Denied");
        }
    }
}
