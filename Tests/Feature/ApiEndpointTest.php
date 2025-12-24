<?php

namespace Tests\Feature;

use Core\Model\Router;
use Core\Model\Request;
use Core\Model\Response;
use Core\Model\Validation;
use Core\Model\Database\Database;
use PHPUnit\Framework\TestCase;

/**
 * Feature Test: API Endpoint Validation
 *
 * Tests complete API request/response cycle with validation.
 */
class ApiEndpointTest extends TestCase
{
    private Router $router;
    private Request $request;
    private Response $response;
    private Validation $validation;
    private Database $mockDb;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockDb = $this->createMock(Database::class);
        $this->validation = new Validation($this->mockDb);
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
        parent::tearDown();
    }

    public function testApiGetRequestWithParameters(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/users/123';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        
        $capturedId = null;
        
        $this->router->get('/api/users/{id}', function($req, $res, $id) use (&$capturedId) {
            $capturedId = $id;
            return ['user_id' => $id, 'status' => 'found'];
        });
        
        $result = $this->router->resolve();
        
        $this->assertEquals('123', $capturedId);
        $this->assertIsArray($result);
        $this->assertEquals('123', $result['user_id']);
    }

    public function testApiPostWithJsonData(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/users';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        
        $_POST = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30
        ];
        
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        
        $receivedData = null;
        
        $this->router->post('/api/users', function($req, $res) use (&$receivedData) {
            $receivedData = $req->all();
            return ['success' => true, 'data' => $receivedData];
        });
        
        $result = $this->router->resolve();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('John Doe', $receivedData['name']);
    }

    public function testApiValidationOnPostRequest(): void
    {
        $_POST = [
            'name' => 'J',  // Too short
            'email' => 'invalid-email',
            'age' => 'not-a-number'
        ];
        
        $rules = [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email',
            'age' => 'required|numeric'
        ];
        
        $isValid = $this->validation->make($_POST, $rules);
        
        $this->assertFalse($isValid);
        $this->assertTrue($this->validation->fails());
        
        $errors = $this->validation->errors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('age', $errors);
    }

    public function testApiPutRequestFlow(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/api/users/456';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        $_POST = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ];
        
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        
        $capturedId = null;
        $capturedData = null;
        
        $this->router->put('/api/users/{id}', function($req, $res, $id) use (&$capturedId, &$capturedData) {
            $capturedId = $id;
            $capturedData = $req->all();
            return ['updated' => true, 'id' => $id];
        });
        
        $result = $this->router->resolve();
        
        $this->assertEquals('456', $capturedId);
        $this->assertTrue($result['updated']);
    }

    public function testApiDeleteRequestFlow(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['REQUEST_URI'] = '/api/users/789';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        
        $deletedId = null;
        
        $this->router->delete('/api/users/{id}', function($req, $res, $id) use (&$deletedId) {
            $deletedId = $id;
            return ['deleted' => true, 'id' => $id];
        });
        
        $result = $this->router->resolve();
        
        $this->assertEquals('789', $deletedId);
        $this->assertTrue($result['deleted']);
    }

    public function testComplexValidationRules(): void
    {
        $_POST = [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123',
            'age' => '25',
            'website' => 'https://example.com',
            'bio' => 'Test bio'
        ];
        
        $rules = [
            'username' => 'required|min:3|max:20|alpha',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'age' => 'numeric',
            'website' => 'url',
            'bio' => 'max:500'
        ];
        
        $isValid = $this->validation->make($_POST, $rules);
        
        $this->assertTrue($isValid);
        $this->assertTrue($this->validation->passes());
        $this->assertEmpty($this->validation->errors());
    }

    public function testNestedRouteParameters(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/users/100/posts/200/comments/300';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        
        $params = [];
        
        $this->router->get('/api/users/{userId}/posts/{postId}/comments/{commentId}',
            function($req, $res, $userId, $postId, $commentId) use (&$params) {
                $params = compact('userId', 'postId', 'commentId');
                return $params;
            }
        );
        
        $result = $this->router->resolve();
        
        $this->assertEquals('100', $params['userId']);
        $this->assertEquals('200', $params['postId']);
        $this->assertEquals('300', $params['commentId']);
    }

    public function testApiErrorHandlingFor404(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/nonexistent';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        
        $result = $this->router->resolve();
        
        $this->assertEquals('404 - Not Found', $result);
    }
}

