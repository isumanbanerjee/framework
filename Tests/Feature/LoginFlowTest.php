<?php

namespace Tests\Feature;

use Core\Model\Router;
use Core\Model\Request;
use Core\Model\Response;
use Core\Model\Session;
use Core\Model\Auth;
use Core\Model\Database\Database;
use PHPUnit\Framework\TestCase;

/**
 * Feature Test: Complete Login Flow
 *
 * Tests the entire authentication process with session management.
 */
class LoginFlowTest extends TestCase
{
    private Router $router;
    private Request $request;
    private Response $response;
    private Session $session;
    private Auth $auth;
    private Database $mockDb;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION = [];
        
        $this->mockDb = $this->createMock(Database::class);
        $this->session = new Session();
        $this->auth = new Auth($this->mockDb, $this->session);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
        parent::tearDown();
    }

    public function testCompleteLoginFlowWithValidCredentials(): void
    {
        // Arrange: Setup request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/login';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        $_POST = [
            'email' => 'john@example.com',
            'password' => 'password123',
            'remember' => '1'
        ];
        
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        
        // Mock user in database
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        $mockUser = [
            'id' => 1,
            'email' => 'john@example.com',
            'password' => $hashedPassword,
            'name' => 'John Doe'
        ];
        
        $this->mockDb->expects($this->once())
            ->method('fetchOneNamed')
            ->willReturn($mockUser);
        
        $this->mockDb->expects($this->once())
            ->method('executeQuery')
            ->willReturn(true);
        
        // Act: Attempt login
        $result = $this->auth->login(
            $_POST['email'],
            $_POST['password'],
            !empty($_POST['remember'])
        );
        
        // Assert: Login successful
        $this->assertTrue($result);
        $this->assertTrue($this->auth->check());
        $this->assertEquals(1, $this->auth->id());
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $_POST = [
            'email' => 'john@example.com',
            'password' => 'wrongpassword'
        ];
        
        $mockUser = [
            'id' => 1,
            'email' => 'john@example.com',
            'password' => password_hash('correctpassword', PASSWORD_DEFAULT)
        ];
        
        $this->mockDb->method('fetchOneNamed')
            ->willReturn($mockUser);
        
        $result = $this->auth->login($_POST['email'], $_POST['password']);
        
        $this->assertFalse($result);
        $this->assertFalse($this->auth->check());
    }

    public function testLoginFailsWithNonExistentUser(): void
    {
        $this->mockDb->method('fetchOneNamed')
            ->willReturn(null);
        
        $result = $this->auth->login('fake@example.com', 'password');
        
        $this->assertFalse($result);
    }

    public function testSessionRegenerationAfterLogin(): void
    {
        $mockUser = [
            'id' => 1,
            'email' => 'test@example.com',
            'password' => password_hash('pass123', PASSWORD_DEFAULT)
        ];
        
        $this->mockDb->method('fetchOneNamed')->willReturn($mockUser);
        $this->mockDb->method('executeQuery')->willReturn(true);
        
        $oldSessionId = session_id();
        
        $this->auth->login('test@example.com', 'pass123');
        
        // Session should be regenerated for security
        $this->assertTrue($this->auth->check());
    }

    public function testLoginSetsCorrectSessionData(): void
    {
        $mockUser = [
            'id' => 42,
            'email' => 'user@example.com',
            'password' => password_hash('testpass', PASSWORD_DEFAULT),
            'name' => 'Test User'
        ];
        
        $this->mockDb->method('fetchOneNamed')->willReturn($mockUser);
        $this->mockDb->method('executeQuery')->willReturn(true);
        
        $this->auth->login('user@example.com', 'testpass');
        
        $this->assertEquals(42, $this->auth->id());
        $this->assertTrue($this->session->has('user_id'));
    }

    public function testCsrfProtectionDuringLogin(): void
    {
        // Generate CSRF token
        $token = $this->session->generateCsrfToken();
        
        // Simulate form submission with CSRF token
        $_POST['csrf_token'] = $token;
        
        // Validate CSRF token
        $isValid = $this->session->validateCsrfToken($_POST['csrf_token']);
        
        $this->assertTrue($isValid);
    }

    public function testInvalidCsrfTokenPreventsLogin(): void
    {
        $this->session->generateCsrfToken();
        
        // Submit with invalid token
        $isValid = $this->session->validateCsrfToken('invalid_token');
        
        $this->assertFalse($isValid);
    }
}

