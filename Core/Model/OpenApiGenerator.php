<?php

/**
 * OpenAPI Specification Generator
 *
 * This file contains the OpenApiGenerator class which builds an OpenAPI 3.0
 * document directly from a Router's registered routes.
 *
 * PHP version 8.1
 *
 * @category  Documentation
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
 * OpenAPI Generator Class
 *
 * Introspects the routes registered on a Router instance and produces an
 * OpenAPI 3.0.3 document (as a plain array, ready for json_encode()) without
 * requiring docblock annotations. Path parameters ({id}) are detected
 * directly from the route pattern, operation ids and tags are derived from
 * the controller action, and per-route middleware becomes an
 * `x-middleware` extension so the pipeline stays visible in the spec.
 *
 * Example:
 * ```php
 * $generator = new OpenApiGenerator(['title' => 'My API', 'version' => '1.0.0']);
 * $spec = $generator->generate($router);
 * file_put_contents('openapi.json', json_encode($spec, JSON_PRETTY_PRINT));
 * ```
 *
 * @category  Documentation
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @version   1.0.0
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */
class OpenApiGenerator
{
    /**
     * API-level metadata (title, version, description).
     *
     * @var array<string,mixed>
     */
    private array $info;

    /**
     * @param array<string,mixed> $info Document info block. Supported keys:
     *                                  "title", "version", "description".
     */
    public function __construct(array $info = [])
    {
        $this->info = array_merge([
            'title' => 'API Documentation',
            'version' => '1.0.0',
            'description' => 'Generated from registered routes.',
        ], $info);
    }

    /**
     * Build a full OpenAPI 3.0.3 document from a Router's registered routes.
     *
     * @param Router $router A router with routes already registered on it.
     *
     * @return array<string,mixed> OpenAPI document as a plain array.
     *
     * @since 1.0.0
     */
    public function generate(Router $router): array
    {
        $paths = [];

        foreach ($router->getRoutes() as $route) {
            $path = $route->getPath();
            $paths[$path] ??= [];
            $paths[$path][strtolower($route->getMethod())] = $this->buildOperation($route);
        }

        ksort($paths);

        return [
            'openapi' => '3.0.3',
            'info' => $this->info,
            'paths' => $paths,
        ];
    }

    /**
     * Build the OpenAPI Operation Object for a single route.
     *
     * @param Route $route The route to describe.
     *
     * @return array<string,mixed>
     */
    private function buildOperation(Route $route): array
    {
        $actionLabel = $this->describeAction($route->getAction());

        $operation = [
            'summary' => $route->getName() ?? $actionLabel,
            'operationId' => $route->getName() ?? $this->operationId($route),
            'tags' => [$this->deriveTag($route->getPath())],
            'parameters' => $this->pathParameters($route->getPath()),
            'responses' => [
                '200' => ['description' => 'Successful response'],
            ],
        ];

        if (!empty($route->getMiddleware())) {
            $operation['x-middleware'] = array_values(array_map(
                fn ($middleware) => is_string($middleware) ? $middleware : gettype($middleware),
                $route->getMiddleware()
            ));
        }

        return $operation;
    }

    /**
     * Extract {param} placeholders from a route pattern as OpenAPI path
     * parameter objects.
     *
     * @param string $path Route pattern.
     *
     * @return array<int,array<string,mixed>>
     */
    private function pathParameters(string $path): array
    {
        if (!preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $path, $matches)) {
            return [];
        }

        return array_map(static fn (string $name) => [
            'name' => $name,
            'in' => 'path',
            'required' => true,
            'schema' => ['type' => 'string'],
        ], $matches[1]);
    }

    /**
     * Derive a human-readable label for a route action.
     *
     * @param callable|array<int,mixed>|string $action Route action.
     *
     * @return string
     */
    private function describeAction($action): string
    {
        if (is_string($action)) {
            return $action;
        }

        if (is_array($action)) {
            $controller = is_string($action[0]) ? $action[0] : get_class($action[0]);
            return $controller . '::' . $action[1];
        }

        return 'Closure';
    }

    /**
     * Build a stable operationId from the route's method and action.
     *
     * @param Route $route Route to derive an id for.
     *
     * @return string
     */
    private function operationId(Route $route): string
    {
        $action = $this->describeAction($route->getAction());
        $slug = preg_replace('/[^a-zA-Z0-9]+/', '_', $action);

        return strtolower($route->getMethod()) . '_' . trim((string) $slug, '_');
    }

    /**
     * Derive a grouping tag from the first meaningful path segment, skipping
     * a leading "api" segment and version prefixes (e.g. "v1") so resources
     * group together regardless of API namespace or version.
     *
     * @param string $path Route pattern.
     *
     * @return string
     */
    private function deriveTag(string $path): string
    {
        $segments = array_values(array_filter(explode('/', $path)));

        foreach ($segments as $segment) {
            if (strcasecmp($segment, 'api') === 0 || preg_match('/^v\d+$/i', $segment)) {
                continue;
            }

            return $segment;
        }

        return 'default';
    }
}
