<?php
namespace OnCallDutyPlanner\Tests\Unit;

use OnCallDutyPlanner\Tests\TestCase;
use OnCallDutyPlanner\Classes\Authentication;
use PDOException;

class AuthenticationTest extends TestCase {
    private $auth;

    protected function setUp(): void {
        parent::setUp();
        $this->auth = new \OnCallDutyPlanner\Classes\Authentication();
        $this->resetDatabase();
    }

    public function testUserRegistration() {
        $result = $this->auth->register(
            'testuser', 
            'test@example.com', 
            'password123', 
            'Test', 
            'User'
        );

        $this->assertTrue($result, 'User registration should succeed');
    }

    public function testUserLogin() {
        // First register a user
        $this->auth->register(
            'loginuser', 
            'login@example.com', 
            'password123', 
            'Login', 
            'User'
        );

        // Then test login
        $loginResult = $this->auth->login('loginuser', 'password123');
        $this->assertTrue($loginResult, 'User login should succeed');

        // Test failed login
        $failedLogin = $this->auth->login('loginuser', 'wrongpassword');
        $this->assertFalse($failedLogin, 'Login with wrong password should fail');
    }

    public function testDuplicateRegistration() {
        // Register first time
        $firstRegistration = $this->auth->register(
            'uniqueuser', 
            'unique@example.com', 
            'password123', 
            'Unique', 
            'User'
        );

        // Try to register again with same username
        $secondRegistration = $this->auth->register(
            'uniqueuser', 
            'another@example.com', 
            'password456', 
            'Another', 
            'User'
        );

        $this->assertTrue($firstRegistration, 'First registration should succeed');
        $this->assertFalse($secondRegistration, 'Duplicate registration should fail');
    }
}
