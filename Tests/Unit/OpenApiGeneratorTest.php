<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\OpenApiGenerator;
use Core\Model\Request;
use Core\Model\Response;
use Core\Model\Router;
use PHPUnit\Framework\TestCase;

final class OpenApiGeneratorTest extends TestCase
{
    private function router(): Router
    {
        return new Router(new Request(), new Response());
    }

    public function testGenerateIncludesDocumentInfo(): void
    {
        $generator = new OpenApiGenerator(['title' => 'Test API', 'version' => '2.0.0']);
        $spec = $generator->generate($this->router());

        $this->assertSame('3.0.3', $spec['openapi']);
        $this->assertSame('Test API', $spec['info']['title']);
        $this->assertSame('2.0.0', $spec['info']['version']);
    }

    public function testGenerateListsEachRegisteredRouteByPathAndMethod(): void
    {
        $router = $this->router();
        $router->get('/users', 'System\Controller\UserController@index');
        $router->post('/users', 'System\Controller\UserController@store');

        $spec = (new OpenApiGenerator())->generate($router);

        $this->assertArrayHasKey('/users', $spec['paths']);
        $this->assertArrayHasKey('get', $spec['paths']['/users']);
        $this->assertArrayHasKey('post', $spec['paths']['/users']);
    }

    public function testGenerateExtractsPathParameters(): void
    {
        $router = $this->router();
        $router->get('/users/{id}', 'System\Controller\UserController@show');

        $spec = (new OpenApiGenerator())->generate($router);
        $parameters = $spec['paths']['/users/{id}']['get']['parameters'];

        $this->assertCount(1, $parameters);
        $this->assertSame('id', $parameters[0]['name']);
        $this->assertSame('path', $parameters[0]['in']);
        $this->assertTrue($parameters[0]['required']);
    }

    public function testGenerateUsesRouteNameAsOperationIdWhenPresent(): void
    {
        $router = $this->router();
        $router->get('/users', 'System\Controller\UserController@index')->name('users.index');

        $spec = (new OpenApiGenerator())->generate($router);

        $this->assertSame('users.index', $spec['paths']['/users']['get']['operationId']);
    }

    public function testGenerateFallsBackToDerivedOperationIdWithoutRouteName(): void
    {
        $router = $this->router();
        $router->get('/users', 'System\Controller\UserController@index');

        $spec = (new OpenApiGenerator())->generate($router);

        $this->assertSame(
            'get_System_Controller_UserController_index',
            $spec['paths']['/users']['get']['operationId']
        );
    }

    public function testGenerateSkipsVersionSegmentWhenDerivingTag(): void
    {
        $router = $this->router();
        $router->get('/api/v1/users', 'System\Controller\UserController@index');

        $spec = (new OpenApiGenerator())->generate($router);

        $this->assertSame(['users'], $spec['paths']['/api/v1/users']['get']['tags']);
    }

    public function testGenerateExposesMiddlewareAsExtension(): void
    {
        $router = $this->router();
        $router->get('/reports', 'System\Controller\ReportController@index')->middleware(['throttle', 'auth']);

        $spec = (new OpenApiGenerator())->generate($router);

        $this->assertSame(['throttle', 'auth'], $spec['paths']['/reports']['get']['x-middleware']);
    }

    public function testGenerateOmitsMiddlewareExtensionWhenNoneAttached(): void
    {
        $router = $this->router();
        $router->get('/users', 'System\Controller\UserController@index');

        $spec = (new OpenApiGenerator())->generate($router);

        $this->assertArrayNotHasKey('x-middleware', $spec['paths']['/users']['get']);
    }
}
