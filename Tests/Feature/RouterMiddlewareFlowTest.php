<?php

declare(strict_types=1);

namespace Tests\Feature;

use Core\Model\Request;
use Core\Model\Response;
use Core\Model\Router;
use Core\Model\SanitizeMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * A Response test double that records what would have been sent instead of
 * calling header()/echo/exit(), so a full Router->resolve() request cycle
 * (including built-in middleware that terminates the script on failure)
 * can be exercised inside a single PHPUnit process.
 */
final class RecordingResponse extends Response
{
    public ?int $statusCode = null;
    public array $headers = [];
    public ?string $body = null;

    public function setStatusCode(int $code): void
    {
        $this->statusCode = $code;
    }

    public function setHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    public function json(mixed $data, int $statusCode = 200): void
    {
        $this->body = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');
        $this->setStatusCode($statusCode);
    }

    public function redirect(string $url): void
    {
        $this->setHeader('Location', $url);
        $this->setStatusCode(302);
    }
}

/**
 * End-to-end tests exercising the real Router + Middleware pipeline +
 * controller-style callback together, as opposed to Tests/Unit/RouterTest.php
 * and Tests/Unit/SanitizeMiddlewareTest.php which each cover their component
 * in isolation.
 */
final class RouterMiddlewareFlowTest extends TestCase
{
    private array $originalGet;
    private array $originalPost;

    protected function setUp(): void
    {
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
    }

    protected function tearDown(): void
    {
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
    }

    private function request(string $method, string $uri): Request
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        return new Request();
    }

    public function testFullLifecycleRunsGroupMiddlewareThenController(): void
    {
        $_POST = ['name' => '  Ada Lovelace  ', 'email' => '  ada@example.com  '];

        $response = new RecordingResponse();
        $router = new Router($this->request('POST', '/api/v1/register'), $response);

        $router->group(['prefix' => '/api/v1', 'middleware' => [SanitizeMiddleware::class]], function ($r) {
            $r->post('/register', function (Request $request, Response $response) {
                $response->json([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                ], 201);

                return 'handled';
            });
        });

        $result = $router->resolve();

        $this->assertSame('handled', $result);
        $this->assertSame(201, $response->statusCode);

        $payload = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Ada Lovelace', $payload['name']);
        $this->assertSame('ada@example.com', $payload['email']);
    }

    public function testCustomMiddlewareShortCircuitsBeforeReachingController(): void
    {
        $controllerRan = false;

        $response = new RecordingResponse();
        $router = new Router($this->request('GET', '/admin/reports'), $response);

        $requireToken = function (Request $request, Response $response, \Closure $next) {
            if ($request->input('token') !== 'secret') {
                $response->json(['error' => 'unauthorized'], 401);

                return 'blocked';
            }

            return $next($request, $response);
        };

        $router->get('/admin/reports', function (Request $request, Response $response) use (&$controllerRan) {
            $controllerRan = true;
            $response->json(['data' => 'reports'], 200);

            return 'handled';
        })->middleware([$requireToken]);

        $result = $router->resolve();

        $this->assertSame('blocked', $result);
        $this->assertFalse($controllerRan);
        $this->assertSame(401, $response->statusCode);
        $this->assertStringContainsString('unauthorized', $response->body);
    }

    public function testMiddlewareCanMutateRequestBeforeControllerReadsIt(): void
    {
        $_GET = ['q' => '  hello world  '];

        $response = new RecordingResponse();
        $router = new Router($this->request('GET', '/search'), $response);

        $router->get('/search', function (Request $request, Response $response) {
            $response->json(['q' => $request->input('q')], 200);

            return 'searched';
        })->middleware([SanitizeMiddleware::class]);

        $router->resolve();

        $payload = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('hello world', $payload['q']);
    }

    public function testRouteParametersReachTheControllerAfterMiddlewareRuns(): void
    {
        $order = [];

        $response = new RecordingResponse();
        $router = new Router($this->request('GET', '/users/42/posts/hello-world'), $response);

        $router->get('/users/{id}/posts/{slug}', function (Request $request, Response $response, $id, $slug) use (&$order) {
            $order[] = 'controller';
            $response->json(['id' => $id, 'slug' => $slug], 200);

            return "$id:$slug";
        })->middleware([
            function (Request $request, Response $response, \Closure $next) use (&$order) {
                $order[] = 'middleware';

                return $next($request, $response);
            },
        ]);

        $result = $router->resolve();

        $this->assertSame('42:hello-world', $result);
        $this->assertSame(['middleware', 'controller'], $order);

        $payload = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('42', $payload['id']);
        $this->assertSame('hello-world', $payload['slug']);
    }

    public function testUnmatchedRouteReturns404WithoutRunningAnyMiddleware(): void
    {
        $middlewareRan = false;

        $response = new RecordingResponse();
        $router = new Router($this->request('GET', '/missing'), $response);

        $router->get('/exists', function () {
            return 'ok';
        })->middleware([
            function (Request $request, Response $response, \Closure $next) use (&$middlewareRan) {
                $middlewareRan = true;

                return $next($request, $response);
            },
        ]);

        $result = $router->resolve();

        $this->assertSame('404 - Not Found', $result);
        $this->assertSame(404, $response->statusCode);
        $this->assertFalse($middlewareRan);
    }

    public function testRedirectingMiddlewareStopsThePipelineAndSetsLocation(): void
    {
        $response = new RecordingResponse();
        $router = new Router($this->request('GET', '/dashboard'), $response);

        $requireLogin = function (Request $request, Response $response, \Closure $next) {
            $response->redirect('/login');

            return 'redirected';
        };

        $router->get('/dashboard', function () {
            return 'dashboard';
        })->middleware([$requireLogin]);

        $result = $router->resolve();

        $this->assertSame('redirected', $result);
        $this->assertSame(302, $response->statusCode);
        $this->assertSame('/login', $response->headers['Location']);
    }
}
