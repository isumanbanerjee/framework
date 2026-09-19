<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Request;
use Core\Model\Response;
use Core\Model\Route;
use Core\Model\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private function router(string $method = 'GET', string $uri = '/'): Router
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        return new Router(new Request(), new Response());
    }

    public function testVerbMethodsReturnRoute(): void
    {
        $router = $this->router();

        $this->assertInstanceOf(Route::class, $router->get('/a', fn () => 'a'));
        $this->assertInstanceOf(Route::class, $router->post('/b', fn () => 'b'));
        $this->assertInstanceOf(Route::class, $router->put('/c', fn () => 'c'));
        $this->assertInstanceOf(Route::class, $router->patch('/d', fn () => 'd'));
        $this->assertInstanceOf(Route::class, $router->delete('/e', fn () => 'e'));
        $this->assertInstanceOf(Route::class, $router->options('/f', fn () => 'f'));

        $this->assertCount(6, $router->getRoutes());
    }

    public function testRouteRecordsMethodAndPath(): void
    {
        $router = $this->router();
        $router->put('/users/{id}', fn () => 'ok');

        $route = $router->getRoutes()[0];
        $this->assertSame('PUT', $route->getMethod());
        $this->assertSame('/users/{id}', $route->getPath());
    }

    public function testResolveMatchesStaticRoute(): void
    {
        $router = $this->router('GET', '/hello');
        $router->get('/hello', fn ($req, $res) => 'world');

        $this->assertSame('world', $router->resolve());
    }

    public function testResolveExtractsParameters(): void
    {
        $router = $this->router('GET', '/users/42/posts/hello');
        $router->get('/users/{id}/posts/{slug}', fn ($req, $res, $id, $slug) => "$id:$slug");

        $this->assertSame('42:hello', $router->resolve());
    }

    public function testResolveReturns404WhenNoMatch(): void
    {
        $router = $this->router('GET', '/missing');
        $router->get('/exists', fn () => 'ok');

        $this->assertSame('404 - Not Found', $router->resolve());
    }

    public function testResolveRespectsHttpMethod(): void
    {
        $router = $this->router('POST', '/resource');
        $router->get('/resource', fn () => 'get');
        $router->post('/resource', fn () => 'post');

        $this->assertSame('post', $router->resolve());
    }

    public function testMatchRegistersMultipleMethods(): void
    {
        $router = $this->router();
        $routes = $router->match(['GET', 'POST'], '/multi', fn () => 'x');

        $this->assertCount(2, $routes);
        $this->assertSame('GET', $routes[0]->getMethod());
        $this->assertSame('POST', $routes[1]->getMethod());
    }

    public function testAnyRegistersAllVerbs(): void
    {
        $router = $this->router();
        $router->any('/all', fn () => 'x');

        $this->assertCount(6, $router->getRoutes());
    }

    public function testGroupAppliesPrefix(): void
    {
        $router = $this->router();
        $router->group(['prefix' => '/api/v1'], function ($r) {
            $r->get('/users', fn () => 'users');
        });

        $this->assertSame('/api/v1/users', $router->getRoutes()[0]->getPath());
    }

    public function testGroupAppliesMiddleware(): void
    {
        $router = $this->router();
        $router->group(['middleware' => ['api', 'throttle']], function ($r) {
            $r->get('/x', fn () => 'x');
        });

        $this->assertSame(['api', 'throttle'], $router->getRoutes()[0]->getMiddleware());
    }

    public function testNestedGroupsComposePrefixAndMiddleware(): void
    {
        $router = $this->router();
        $router->group(['prefix' => '/api', 'middleware' => ['a']], function ($r) {
            $r->group(['prefix' => '/v2', 'middleware' => ['b']], function ($r2) {
                $r2->get('/ping', fn () => 'pong');
            });
        });

        $route = $router->getRoutes()[0];
        $this->assertSame('/api/v2/ping', $route->getPath());
        $this->assertSame(['a', 'b'], $route->getMiddleware());
    }

    public function testFluentMiddlewareAndName(): void
    {
        $router = $this->router();
        $route = $router->get('/admin', fn () => 'x')
            ->middleware(['auth', 'admin'])
            ->name('admin.home');

        $this->assertSame(['auth', 'admin'], $route->getMiddleware());
        $this->assertSame('admin.home', $route->getName());
    }

    public function testMiddlewarePipelineRunsBeforeCallback(): void
    {
        $log = [];

        $router = $this->router('GET', '/guarded');
        $router->get('/guarded', function ($req, $res) use (&$log) {
            $log[] = 'handler';
            return 'done';
        })->middleware([
            function ($req, $res, $next) use (&$log) {
                $log[] = 'before';
                $result = $next($req, $res);
                $log[] = 'after';
                return $result;
            },
        ]);

        $result = $router->resolve();

        $this->assertSame('done', $result);
        $this->assertSame(['before', 'handler', 'after'], $log);
    }

    public function testTrailingSlashIsNormalized(): void
    {
        $router = $this->router('GET', '/trail/');
        $router->get('/trail', fn () => 'matched');

        $this->assertSame('matched', $router->resolve());
    }

    public function testToCacheableIncludesStringAndArrayActions(): void
    {
        $router = $this->router();
        $router->get('/users', 'System\Controller\UserController@index')->name('users.index');
        $router->post('/posts', [\stdClass::class, 'store'])->middleware(['auth', 'csrf']);

        $cacheable = $router->toCacheable();

        $this->assertSame(0, $cacheable['skipped']);
        $this->assertCount(2, $cacheable['routes']);
        $this->assertSame([
            'method' => 'GET',
            'path' => '/users',
            'action' => 'System\Controller\UserController@index',
            'middleware' => [],
            'name' => 'users.index',
        ], $cacheable['routes'][0]);
        $this->assertSame([
            'method' => 'POST',
            'path' => '/posts',
            'action' => [\stdClass::class, 'store'],
            'middleware' => ['auth', 'csrf'],
            'name' => null,
        ], $cacheable['routes'][1]);
    }

    public function testToCacheableSkipsClosureActionsAndClosureMiddleware(): void
    {
        $router = $this->router();
        $router->get('/closure-action', fn () => 'nope');
        $router->get('/closure-middleware', 'System\Controller\UserController@index')
            ->middleware([fn ($req, $res, $next) => $next($req, $res)]);
        $router->get('/cacheable', 'System\Controller\UserController@show');

        $cacheable = $router->toCacheable();

        $this->assertSame(2, $cacheable['skipped']);
        $this->assertCount(1, $cacheable['routes']);
        $this->assertSame('/cacheable', $cacheable['routes'][0]['path']);
    }

    public function testLoadCachedRebuildsRoutesAndTheyResolve(): void
    {
        $router = $this->router('GET', '/cached/42');
        $router->loadCached([
            [
                'method' => 'GET',
                'path' => '/cached/{id}',
                'action' => fn ($req, $res, $id) => "id:$id",
                'middleware' => [],
                'name' => 'cached.show',
            ],
        ]);

        $this->assertSame('id:42', $router->resolve());
        $this->assertSame('cached.show', $router->getRoutes()[0]->getName());
    }

    public function testToCacheableRoundTripsThroughVarExport(): void
    {
        $router = $this->router();
        $router->get('/users', 'System\Controller\UserController@index')->name('users.index');

        $cacheable = $router->toCacheable();
        $exported = var_export($cacheable['routes'], true);
        $restored = eval('return ' . $exported . ';');

        $newRouter = $this->router('GET', '/users');
        $newRouter->loadCached($restored);

        $this->assertSame(
            $cacheable['routes'][0]['action'],
            $newRouter->getRoutes()[0]->getAction()
        );
    }
}
