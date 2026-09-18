<?php

/**
 * Global Helper Functions
 *
 * Convenience functions available application-wide, autoloaded via
 * Composer's "files" directive. Each is guarded with function_exists()
 * so the file can be included more than once without redeclaration errors.
 *
 * PHP version 8.1
 *
 * @category  Support
 * @package   Core
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */

declare(strict_types=1);

use Core\Model\App;

if (!function_exists('config')) {
    /**
     * Retrieve a configuration value by key.
     *
     * @param string $key     Configuration key.
     * @param mixed  $default Value returned when the key is absent.
     *
     * @return mixed The configuration value or the default.
     */
    function config(string $key, mixed $default = null): mixed
    {
        return App::config($key, $default);
    }
}

if (!function_exists('env')) {
    /**
     * Read an environment variable with a fallback.
     *
     * Checks $_ENV, $_SERVER, and getenv() in turn.
     *
     * @param string $key     Environment variable name.
     * @param mixed  $default Value returned when the variable is unset.
     *
     * @return mixed The environment value or the default.
     */
    function env(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        $value = getenv($key);

        return $value === false ? $default : $value;
    }
}

if (!function_exists('e')) {
    /**
     * Escape a string for safe HTML output.
     *
     * @param string|null $value Raw value to escape.
     *
     * @return string HTML-escaped string.
     */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
