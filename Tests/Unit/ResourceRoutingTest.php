<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Controller\ResourceController;
use Core\Model\Request;
use Core\Model\Response;
use Core\Model\Router;
use PHPUnit\Framework\TestCase;

class PhotoController extends ResourceController
{
    public function show(Request $request, Response $response, string $id)
    {
        return "photo:$id";
    }
}

final class ResourceRoutingTest extends TestCase
{
    private function router(string $method = 'GET', string $uri = '/'): Router
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        return new Router(new Request(), new Response());
    }

    public function testResourceRegistersSevenRoutes(): void
    {
        $router = $this->router();
        $routes = $router->resource('photos', PhotoController::class);

        $this->assertCount(7, $routes);
    }

    public function testResourceRouteMethodsAndPaths(): void
    {
        $router = $this->router();
        $router->resource('photos', PhotoController::class);

        $signatures = array_map(
            fn ($r) => $r->getMethod() . ' ' . $r->getPath(),
            $router->getRoutes()
        );

        $this->assertSame([
            'GET /photos',
            'GET /photos/create',
            'POST /photos',
            'GET /photos/{id}',
            'GET /photos/{id}/edit',
            'PUT /photos/{id}',
            'DELETE /photos/{id}',
        ], $signatures);
    }

    public function testCreateRouteMatchesBeforeShow(): void
    {
        $router = $this->router('GET', '/photos/create');
        $router->resource('photos', PhotoController::class);

        // "create" must resolve to the create action (501 default), not show().
        $result = $router->resolve();
        $this->assertSame(['error' => "Action 'create' is not implemented."], $result);
    }

    public function testShowRouteDispatchesToOverriddenAction(): void
    {
        $router = $this->router('GET', '/photos/42');
        $router->resource('photos', PhotoController::class);

        $this->assertSame('photo:42', $router->resolve());
    }

    public function testUnimplementedActionReturns501Payload(): void
    {
        $router = $this->router('GET', '/photos');
        $router->resource('photos', PhotoController::class);

        $this->assertSame(['error' => "Action 'index' is not implemented."], $router->resolve());
    }
}
