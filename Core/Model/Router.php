<?php

/**
 * URL Router and Dispatcher
 *
 * This file contains the Router class which manages URL routing, pattern matching,
 * and dispatching requests to appropriate controllers or callbacks.
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
 * Manages URL routing, request dispatching, and route parameter extraction.
 * Supports both static and dynamic routes with parameter placeholders.
 *
 * Features:
 * - HTTP method-based routing (GET, POST, PUT, DELETE, etc.)
 * - Static route matching (/home, /about)
 * - Dynamic routes with parameters (/user/{id}, /post/{slug})
 * - Multiple callback formats (closures, controller@method, [Class, 'method'])
 * - Automatic parameter extraction and injection
 * - Request and Response object injection
 * - 404 handling for unmatched routes
 * - Performance-optimized (exact match checked first)
 *
 * Route registration examples:
 * ```php
 * $router = new Router($request, $response);
 *
 * // Closure callback
 * $router->get('/home', function($req, $res) {
 *     return $res->html('<h1>Home</h1>');
 * });
 *
 * // Controller string format
 * $router->post('/user/create', 'App\Controller\UserController@create');
 *
 * // Controller array format
 * $router->get('/user/{id}', [UserController::class, 'show']);
 * ```
 *
 * @category  Routing
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @version   1.0.0
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */
class Router
{
    /**
     * The current HTTP request object
     *
     * @var Request
     */
    private Request $request;

    /**
     * The HTTP response object
     *
     * @var Response
     */
    private Response $response;

    /**
     * Registered routes organized by HTTP method
     *
     * Structure: ['GET' => ['/path' => callback], 'POST' => [...]]
     *
     * @var array<string,array<string,callable|array<int,mixed>|string>>
     */
    private array $routes = [];

    /**
     * Initialize the router with request and response objects
     *
     * Creates a new router instance that will use the provided Request and
     * Response objects for dispatching and handling routes.
     *
     * @param Request  $request  The HTTP request object containing request data
     * @param Response $response The HTTP response object for sending responses
     *
     * @since 1.0.0
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Register a GET route
     *
     * Registers a route that responds to HTTP GET requests. Supports both static
     * paths and dynamic paths with parameter placeholders.
     *
     * Callback formats:
     * - Closure: function($req, $res) { }
     * - String: 'Namespace\Controller@method'
     * - Array: [ControllerClass::class, 'methodName']
     *
     * Example:
     * ```php
     * $router->get('/users', function($req, $res) {
     *     return $res->json(['users' => User::all()]);
     * });
     *
     * $router->get('/user/{id}', 'UserController@show');
     * ```
     *
     * @param string                      $path     The URL path, may contain {param}
     *                                               placeholders for dynamic segments
     * @param callable|array<int,mixed>|string $callback The function, controller, or
     *                                               method to execute when matched
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function get(string $path, callable|array|string $callback): void
    {
        $this->routes['GET'][$path] = $callback;
    }

    /**
     * Register a POST route
     *
     * Registers a route that responds to HTTP POST requests. Typically used for
     * form submissions, data creation, and non-idempotent operations.
     *
     * Callback formats:
     * - Closure: function($req, $res) { }
     * - String: 'Namespace\Controller@method'
     * - Array: [ControllerClass::class, 'methodName']
     *
     * Example:
     * ```php
     * $router->post('/user/create', function($req, $res) {
     *     $name = $req->input('name');
     *     // Create user logic
     *     return $res->json(['success' => true], 201);
     * });
     *
     * $router->post('/login', 'AuthController@login');
     * ```
     *
     * @param string                      $path     The URL path to match
     * @param callable|array<int,mixed>|string $callback The handler to execute
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function post(string $path, callable|array|string $callback): void
    {
        $this->routes['POST'][$path] = $callback;
    }

    /**
     * Resolve and dispatch the current request to matching route
     *
     * Performs a two-stage route matching process:
     * 1. Fast exact path match (for performance)
     * 2. Regex pattern matching for dynamic routes
     *
     * Extracts route parameters from dynamic segments and passes them to the
     * callback along with Request and Response objects.
     *
     * Returns 404 response if no matching route is found.
     *
     * Execution flow:
     * 1. Get current HTTP method and path from request
     * 2. Check exact match in routes array
     * 3. If no exact match, iterate through dynamic routes
     * 4. Extract parameters from matched dynamic route
     * 5. Execute callback with parameters
     * 6. Return 404 if no match found
     *
     * Example parameter extraction:
     * Route: /user/{id}/post/{slug}
     * URL: /user/123/post/hello-world
     * Parameters: ['id' => '123', 'slug' => 'hello-world']
     *
     * @return mixed The return value from the executed callback
     *
     * @since 1.0.0
     *
     * @see executeCallback() For callback execution details
     * @see convertRouteToRegex() For pattern matching logic
     */
    public function resolve()
    {
        try {
            $method = $this->request->getMethod();
            $path = $this->request->getPath();

            // 1. Check for exact match first (Performance optimization)
            $callback = $this->routes[$method][$path] ?? false;

            if ($callback) {
                return $this->executeCallback($callback);
            }

            // 2. Check for dynamic routes (e.g., /user/{id})
            foreach ($this->routes[$method] ?? [] as $route => $action) {
                $pattern = $this->convertRouteToRegex($route);

                if (preg_match($pattern, $path, $matches) === false) {
                    throw new Exception('Invalid regex pattern for route: ' . $route);
                }
                
                if (preg_match($pattern, $path, $matches)) {
                    // Remove the full match, keeping only named parameters
                    array_shift($matches);
                    // Filter out numeric keys if using named groups
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                    return $this->executeCallback($action, $params);
                }
            }

            // 3. No route found - return 404
            $this->response->setStatusCode(404);
            return "404 - Not Found";
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
     * Convert route path with placeholders to regex pattern
     *
     * Transforms a route definition like '/user/{id}/post/{slug}' into a
     * regular expression pattern that can match URLs and capture parameters.
     *
     * Conversion rules:
     * - Forward slashes are escaped
     * - {param} becomes named capture group: (?P<param>[a-zA-Z0-9_-]+)
     * - Anchored with ^ and $ for exact matching
     *
     * Examples:
     * - /user/{id} → /^\/user\/(?P<id>[a-zA-Z0-9_-]+)$/
     * - /post/{year}/{month} → /^\/post\/(?P<year>[...])/(?P<month>[...])$/
     *
     * @param string $route The route path with {param} placeholders
     *
     * @return string The regex pattern for matching
     *
     * @since 1.0.0
     */
    private function convertRouteToRegex(string $route): string
    {
        // Escape forward slashes
        $route = preg_replace('/\//', '\\/', $route);
        // Convert {param} to named capture group (?P<param>[a-zA-Z0-9_-]+)
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $route);
        // Add start and end delimiters
        return '/^' . $route . '$/';
    }

    /**
     * Execute the route callback with dependency injection
     *
     * Handles multiple callback formats and performs automatic instantiation
     * and parameter injection. Passes Request, Response, and route parameters
     * to the callback.
     *
     * Supported callback formats:
     * 1. Closure: function($req, $res, ...$params) { }
     * 2. String: 'Namespace\Controller@method' (parsed and instantiated)
     * 3. Array: [ControllerClass::class, 'method'] (instantiated if needed)
     * 4. Array: [new Controller(), 'method'] (used as-is)
     *
     * Parameters injected in order:
     * 1. Request object
     * 2. Response object
     * 3. Route parameters (as individual arguments)
     *
     * Example callback signature:
     * ```php
     * function($request, $response, $id, $slug) {
     *     // $id and $slug from route parameters
     * }
     * ```
     *
     * @param callable|array<int,mixed>|string $callback The callback to execute
     * @param array<string,string>             $params   Route parameters extracted
     *                                                    from URL
     *
     * @return mixed The return value from the executed callback
     *
     * @since 1.0.0
     */
    private function executeCallback($callback, array $params = [])
    {
        try {
            // Handle Controller String format: "Namespace\Controller@method"
            if (is_string($callback)) {
                $parts = explode('@', $callback);
                if (count($parts) === 2) {
                    $controllerClass = $parts[0];
                    $method = $parts[1];
                    
                    if (!class_exists($controllerClass)) {
                        throw new Exception("Controller class not found: {$controllerClass}");
                    }
                    
                    $callback = [new $controllerClass(), $method];
                    
                    if (!method_exists($callback[0], $method)) {
                        throw new Exception("Method {$method} not found in {$controllerClass}");
                    }
                } else {
                    throw new Exception('Invalid callback format. Expected: Controller@method');
                }
            }

            // Handle Array format: [Controller::class, 'method']
            if (is_array($callback)) {
                // Instantiate the controller if it's not an object yet
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

            // Execute the function/method with injected dependencies and parameters
            return call_user_func_array($callback, [$this->request, $this->response, ...$params]);
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

