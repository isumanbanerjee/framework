<?php
require_once __DIR__ . '/resources/vendor/autoload.php';

use Core\Model\Request;
use Core\Model\Response;
use Core\Model\Router;

$request = new Request();
$response = new Response();
$router = new Router($request, $response);

$router->get('/', function ($req, $res) {
    $res->html('<h1>Welcome to OmnioPHP!</h1>');
});

$router->get('/api/status', function ($req, $res) {
    $res->json(['status' => 'ok', 'framework' => 'OmnioPHP']);
});

$router->resolve();
