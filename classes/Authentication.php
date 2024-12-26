<?php
namespace OnCallDutyPlanner\Classes;

use OnCallDutyPlanner\Config\DatabaseConnection;
use Exception;
use PDO;
use PDOException;

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
        $password_hash = password_hash($password, PASSWORD_ARGON2ID);

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
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute($params);
            return $result;
        } catch (PDOException $e) {
            // Check for duplicate entry
            if ($e->getCode() == '23000') {
                return false;
            }
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
            $stmt = $conn->prepare($sql);
            $stmt->execute([':username' => $username]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                return false;
            }

            // Update last login
            $update_sql = "UPDATE users SET last_login = NOW() WHERE username = :username";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->execute([':username' => $username]);

            // Start session
            session_start();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            return true;
        } catch (PDOException $e) {
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
