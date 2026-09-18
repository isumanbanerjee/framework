<?php

/**
 * Route Definition
 *
 * A fluent value object representing a single registered route. Returned by
 * the Router's verb methods so per-route middleware and a name can be
 * attached via method chaining.
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

/**
 * Route Class
 *
 * Holds the HTTP method, URI pattern, action, attached middleware, and
 * optional name for one route.
 *
 * Example:
 * ```php
 * $router->get('/admin', [AdminController::class, 'index'])
 *     ->middleware(['auth', 'admin'])
 *     ->name('admin.dashboard');
 * ```
 *
 * @category  Routing
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
class Route
{
    /**
     * HTTP method for this route (GET, POST, ...).
     *
     * @var string
     */
    private string $method;

    /**
     * URI pattern, may contain {param} placeholders.
     *
     * @var string
     */
    private string $path;

    /**
     * The route action (closure, "Controller@method", or [Class, 'method']).
     *
     * @var callable|array<int,mixed>|string
     */
    private $action;

    /**
     * Middleware names/aliases/groups/closures attached to this route.
     *
     * @var array<int,mixed>
     */
    private array $middleware = [];

    /**
     * Optional route name.
     *
     * @var string|null
     */
    private ?string $name = null;

    /**
     * @param string                           $method HTTP method.
     * @param string                           $path   URI pattern.
     * @param callable|array<int,mixed>|string $action Route action.
     */
    public function __construct(string $method, string $path, $action)
    {
        $this->method = $method;
        $this->path = $path;
        $this->action = $action;
    }

    /**
     * Attach middleware to this route.
     *
     * @param array<int,mixed>|string $middleware One or more middleware.
     *
     * @return self
     */
    public function middleware($middleware): self
    {
        $middleware = is_array($middleware) ? $middleware : [$middleware];
        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }

    /**
     * Assign a name to this route.
     *
     * @param string $name Route name.
     *
     * @return self
     */
    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string HTTP method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string URI pattern.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return callable|array<int,mixed>|string Route action.
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return array<int,mixed> Attached middleware.
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * @return string|null Route name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}
