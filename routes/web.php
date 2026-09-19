<?php

/**
 * Web routes: login/register against the resources/views/auth scaffolding.
 *
 * Registered against the shared $router/$request/$response created in
 * index.php — this file only adds routes, it doesn't build its own Router.
 *
 * PHP version 8.1
 */

declare(strict_types=1);

use Core\Model\Router;

/** @var Router $router */

$router->group(['middleware' => ['csrf']], function (Router $router) {
    $router->get('/login', 'System\Controller\AuthController@showLogin')->name('login');
    $router->post('/login', 'System\Controller\AuthController@login');
    $router->get('/register', 'System\Controller\AuthController@showRegister')->name('register');
    $router->post('/register', 'System\Controller\AuthController@register');
});

$router->post('/logout', 'System\Controller\AuthController@logout')
    ->middleware('csrf')
    ->name('logout');
