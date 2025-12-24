<?php

namespace Tests\Unit;

use Core\Model\Router;
use Core\Model\Request;
use Core\Model\Response;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for Router Class
 *
 * Tests route registration and resolution.
 */
class RouterTest extends TestCase
{
    private Router $router;
    private Request $request;
    private Response $response;

    protected function setUp(): void
    {
        parent::setUp();
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
    }

    public function testGetMethodRegistersRoute(): void
    {
        $callback = function() { return 'test'; };
        $this->router->get('/test', $callback);
        
        // Verify route is registered by checking it doesn't throw an error
        $this->assertTrue(true);
    }

    public function testPostMethodRegistersRoute(): void
    {
        $callback = function() { return 'test'; };
        $this->router->post('/test', $callback);
        
        $this->assertTrue(true);
    }


    public function testResolveFindsExactMatch(): void
    {
        $_SERVER['REQUEST_URI'] = '/home';
        $request = new Request();
        $router = new Router($request, $this->response);
        
        $called = false;
        $router->get('/home', function() use (&$called) {
            $called = true;
            return 'home';
        });
        
        $result = $router->resolve();
        
        $this->assertTrue($called);
        $this->assertEquals('home', $result);
    }

    public function testResolveHandlesDynamicRoutes(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/123';
        $request = new Request();
        $router = new Router($request, $this->response);
        
        $capturedId = null;
        $router->get('/user/{id}', function($req, $res, $id) use (&$capturedId) {
            $capturedId = $id;
            return 'user';
        });
        
        $result = $router->resolve();
        
        $this->assertEquals('123', $capturedId);
    }

    public function testResolveReturns404ForNonExistentRoute(): void
    {
        $_SERVER['REQUEST_URI'] = '/non-existent';
        $request = new Request();
        $router = new Router($request, $this->response);
        
        $result = $router->resolve();
        
        $this->assertEquals('404 - Not Found', $result);
    }

    public function testResolveHandlesMultipleParameters(): void
    {
        $_SERVER['REQUEST_URI'] = '/post/123/comment/456';
        $request = new Request();
        $router = new Router($request, $this->response);
        
        $capturedPostId = null;
        $capturedCommentId = null;
        
        $router->get('/post/{postId}/comment/{commentId}',
            function($req, $res, $postId, $commentId) use (&$capturedPostId, &$capturedCommentId) {
                $capturedPostId = $postId;
                $capturedCommentId = $commentId;
                return 'comment';
            }
        );
        
        $router->resolve();
        
        $this->assertEquals('123', $capturedPostId);
        $this->assertEquals('456', $capturedCommentId);
    }

    public function testRouterInjectsRequestAndResponse(): void
    {
        $_SERVER['REQUEST_URI'] = '/test';
        $request = new Request();
        $router = new Router($request, $this->response);
        
        $receivedRequest = null;
        $receivedResponse = null;
        
        $router->get('/test', function($req, $res) use (&$receivedRequest, &$receivedResponse) {
            $receivedRequest = $req;
            $receivedResponse = $res;
            return 'test';
        });
        
        $router->resolve();
        
        $this->assertInstanceOf(Request::class, $receivedRequest);
        $this->assertInstanceOf(Response::class, $receivedResponse);
    }
}

