<?php

/**
 * URL Router and Dispatcher
 *
 * This file contains the Router class which manages URL routing, pattern matching,
 * middleware, route groups, and dispatching requests to controllers or callbacks.
 *
 * PHP version 8.1
 *
 * @category  Routing
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */

declare(strict_types=1);

namespace Core\Model;

use Core\Model\Error;
use Exception;

/**
 * Router Class
 *
 * Manages URL routing, request dispatching, route parameter extraction,
 * route groups (shared prefix/middleware), and per-route middleware.
 *
 * Features:
 * - HTTP method routing: get, post, put, patch, delete, options, any, match
 * - Static and dynamic routes with {param} placeholders
 * - Route groups with shared prefix and middleware
 * - Per-route middleware via a fluent Route handle
 * - Named routes
 * - Multiple callback formats (closures, "Controller@method", [Class, 'method'])
 * - 404 handling for unmatched routes
 *
 * Example:
 * ```php
 * $router = new Router($request, $response);
 *
 * $router->get('/home', fn($req, $res) => $res->html('<h1>Home</h1>'));
 *
 * $router->get('/admin', [AdminController::class, 'index'])
 *     ->middleware(['auth', 'admin']);
 *
 * $router->group(['prefix' => '/api/v1', 'middleware' => ['api']], function ($r) {
 *     $r->get('/users', [UserController::class, 'index']);
 *     $r->post('/users', [UserController::class, 'store']);
 * });
 *
 * $router->resolve();
 * ```
 *
 * @category  Routing
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   2.0.0
 * @since     1.0.0
 */
class Router
{
    /**
     * The current HTTP request object.
     *
     * @var Request
     */
    private Request $request;

    /**
     * The HTTP response object.
     *
     * @var Response
     */
    private Response $response;

    /**
     * All registered routes.
     *
     * @var array<int,Route>
     */
    private array $routes = [];

    /**
     * Stack of active group attributes (prefix, middleware) during registration.
     *
     * @var array<int,array{prefix:string,middleware:array<int,mixed>}>
     */
    private array $groupStack = [];

    /**
     * @param Request  $request  The HTTP request object.
     * @param Response $response The HTTP response object.
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Register a GET route.
     *
     * @param string                           $path     URI pattern.
     * @param callable|array<int,mixed>|string $callback Route action.
     *
     * @return Route
     */
    public function get(string $path, callable|array|string $callback): Route
    {
        return $this->addRoute('GET', $path, $callback);
    }

    /**
     * Register a POST route.
     *
     * @param string                           $path     URI pattern.
     * @param callable|array<int,mixed>|string $callback Route action.
     *
     * @return Route
     */
    public function post(string $path, callable|array|string $callback): Route
    {
        return $this->addRoute('POST', $path, $callback);
    }

    /**
     * Register a PUT route.
     *
     * @param string                           $path     URI pattern.
     * @param callable|array<int,mixed>|string $callback Route action.
     *
     * @return Route
     */
    public function put(string $path, callable|array|string $callback): Route
    {
        return $this->addRoute('PUT', $path, $callback);
    }

    /**
     * Register a PATCH route.
     *
     * @param string                           $path     URI pattern.
     * @param callable|array<int,mixed>|string $callback Route action.
     *
     * @return Route
     */
    public function patch(string $path, callable|array|string $callback): Route
    {
        return $this->addRoute('PATCH', $path, $callback);
    }

    /**
     * Register a DELETE route.
     *
     * @param string                           $path     URI pattern.
     * @param callable|array<int,mixed>|string $callback Route action.
     *
     * @return Route
     */
    public function delete(string $path, callable|array|string $callback): Route
    {
        return $this->addRoute('DELETE', $path, $callback);
    }

    /**
     * Register an OPTIONS route.
     *
     * @param string                           $path     URI pattern.
     * @param callable|array<int,mixed>|string $callback Route action.
     *
     * @return Route
     */
    public function options(string $path, callable|array|string $callback): Route
    {
        return $this->addRoute('OPTIONS', $path, $callback);
    }

    /**
     * Register a route responding to several HTTP methods.
     *
     * @param array<int,string>                $methods  HTTP methods.
     * @param string                           $path     URI pattern.
     * @param callable|array<int,mixed>|string $callback Route action.
     *
     * @return array<int,Route>
     */
    public function match(array $methods, string $path, callable|array|string $callback): array
    {
        $routes = [];
        foreach ($methods as $method) {
            $routes[] = $this->addRoute(strtoupper($method), $path, $callback);
        }

        return $routes;
    }

    /**
     * Register a route responding to all common HTTP methods.
     *
     * @param string                           $path     URI pattern.
     * @param callable|array<int,mixed>|string $callback Route action.
     *
     * @return array<int,Route>
     */
    public function any(string $path, callable|array|string $callback): array
    {
        return $this->match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $path, $callback);
    }

    /**
     * Register the seven conventional RESTful routes for a resource.
     *
     * Generates:
     * - GET    /{name}            -> index
     * - GET    /{name}/create     -> create
     * - POST   /{name}            -> store
     * - GET    /{name}/{id}       -> show
     * - GET    /{name}/{id}/edit  -> edit
     * - PUT    /{name}/{id}       -> update
     * - DELETE /{name}/{id}       -> destroy
     *
     * The static "create" route is registered before the dynamic "{id}" route
     * so it matches first.
     *
     * @param string $name       Resource path segment (e.g. "photos").
     * @param string $controller Controller class name.
     *
     * @return array<int,Route>
     */
    public function resource(string $name, string $controller): array
    {
        $name = trim($name, '/');

        return [
            $this->get("/$name", [$controller, 'index']),
            $this->get("/$name/create", [$controller, 'create']),
            $this->post("/$name", [$controller, 'store']),
            $this->get("/$name/{id}", [$controller, 'show']),
            $this->get("/$name/{id}/edit", [$controller, 'edit']),
            $this->put("/$name/{id}", [$controller, 'update']),
            $this->delete("/$name/{id}", [$controller, 'destroy']),
        ];
    }

    /**
     * Define a group of routes sharing a prefix and/or middleware.
     *
     * @param array{prefix?:string,middleware?:array<int,mixed>|string} $attributes Group attributes.
     * @param callable                                                  $callback   Receives this router.
     *
     * @return void
     */
    public function group(array $attributes, callable $callback): void
    {
        $prefix = $attributes['prefix'] ?? '';
        $middleware = $attributes['middleware'] ?? [];
        $middleware = is_array($middleware) ? $middleware : [$middleware];

        $this->groupStack[] = [
            'prefix' => $prefix,
            'middleware' => $middleware,
        ];

        $callback($this);

        array_pop($this->groupStack);
    }

    /**
     * Create, register, and return a Route, applying any active group context.
     *
     * @param string                           $method   HTTP method.
     * @param string                           $path     URI pattern.
     * @param callable|array<int,mixed>|string $callback Route action.
     *
     * @return Route
     */
    private function addRoute(string $method, string $path, $callback): Route
    {
        $prefix = '';
        $middleware = [];

        foreach ($this->groupStack as $group) {
            $prefix .= $group['prefix'];
            $middleware = array_merge($middleware, $group['middleware']);
        }

        $fullPath = $this->normalizePath($prefix . $path);

        $route = new Route($method, $fullPath, $callback);

        if (!empty($middleware)) {
            $route->middleware($middleware);
        }

        $this->routes[] = $route;

        return $route;
    }

    /**
     * Normalize a path: ensure a single leading slash and no trailing slash
     * (except the root "/").
     *
     * @param string $path Raw path.
     *
     * @return string
     */
    private function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    /**
     * All registered routes.
     *
     * @return array<int,Route>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Resolve and dispatch the current request to the matching route.
     *
     * @return mixed The value returned by the executed callback, or a 404 marker.
     *
     * @since 1.0.0
     */
    public function resolve()
    {
        try {
            $method = $this->request->getMethod();
            $path = $this->normalizePath($this->request->getPath());

            foreach ($this->routes as $route) {
                if ($route->getMethod() !== $method) {
                    continue;
                }

                $params = $this->matchPath($route->getPath(), $path);

                if ($params === null) {
                    continue;
                }

                return $this->runRoute($route, $params);
            }

            // No route found - return 404
            $this->response->setStatusCode(404);
            return '404 - Not Found';
        } catch (Exception $e) {
            $error = new Error();
            $error->terminateWithError(
                'ROUTING_RESOLVE_FAILED',
                'Route resolution failed: ' . $e->getMessage(),
                Error::SEVERITY_ERROR,
                ['method' => $method ?? 'unknown', 'path' => $path ?? 'unknown']
            );
        }
    }

    /**
     * Match a request path against a route pattern, returning extracted
     * parameters, or null when there is no match.
     *
     * @param string $pattern Route pattern (may contain {param}).
     * @param string $path    Request path.
     *
     * @return array<string,string>|null
     */
    private function matchPath(string $pattern, string $path): ?array
    {
        if ($pattern === $path) {
            return [];
        }

        // Only bother with regex when the pattern has parameters.
        if (!str_contains($pattern, '{')) {
            return null;
        }

        $regex = $this->convertRouteToRegex($pattern);

        if (preg_match($regex, $path, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return null;
    }

    /**
     * Run a matched route, wrapping it in its middleware pipeline when present.
     *
     * @param Route                 $route  Matched route.
     * @param array<string,string>  $params Extracted route parameters.
     *
     * @return mixed
     */
    private function runRoute(Route $route, array $params)
    {
        $destination = fn () => $this->executeCallback($route->getAction(), $params);

        if (empty($route->getMiddleware())) {
            return $destination();
        }

        $pipeline = new Middleware($this->request, $this->response);

        return $pipeline->handle(
            $route->getMiddleware(),
            fn ($request, $response) => $destination()
        );
    }

    /**
     * Convert a route pattern with {param} placeholders to a regex.
     *
     * @param string $route Route pattern.
     *
     * @return string
     */
    private function convertRouteToRegex(string $route): string
    {
        $route = preg_replace('/\//', '\\/', $route);
        $route = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $route);

        return '/^' . $route . '$/';
    }

    /**
     * Execute the route callback with the request, response, and route params.
     *
     * @param callable|array<int,mixed>|string $callback Route action.
     * @param array<string,string>             $params   Route parameters.
     *
     * @return mixed
     */
    private function executeCallback($callback, array $params = [])
    {
        try {
            // "Namespace\Controller@method"
            if (is_string($callback)) {
                $parts = explode('@', $callback);
                if (count($parts) === 2) {
                    [$controllerClass, $methodName] = $parts;

                    if (!class_exists($controllerClass)) {
                        throw new Exception("Controller class not found: {$controllerClass}");
                    }

                    $callback = [new $controllerClass(), $methodName];

                    if (!method_exists($callback[0], $methodName)) {
                        throw new Exception("Method {$methodName} not found in {$controllerClass}");
                    }
                } else {
                    throw new Exception('Invalid callback format. Expected: Controller@method');
                }
            }

            // [Controller::class, 'method']
            if (is_array($callback)) {
                if (is_string($callback[0])) {
                    if (!class_exists($callback[0])) {
                        throw new Exception("Controller class not found: {$callback[0]}");
                    }
                    $callback[0] = new $callback[0]();
                }

                if (!method_exists($callback[0], $callback[1])) {
                    $className = get_class($callback[0]);
                    throw new Exception("Method {$callback[1]} not found in {$className}");
                }
            }

            if (!is_callable($callback)) {
                throw new Exception('Callback is not callable');
            }

            return call_user_func_array($callback, [$this->request, $this->response, ...array_values($params)]);
        } catch (Exception $e) {
            $error = new Error();
            $error->terminateWithError(
                'CALLBACK_EXECUTION_FAILED',
                'Callback execution failed: ' . $e->getMessage(),
                Error::SEVERITY_ERROR,
                ['callback_type' => gettype($callback)]
            );
        }
    }
}
