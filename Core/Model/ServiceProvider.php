<?php

/**
 * Service Provider Base Class
 *
 * Provides a structured way to register bindings into the Container and to
 * run boot-time logic once all providers have been registered.
 *
 * PHP version 8.1
 *
 * @category  Support
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
 * ServiceProvider Class
 *
 * Extend this class to group related container bindings. The two-phase
 * register()/boot() lifecycle mirrors common framework conventions:
 *
 * - register(): bind services into the container. Do NOT resolve services
 *   here, since other providers may not have registered theirs yet.
 * - boot(): runs after every provider has been registered; safe to resolve
 *   and wire services together.
 *
 * Example:
 * ```php
 * class DatabaseServiceProvider extends ServiceProvider
 * {
 *     public function register(): void
 *     {
 *         $this->container->singleton(Database::class, function ($c) {
 *             return new Database($c->get(Logger::class), $c->get(Error::class));
 *         });
 *     }
 * }
 * ```
 *
 * @category  Support
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @version   1.0.0
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */
abstract class ServiceProvider
{
    /**
     * The container instance provided at construction.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * @param Container $container The application container.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register bindings into the container.
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     * Perform post-registration bootstrapping.
     *
     * Optional; override when a provider needs to run logic after all
     * providers have registered their bindings.
     *
     * @return void
     */
    public function boot(): void
    {
    }
}
