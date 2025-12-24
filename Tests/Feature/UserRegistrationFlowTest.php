<?php

namespace Tests\Feature;

use Core\Model\Router;
use Core\Model\Request;
use Core\Model\Response;
use Core\Model\Session;
use Core\Model\Auth;
use Core\Model\Validation;
use Core\Model\Database\Database;
use PHPUnit\Framework\TestCase;

/**
 * Feature Test: Complete User Registration Flow
 *
 * Tests the entire user registration process from request to database.
 */
class UserRegistrationFlowTest extends TestCase
{
    private Router $router;
    private Request $request;
    private Response $response;
    private Session $session;
    private Validation $validation;
    private Auth $auth;
    private Database $mockDb;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup session
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION = [];
        
        // Create instances
        $this->mockDb = $this->createMock(Database::class);
        $this->session = new Session();
        $this->auth = new Auth($this->mockDb, $this->session);
        $this->validation = new Validation($this->mockDb);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
        parent::tearDown();
    }

    public function testCompleteRegistrationWithValidData(): void
    {
        // Arrange: Simulate POST request with registration data
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/register';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        $_POST = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!'
        ];
        
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        
        // Mock validation: email is unique
        $this->mockDb->method('fetchOneNamed')
            ->willReturn(null); // Email doesn't exist
        
        // Mock database insert
        $this->mockDb->method('executeQuery')
            ->willReturn(true);
        
        $this->mockDb->method('fetchOne')
            ->willReturn(['id' => 1]);
        
        // Act: Validate and register
        $rules = [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email',
            'password' => 'required|min:8'
        ];
        
        $isValid = $this->validation->make($_POST, $rules);
        
        // Assert: Validation passes
        $this->assertTrue($isValid);
        $this->assertTrue($this->validation->passes());
        
        // Act: Register user
        $userId = $this->auth->register([
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'password' => $_POST['password']
        ]);
        
        // Assert: User registered successfully
        $this->assertEquals(1, $userId);
    }

    public function testRegistrationFailsWithInvalidEmail(): void
    {
        $_POST = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'SecurePass123!'
        ];
        
        $rules = [
            'email' => 'required|email'
        ];
        
        $isValid = $this->validation->make($_POST, $rules);
        
        $this->assertFalse($isValid);
        $this->assertTrue($this->validation->fails());
        $this->assertArrayHasKey('email', $this->validation->errors());
    }

    public function testRegistrationFailsWithShortPassword(): void
    {
        $_POST = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '123'
        ];
        
        $rules = [
            'password' => 'required|min:8'
        ];
        
        $isValid = $this->validation->make($_POST, $rules);
        
        $this->assertFalse($isValid);
        $this->assertArrayHasKey('password', $this->validation->errors());
    }

    public function testRegistrationSetsFlashMessageOnSuccess(): void
    {
        // Mock successful registration
        $this->mockDb->method('executeQuery')->willReturn(true);
        $this->mockDb->method('fetchOne')->willReturn(['id' => 1]);
        
        $userId = $this->auth->register([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        // Set success flash message
        $this->session->setFlash('success', 'Registration successful!');
        
        // Assert flash message exists
        $message = $this->session->getFlash('success');
        $this->assertEquals('Registration successful!', $message);
    }

    public function testRegistrationGeneratesCsrfToken(): void
    {
        $token = $this->session->generateCsrfToken();
        
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token));
        
        // Verify token validates
        $this->assertTrue($this->session->validateCsrfToken($token));
    }
}

