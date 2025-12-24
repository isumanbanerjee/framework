<?php

namespace Tests\Integration;

use Core\Model\Router;
use Core\Model\Request;
use Core\Model\Response;
use PHPUnit\Framework\TestCase;

/**
 * Integration Tests for Router with Request/Response
 *
 * Tests complete routing flow with actual HTTP simulation.
 */
class RouterRequestResponseIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up basic server variables
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTPS'] = 'off';
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
        
        parent::tearDown();
    }

    public function testCompleteGetRequestFlow(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users';
        
        $request = new Request();
        $response = new Response();
        $router = new Router($request, $response);
        
        $executed = false;
        $router->get('/users', function($req, $res) use (&$executed) {
            $executed = true;
            return 'users list';
        });
        
        $result = $router->resolve();
        
        $this->assertTrue($executed);
        $this->assertEquals('users list', $result);
    }

    public function testCompletePostRequestFlow(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/users';
        $_POST = ['name' => 'John', 'email' => 'john@example.com'];
        
        $request = new Request();
        $response = new Response();
        $router = new Router($request, $response);
        
        $receivedData = null;
        $router->post('/users', function($req, $res) use (&$receivedData) {
            $receivedData = $req->postAll();
            return 'user created';
        });
        
        $result = $router->resolve();
        
        $this->assertEquals('user created', $result);
        $this->assertEquals('John', $receivedData['name']);
        $this->assertEquals('john@example.com', $receivedData['email']);
    }

    public function testDynamicRouteWithParameters(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users/123';
        
        $request = new Request();
        $response = new Response();
        $router = new Router($request, $response);
        
        $capturedId = null;
        $router->get('/users/{id}', function($req, $res, $id) use (&$capturedId) {
            $capturedId = $id;
            return "user $id";
        });
        
        $result = $router->resolve();
        
        $this->assertEquals('123', $capturedId);
        $this->assertEquals('user 123', $result);
    }

    public function testNestedDynamicRoutes(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users/123/posts/456';
        
        $request = new Request();
        $response = new Response();
        $router = new Router($request, $response);
        
        $capturedUserId = null;
        $capturedPostId = null;
        
        $router->get('/users/{userId}/posts/{postId}',
            function($req, $res, $userId, $postId) use (&$capturedUserId, &$capturedPostId) {
                $capturedUserId = $userId;
                $capturedPostId = $postId;
                return "user $userId, post $postId";
            }
        );
        
        $router->resolve();
        
        $this->assertEquals('123', $capturedUserId);
        $this->assertEquals('456', $capturedPostId);
    }

    public function testRequestAccessesHeadersInRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/data';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test-token';
        
        $request = new Request();
        $response = new Response();
        $router = new Router($request, $response);
        
        $capturedAuth = null;
        $router->get('/api/data', function($req, $res) use (&$capturedAuth) {
            $capturedAuth = $req->getHeader('Authorization');
            return 'data';
        });
        
        $router->resolve();
        
        $this->assertEquals('Bearer test-token', $capturedAuth);
    }

    public function testMultipleRoutesWithSameMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/about';
        
        $request = new Request();
        $response = new Response();
        $router = new Router($request, $response);
        
        $router->get('/home', function() { return 'home'; });
        $router->get('/about', function() { return 'about'; });
        $router->get('/contact', function() { return 'contact'; });
        
        $result = $router->resolve();
        
        $this->assertEquals('about', $result);
    }

    public function testDifferentMethodsSameRoute(): void
    {
        $_SERVER['REQUEST_URI'] = '/users';
        
        $request = new Request();
        $response = new Response();
        $router = new Router($request, $response);
        
        $router->get('/users', function() { return 'get users'; });
        $router->post('/users', function() { return 'create user'; });
        
        // Test GET
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request();
        $router = new Router($request, $response);
        $router->get('/users', function() { return 'get users'; });
        $router->post('/users', function() { return 'create user'; });
        $result1 = $router->resolve();
        
        // Test POST
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = new Request();
        $router = new Router($request, $response);
        $router->get('/users', function() { return 'get users'; });
        $router->post('/users', function() { return 'create user'; });
        $result2 = $router->resolve();
        
        $this->assertEquals('get users', $result1);
        $this->assertEquals('create user', $result2);
    }
}

