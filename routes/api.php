<?php

/**
 * Example API Routes
 *
 * Demonstrates two patterns that are otherwise easy to miss in the
 * framework: versioned route groups (prefix-based) and rate limiting via
 * the built-in "api" middleware group (throttle + json + cors + log).
 *
 * This file returns a configured Router so it can be fed straight into the
 * OpenAPI generator:
 *
 *   php console docs:generate --routes=routes/api.php --output=public/openapi.json
 *
 * It is a template, not framework glue — copy the patterns into your own
 * bootstrap and point them at your real controllers.
 *
 * PHP version 8.1
 */

declare(strict_types=1);

use Core\Model\Request;
use Core\Model\Response;
use Core\Model\Router;

$router = new Router(new Request(), new Response());

// Versioning: group every route under /api/v1 so a future /api/v2 can be
// introduced alongside it without breaking existing clients.
$router->group(['prefix' => '/api/v1', 'middleware' => ['api']], function (Router $router) {
    $router->get('/users', 'System\Controller\UserController@index')->name('users.index');
    $router->get('/users/{id}', 'System\Controller\UserController@show')->name('users.show');
    $router->post('/users', 'System\Controller\UserController@store')->name('users.store');
    $router->put('/users/{id}', 'System\Controller\UserController@update')->name('users.update');
    $router->delete('/users/{id}', 'System\Controller\UserController@destroy')->name('users.destroy');
});

// Rate limiting: the "api" group already includes "throttle" (see
// Core\Model\Middleware::registerBuiltInMiddleware). Expensive endpoints can
// stack an additional "throttle" on top for a tighter, route-specific limit.
$router->group(['prefix' => '/api/v1', 'middleware' => ['api']], function (Router $router) {
    $router->post('/reports/export', 'System\Controller\ReportController@export')
        ->middleware('throttle')
        ->name('reports.export');
});

return $router;
