<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Request;
use Core\Model\Response;
use Core\Model\SanitizeMiddleware;
use PHPUnit\Framework\TestCase;

final class SanitizeMiddlewareTest extends TestCase
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

    public function testTrimsWhitespaceFromStringInput(): void
    {
        $_GET = ['name' => '  Alice  '];
        $_POST = ['bio' => "\tHello world\n"];

        $request = new Request();
        $response = new Response();
        $middleware = new SanitizeMiddleware();

        $middleware->handle($request, $response, function (Request $request, Response $response) {
            $this->assertSame('Alice', $request->all()['name']);
            $this->assertSame('Hello world', $request->all()['bio']);

            return $response;
        });
    }

    public function testConvertsEmptyStringToNull(): void
    {
        $_GET = ['middle_name' => '   '];
        $_POST = [];

        $request = new Request();
        $response = new Response();
        $middleware = new SanitizeMiddleware();

        $middleware->handle($request, $response, function (Request $request, Response $response) {
            $this->assertNull($request->all()['middle_name']);

            return $response;
        });
    }

    public function testLeavesNonStringValuesUntouched(): void
    {
        $_GET = ['page' => '2', 'active' => '1'];
        $_POST = ['tags' => ['  php  ', '', '  web ']];

        $request = new Request();
        $response = new Response();
        $middleware = new SanitizeMiddleware();

        $middleware->handle($request, $response, function (Request $request, Response $response) {
            $all = $request->all();
            $this->assertSame('2', $all['page']);
            $this->assertSame(['php', null, 'web'], $all['tags']);

            return $response;
        });
    }

    public function testCallsNextAndReturnsItsResult(): void
    {
        $_GET = [];
        $_POST = [];

        $request = new Request();
        $response = new Response();
        $middleware = new SanitizeMiddleware();

        $result = $middleware->handle($request, $response, function (Request $request, Response $response) {
            return $response;
        });

        $this->assertSame($response, $result);
    }
}
