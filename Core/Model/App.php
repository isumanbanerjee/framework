<?php

/**
 * Application Configuration Manager
 *
 * This file contains the App class which manages application-wide
 * configuration loading and access from config.env files with caching
 * support for performance.
 *
 * PHP version 8.1
 *
 * @category  Configuration
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
 * App Configuration Class
 *
 * Centralized configuration manager that loads and caches application
 * configuration from config.env or config_compiled.php files. Provides
 * singleton pattern access to configuration values throughout the
 * application.
 *
 * Features:
 * - Singleton pattern for consistent configuration access
 * - Automatic loading from compiled or .env files
 * - In-memory caching of configuration
 * - Default value support
 * - Type-safe configuration retrieval
 *
 * Configuration priority:
 * 1. config_compiled.php (preferred, faster)
 * 2. config.env (fallback)
 *
 * Example usage:
 * ```php
 * // Get configuration value
 * $flashKey = App::config('SESSION_FLASH_KEY', 'flash_messages');
 * $dbHost = App::config('DB_HOST', 'localhost');
 *
 * // Check if configuration exists
 * if (App::has('DB_USERNAME')) {
 *     $username = App::config('DB_USERNAME');
 * }
 * ```
 *
 * @category  Configuration
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */
class App
{
    /**
     * Singleton instance of App class
     *
     * @var App|null
     */
    private static ?App $instance = null;

    /**
     * Cached configuration array
     *
     * @var array<string,mixed>
     */
    private array $config = [];

    /**
     * Private constructor to enforce singleton pattern
     *
     * Loads configuration from file system on instantiation.
     *
     * @since 1.0.0
     */
    private function __construct()
    {
        $this->loadConfiguration();
    }

    /**
     * Get singleton instance of App class
     *
     * Creates and returns the singleton instance, ensuring only one
     * configuration manager exists throughout the application lifecycle.
     *
     * @return App Singleton App instance
     *
     * @since 1.0.0
     */
    public static function getInstance(): App
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get configuration value by key
     *
     * Retrieves a configuration value from the loaded configuration.
     * Returns default value if key doesn't exist.
     *
     * @param string $key     Configuration key to retrieve
     * @param mixed  $default Default value if key not found
     *
     * @return mixed Configuration value or default
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $dbHost = App::config('DB_HOST', 'localhost');
     * $flashKey = App::config('SESSION_FLASH_KEY');
     * ```
     */
    public static function config(string $key, mixed $default = null): mixed
    {
        $instance = self::getInstance();
        return $instance->config[$key] ?? $default;
    }

    /**
     * Check if configuration key exists
     *
     * Determines whether a specific configuration key is present in the
     * loaded configuration.
     *
     * @param string $key Configuration key to check
     *
     * @return bool True if key exists, false otherwise
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * if (App::has('DB_USERNAME')) {
     *     // Use the configuration
     * }
     * ```
     */
    public static function has(string $key): bool
    {
        $instance = self::getInstance();
        return isset($instance->config[$key]);
    }

    /**
     * Get all configuration values
     *
     * Returns the complete configuration array. Useful for debugging
     * or bulk configuration operations.
     *
     * @return array<string,mixed> Complete configuration array
     *
     * @since 1.0.0
     */
    public static function all(): array
    {
        $instance = self::getInstance();
        return $instance->config;
    }

    /**
     * Load configuration from file system
     *
     * Loads configuration from config_compiled.php (preferred) or falls
     * back to parsing config.env file. Stores result in memory cache.
     *
     * @return void
     *
     * @internal
     * @since 1.0.0
     */
    private function loadConfiguration(): void
    {
        $compiledFile = __DIR__ . '/../../Configuration/config_compiled.php';
        $envFile = __DIR__ . '/../../Configuration/config.env';

        if (file_exists($compiledFile)) {
            $this->config = include $compiledFile;
        } elseif (file_exists($envFile)) {
            $this->config = parse_ini_file($envFile);
            if ($this->config === false) {
                $this->config = [];
                error_log('Failed to parse config.env file');
            }
        } else {
            $this->config = [];
            error_log('No configuration file found');
        }
    }

    /**
     * Prevent cloning of singleton instance
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization of singleton instance
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}

