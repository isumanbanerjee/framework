<?php

/**
 * RESTful Resource Controller
 *
 * A base controller providing the seven conventional REST actions. Subclasses
 * override the actions they need; unimplemented actions respond with 501.
 *
 * PHP version 8.1
 *
 * @category  Controller
 * @package   Core\Controller
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */

declare(strict_types=1);

namespace Core\Controller;

use Core\Model\Request;
use Core\Model\Response;

/**
 * ResourceController Class
 *
 * Pair with Router::resource() to expose a resource with minimal wiring:
 * ```php
 * $router->resource('photos', PhotoController::class);
 * ```
 *
 * @category  Controller
 * @package   Core\Controller
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
abstract class ResourceController
{
    /**
     * Display a listing of the resource. GET /resource
     *
     * @param Request  $request  Current request.
     * @param Response $response Response handler.
     *
     * @return mixed
     */
    public function index(Request $request, Response $response)
    {
        return $this->notImplemented($response, 'index');
    }

    /**
     * Show the form for creating a new resource. GET /resource/create
     *
     * @param Request  $request  Current request.
     * @param Response $response Response handler.
     *
     * @return mixed
     */
    public function create(Request $request, Response $response)
    {
        return $this->notImplemented($response, 'create');
    }

    /**
     * Store a newly created resource. POST /resource
     *
     * @param Request  $request  Current request.
     * @param Response $response Response handler.
     *
     * @return mixed
     */
    public function store(Request $request, Response $response)
    {
        return $this->notImplemented($response, 'store');
    }

    /**
     * Display the specified resource. GET /resource/{id}
     *
     * @param Request  $request  Current request.
     * @param Response $response Response handler.
     * @param string   $id       Resource identifier.
     *
     * @return mixed
     */
    public function show(Request $request, Response $response, string $id)
    {
        return $this->notImplemented($response, 'show');
    }

    /**
     * Show the form for editing the resource. GET /resource/{id}/edit
     *
     * @param Request  $request  Current request.
     * @param Response $response Response handler.
     * @param string   $id       Resource identifier.
     *
     * @return mixed
     */
    public function edit(Request $request, Response $response, string $id)
    {
        return $this->notImplemented($response, 'edit');
    }

    /**
     * Update the specified resource. PUT /resource/{id}
     *
     * @param Request  $request  Current request.
     * @param Response $response Response handler.
     * @param string   $id       Resource identifier.
     *
     * @return mixed
     */
    public function update(Request $request, Response $response, string $id)
    {
        return $this->notImplemented($response, 'update');
    }

    /**
     * Remove the specified resource. DELETE /resource/{id}
     *
     * @param Request  $request  Current request.
     * @param Response $response Response handler.
     * @param string   $id       Resource identifier.
     *
     * @return mixed
     */
    public function destroy(Request $request, Response $response, string $id)
    {
        return $this->notImplemented($response, 'destroy');
    }

    /**
     * Default response for an action a subclass has not overridden.
     *
     * @param Response $response Response handler.
     * @param string   $action   Action name.
     *
     * @return array<string,string>
     */
    protected function notImplemented(Response $response, string $action): array
    {
        $response->setStatusCode(501);

        return ['error' => "Action '{$action}' is not implemented."];
    }
}
