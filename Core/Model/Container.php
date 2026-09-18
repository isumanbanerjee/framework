<?php

/**
 * Dependency Injection Container
 *
 * A lightweight PSR-11 compliant service container supporting explicit
 * bindings, singletons, pre-built instances, and automatic constructor
 * injection (autowiring) via reflection.
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

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use Throwable;

/**
 * Container Class
 *
 * Resolves dependencies on demand. Identifiers may be arbitrary strings
 * (bound to factory closures) or fully-qualified class names (autowired
 * from their constructor signature).
 *
 * Resolution order for get($id):
 * 1. A previously resolved singleton or registered instance is returned.
 * 2. A singleton factory is invoked once and its result cached.
 * 3. A transient bind() factory is invoked, returning a fresh value.
 * 4. Otherwise the id is treated as a class name and autowired.
 *
 * Example usage:
 * ```php
 * $container = new Container();
 *
 * // Explicit factory (fresh value each call)
 * $container->bind('now', fn () => new DateTimeImmutable());
 *
 * // Shared singleton
 * $container->singleton(Logger::class, fn () => new Logger());
 *
 * // Pre-built instance
 * $container->instance('config', $configArray);
 *
 * // Autowired (no registration needed)
 * $service = $container->get(SomeService::class);
 * ```
 *
 * @category  Support
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @version   1.0.0
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */
class Container implements ContainerInterface
{
    /**
     * Transient factory closures keyed by identifier.
     *
     * @var array<string,Closure>
     */
    private array $bindings = [];

    /**
     * Singleton factory closures keyed by identifier.
     *
     * @var array<string,Closure>
     */
    private array $singletons = [];

    /**
     * Resolved singleton/instance values keyed by identifier.
     *
     * @var array<string,mixed>
     */
    private array $instances = [];

    /**
     * Register a transient binding.
     *
     * The factory is invoked on every get() call, producing a fresh value.
     *
     * @param string  $abstract Identifier (arbitrary string or class name).
     * @param Closure $concrete Factory receiving this container.
     *
     * @return void
     */
    public function bind(string $abstract, Closure $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Register a shared (singleton) binding.
     *
     * The factory is invoked at most once; the result is cached and reused.
     *
     * @param string  $abstract Identifier (arbitrary string or class name).
     * @param Closure $concrete Factory receiving this container.
     *
     * @return void
     */
    public function singleton(string $abstract, Closure $concrete): void
    {
        $this->singletons[$abstract] = $concrete;
    }

    /**
     * Register a pre-built instance as a shared value.
     *
     * @param string $abstract Identifier.
     * @param mixed  $instance The value to return for this identifier.
     *
     * @return void
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve an entry by its identifier.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return mixed
     *
     * @throws NotFoundException  When the id is unknown and not a class.
     * @throws ContainerException When resolution fails.
     */
    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->singletons[$id])) {
            return $this->instances[$id] = ($this->singletons[$id])($this);
        }

        if (isset($this->bindings[$id])) {
            return ($this->bindings[$id])($this);
        }

        if (class_exists($id)) {
            return $this->build($id);
        }

        throw new NotFoundException("No entry or class found for '$id'.");
    }

    /**
     * Determine whether an entry can be resolved.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances)
            || isset($this->singletons[$id])
            || isset($this->bindings[$id])
            || class_exists($id);
    }

    /**
     * Instantiate a class, recursively resolving its constructor dependencies.
     *
     * @param string $class Fully-qualified class name.
     *
     * @return object
     *
     * @throws ContainerException When the class or a dependency cannot be built.
     */
    private function build(string $class): object
    {
        try {
            $reflection = new ReflectionClass($class);
        } catch (Throwable $e) {
            throw new ContainerException("Unable to reflect class '$class': " . $e->getMessage(), 0, $e);
        }

        if (!$reflection->isInstantiable()) {
            throw new ContainerException("Class '$class' is not instantiable.");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->get($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            if ($type instanceof ReflectionNamedType && $type->allowsNull()) {
                $arguments[] = null;
                continue;
            }

            throw new ContainerException(
                "Cannot resolve parameter \${$parameter->getName()} of $class: "
                . 'no type hint, default value, or binding available.'
            );
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
