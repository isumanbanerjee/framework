<?php

declare(strict_types=1);

namespace Tests\Feature;

use Core\Model\Request;
use Core\Model\Router;
use Core\Model\Session;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end coverage for routes/web.php: real Router + "csrf" middleware +
 * AuthController, wired exactly as index.php wires them.
 *
 * Only the DB-free branches are exercised here (rendering the forms, and
 * the pure-validation early returns in AuthController::login()/register()).
 * The success paths (Auth::login()/register() against a real users table)
 * need a live database and are out of scope for this in-process suite -
 * see Tests/README.md for why real Response/terminating middleware can't
 * be driven end-to-end without one (CsrfMiddleware's rejection branch
 * calls a bare `exit`, not a mockable Response method, so it also isn't
 * exercised here).
 */
final class AuthRoutesTest extends TestCase
{
    private array $originalGet;
    private array $originalPost;
    private array $originalSession;

    protected function setUp(): void
    {
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalSession = $_SESSION ?? [];
    }

    protected function tearDown(): void
    {
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_SESSION = $this->originalSession;
    }

    private function request(string $method, string $uri): Request
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        return new Request();
    }

    /**
     * @return array{0: Router, 1: RecordingResponse}
     */
    private function router(string $method, string $uri): array
    {
        $response = new RecordingResponse();
        $router = new Router($this->request($method, $uri), $response);

        require dirname(__DIR__, 2) . '/routes/web.php';

        return [$router, $response];
    }

    public function testGetLoginRendersFormWithCsrfToken(): void
    {
        [$router, $response] = $this->router('GET', '/login');
        $router->resolve();

        $this->assertSame(200, $response->statusCode);
        $this->assertStringContainsString('Sign in', $response->body);
        $this->assertStringContainsString('name="email"', $response->body);
        $this->assertMatchesRegularExpression(
            '/name="csrf_token" value="[0-9a-f]{64}"/',
            $response->body
        );
    }

    public function testGetRegisterRendersFormWithCsrfToken(): void
    {
        [$router, $response] = $this->router('GET', '/register');
        $router->resolve();

        $this->assertSame(200, $response->statusCode);
        $this->assertStringContainsString('Create an account', $response->body);
        $this->assertStringContainsString('name="password_confirmation"', $response->body);
        $this->assertMatchesRegularExpression(
            '/name="csrf_token" value="[0-9a-f]{64}"/',
            $response->body
        );
    }

    public function testPostLoginWithMissingFieldsRedirectsWithoutTouchingDatabase(): void
    {
        $token = (new Session())->generateCsrfToken();
        $_POST = ['email' => '', 'password' => '', 'csrf_token' => $token];

        [$router, $response] = $this->router('POST', '/login');
        $router->resolve();

        $this->assertSame(302, $response->statusCode);
        $this->assertSame('/login', $response->headers['Location']);
        $this->assertSame('Invalid email or password.', (new Session())->getFlash('error'));
    }

    public function testPostRegisterWithMismatchedPasswordConfirmationRedirectsWithoutTouchingDatabase(): void
    {
        $token = (new Session())->generateCsrfToken();
        $_POST = [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'not-the-same',
            'csrf_token' => $token,
        ];

        [$router, $response] = $this->router('POST', '/register');
        $router->resolve();

        $this->assertSame(302, $response->statusCode);
        $this->assertSame('/register', $response->headers['Location']);
        $this->assertSame('Password confirmation does not match.', (new Session())->getFlash('error'));
    }
}
