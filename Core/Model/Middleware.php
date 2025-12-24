<?php

namespace Core\Model;

use Exception;
use Closure;

/**
 * Enterprise Middleware System
 *
 * Provides request/response filtering and transformation through
 * a pipeline of middleware handlers. Supports authentication,
 * authorization, CORS, rate limiting, logging, and custom middleware.
 *
 * Features:
 * - Pipeline-based middleware execution
 * - Built-in authentication middleware
 * - CORS handling
 * - Rate limiting
 * - Request/response transformation
 * - Conditional middleware
 * - Middleware groups
 * - Priority-based execution
 *
 * @package Core\Model
 * @version 1.0.0
 * @since 2025-12-24
 */
class Middleware
{
    /**
     * Registered middleware
     *
     * @var array
     */
    private array $middleware = [];

    /**
     * Middleware groups
     *
     * @var array
     */
    private array $groups = [];

    /**
     * Middleware aliases
     *
     * @var array
     */
    private array $aliases = [];

    /**
     * Global middleware (runs on every request)
     *
     * @var array
     */
    private array $global = [];

    /**
     * Request instance
     *
     * @var Request
     */
    private Request $request;

    /**
     * Response instance
     *
     * @var Response
     */
    private Response $response;

    /**
     * Initialize middleware system
     *
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->registerBuiltInMiddleware();
    }

    /**
     * Register built-in middleware
     */
    private function registerBuiltInMiddleware(): void
    {
        $this->aliases = [
            'auth' => AuthMiddleware::class,
            'guest' => GuestMiddleware::class,
            'csrf' => CsrfMiddleware::class,
            'cors' => CorsMiddleware::class,
            'throttle' => ThrottleMiddleware::class,
            'admin' => AdminMiddleware::class,
            'json' => JsonMiddleware::class,
            'log' => LogMiddleware::class
        ];

        $this->groups = [
            'web' => ['csrf', 'log'],
            'api' => ['throttle', 'json', 'cors', 'log'],
            'admin' => ['auth', 'admin', 'csrf', 'log']
        ];
    }

    /**
     * Add middleware to pipeline
     *
     * @param string|callable $middleware
     * @param int $priority
     * @return self
     */
    public function add($middleware, int $priority = 10): self
    {
        $this->middleware[] = [
            'handler' => $middleware,
            'priority' => $priority
        ];

        return $this;
    }

    /**
     * Add global middleware
     *
     * @param string|callable $middleware
     * @return self
     */
    public function addGlobal($middleware): self
    {
        $this->global[] = $middleware;
        return $this;
    }

    /**
     * Add middleware group
     *
     * @param string $name
     * @param array $middleware
     * @return self
     */
    public function group(string $name, array $middleware): self
    {
        $this->groups[$name] = $middleware;
        return $this;
    }

    /**
     * Register middleware alias
     *
     * @param string $alias
     * @param string $class
     * @return self
     */
    public function alias(string $alias, string $class): self
    {
        $this->aliases[$alias] = $class;
        return $this;
    }

    /**
     * Execute middleware pipeline
     *
     * @param array|string $middleware Middleware to execute
     * @param Closure $destination Final handler
     * @return mixed
     */
    public function handle($middleware, Closure $destination)
    {
        try {
            // Normalize middleware
            $stack = $this->normalizeMiddleware($middleware);

            // Add global middleware
            $stack = array_merge($this->global, $stack);

            // Sort by priority
            usort($stack, function ($a, $b) {
                $priorityA = is_array($a) ? ($a['priority'] ?? 10) : 10;
                $priorityB = is_array($b) ? ($b['priority'] ?? 10) : 10;
                return $priorityA - $priorityB;
            });

            // Build pipeline
            $pipeline = $this->buildPipeline($stack, $destination);

            // Execute pipeline
            return $pipeline($this->request, $this->response);
        } catch (Exception $e) {
            if (defined('TEST_ENV') || php_sapi_name() === 'cli') {
                throw $e;
            }

            $error = new Error();
            $error->terminateWithError(
                'MIDDLEWARE_EXECUTION_FAILED',
                'Middleware execution failed: ' . $e->getMessage(),
                Error::SEVERITY_ERROR,
                ['middleware' => $middleware]
            );
        }
    }

    /**
     * Normalize middleware array
     *
     * @param array|string $middleware
     * @return array
     */
    private function normalizeMiddleware($middleware): array
    {
        if (is_string($middleware)) {
            $middleware = [$middleware];
        }

        $normalized = [];

        foreach ($middleware as $item) {
            // Check if it's a group
            if (isset($this->groups[$item])) {
                $normalized = array_merge($normalized, $this->normalizeMiddleware($this->groups[$item]));
                continue;
            }

            // Check if it's an alias
            if (isset($this->aliases[$item])) {
                $normalized[] = $this->aliases[$item];
                continue;
            }

            // Add as-is
            $normalized[] = $item;
        }

        return $normalized;
    }

    /**
     * Build middleware pipeline
     *
     * @param array $middleware
     * @param Closure $destination
     * @return Closure
     */
    private function buildPipeline(array $middleware, Closure $destination): Closure
    {
        return array_reduce(
            array_reverse($middleware),
            function ($next, $middleware) {
                return function ($request, $response) use ($next, $middleware) {
                    $handler = is_array($middleware) ? $middleware['handler'] : $middleware;

                    if (is_callable($handler)) {
                        return $handler($request, $response, $next);
                    }

                    if (is_string($handler) && class_exists($handler)) {
                        $instance = new $handler();
                        return $instance->handle($request, $response, $next);
                    }

                    return $next($request, $response);
                };
            },
            $destination
        );
    }
}

/**
 * Authentication Middleware
 */
class AuthMiddleware
{
    public function handle(Request $request, Response $response, Closure $next)
    {
        $session = new Session();
        $auth = new Auth(null, $session);

        if (!$auth->check()) {
            $response->redirect('/login');
            exit;
        }

        return $next($request, $response);
    }
}

/**
 * Guest Middleware
 */
class GuestMiddleware
{
    public function handle(Request $request, Response $response, Closure $next)
    {
        $session = new Session();
        $auth = new Auth(null, $session);

        if ($auth->check()) {
            $response->redirect('/dashboard');
            exit;
        }

        return $next($request, $response);
    }
}

/**
 * CSRF Protection Middleware
 */
class CsrfMiddleware
{
    public function handle(Request $request, Response $response, Closure $next)
    {
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE')) {
            $session = new Session();
            $token = $request->input('csrf_token');

            if (!$session->validateCsrfToken($token)) {
                $response->setStatusCode(419);
                $response->json(['error' => 'CSRF token mismatch'], 419);
                exit;
            }
        }

        return $next($request, $response);
    }
}

/**
 * CORS Middleware
 */
class CorsMiddleware
{
    public function handle(Request $request, Response $response, Closure $next)
    {
        $allowedOrigins = App::config('CORS_ALLOWED_ORIGINS', '*');
        $allowedMethods = App::config('CORS_ALLOWED_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
        $allowedHeaders = App::config('CORS_ALLOWED_HEADERS', 'Content-Type, Authorization');

        $response->setHeader('Access-Control-Allow-Origin', $allowedOrigins);
        $response->setHeader('Access-Control-Allow-Methods', $allowedMethods);
        $response->setHeader('Access-Control-Allow-Headers', $allowedHeaders);
        $response->setHeader('Access-Control-Max-Age', '86400');

        if ($request->isMethod('OPTIONS')) {
            $response->setStatusCode(200);
            exit;
        }

        return $next($request, $response);
    }
}

/**
 * Rate Limiting Middleware
 */
class ThrottleMiddleware
{
    public function handle(Request $request, Response $response, Closure $next)
    {
        $cache = new Cache('file');
        $key = 'throttle_' . $request->ip();
        $limit = App::config('RATE_LIMIT', 60);
        $decay = App::config('RATE_LIMIT_DECAY', 60);

        $attempts = (int) $cache->get($key, 0);

        if ($attempts >= $limit) {
            $response->setStatusCode(429);
            $response->json(['error' => 'Too many requests'], 429);
            exit;
        }

        $cache->put($key, $attempts + 1, $decay);

        return $next($request, $response);
    }
}

/**
 * Admin Middleware
 */
class AdminMiddleware
{
    public function handle(Request $request, Response $response, Closure $next)
    {
        $session = new Session();
        $auth = new Auth(null, $session);

        if (!$auth->check()) {
            $response->redirect('/login');
            exit;
        }

        $user = $auth->user();
        if (!isset($user['is_admin']) || !$user['is_admin']) {
            $response->setStatusCode(403);
            $response->json(['error' => 'Forbidden'], 403);
            exit;
        }

        return $next($request, $response);
    }
}

/**
 * JSON Middleware
 */
class JsonMiddleware
{
    public function handle(Request $request, Response $response, Closure $next)
    {
        $response->setHeader('Content-Type', 'application/json');
        return $next($request, $response);
    }
}

/**
 * Logging Middleware
 */
class LogMiddleware
{
    public function handle(Request $request, Response $response, Closure $next)
    {
        $logger = new Logger('logs/requests.log');
        $logger->logInfo('Request', [
            'method' => $request->getMethod(),
            'path' => $request->getPath(),
            'ip' => $request->ip()
        ]);

        return $next($request, $response);
    }
}

