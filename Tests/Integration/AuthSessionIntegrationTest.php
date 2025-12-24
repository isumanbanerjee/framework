<?php

namespace Tests\Integration;

use Core\Model\Session;
use Core\Model\Auth;
use Core\Model\Database\Database;
use Core\Model\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Integration Tests for Auth with Session
 *
 * Tests authentication flow with actual session management.
 */
class AuthSessionIntegrationTest extends TestCase
{
    private Session $session;
    private Database $db;
    private Auth $auth;
    private Logger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        
        // Create logger
        $logFile = sys_get_temp_dir() . '/auth_test.log';
        $this->logger = new Logger($logFile);
        
        // Create database mock or connection
        $this->db = $this->createMock(Database::class);
        
        // Initialize session and auth
        $this->session = new Session();
        $this->auth = new Auth($this->db, $this->session);
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        
        // Clean up log file
        $logFile = sys_get_temp_dir() . '/auth_test.log';
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        
        parent::tearDown();
    }

    public function testAuthenticationSetsSessionData(): void
    {
        // Mock database to return a user
        $mockUser = [
            'id' => 1,
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT)
        ];
        
        $this->db->method('fetchOneNamed')
                 ->willReturn($mockUser);
        
        // Attempt login
        $result = $this->auth->login('test@example.com', 'password123');
        
        // Should set session data
        $this->assertTrue($result);
        $this->assertTrue($this->session->has('user_id'));
    }

    public function testSessionPersistsAcrossRequests(): void
    {
        // Set a value
        $this->session->set('test_key', 'test_value');
        
        // Simulate new request by creating new Session instance
        $newSession = new Session();
        
        // Value should persist
        $this->assertEquals('test_value', $newSession->get('test_key'));
    }

    public function testFlashMessageLifecycle(): void
    {
        // Set flash message
        $this->session->setFlash('success', 'Operation completed');
        
        // First access - message should exist
        $message1 = $this->session->getFlash('success');
        $this->assertEquals('Operation completed', $message1);
        
        // Simulate next request
        $newSession = new Session();
        
        // Second access - message should exist but be marked for removal
        $message2 = $newSession->getFlash('success');
        $this->assertEquals('Operation completed', $message2);
        
        // Third request - message should be gone
        $thirdSession = new Session();
        $message3 = $thirdSession->getFlash('success');
        $this->assertNull($message3);
    }

    public function testCsrfTokenPersistsInSession(): void
    {
        $token = $this->session->generateCsrfToken();
        
        // Create new session instance (same session ID)
        $newSession = new Session();
        $newToken = $newSession->generateCsrfToken();
        
        // Tokens should be the same
        $this->assertEquals($token, $newToken);
    }

    public function testSessionRegenerationPreservesData(): void
    {
        $this->session->set('important_data', 'preserve_this');
        $oldId = session_id();
        
        $this->session->regenerate();
        $newId = session_id();
        
        // ID should change
        $this->assertNotEquals($oldId, $newId);
        
        // Data should persist
        $this->assertEquals('preserve_this', $this->session->get('important_data'));
    }
}

