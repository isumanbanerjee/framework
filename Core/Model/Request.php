<?php

/**
 * HTTP Request Handler
 *
 * This file contains the Request class which encapsulates HTTP request data
 * and provides convenient methods for accessing request information.
 *
 * PHP version 8.1
 *
 * @category  HTTP
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */

declare(strict_types=1);

namespace Core\Model;

use Exception;

/**
 * HTTP Request Class
 *
 * Represents an HTTP request and provides secure methods to access request data
 * including GET parameters, POST data, headers, cookies, and uploaded files.
 * Implements automatic sanitization for common security vulnerabilities.
 *
 * Features:
 * - Access to GET, POST, SERVER, FILES, and COOKIE superglobals
 * - HTTP method detection (GET, POST, PUT, DELETE, etc.)
 * - URL and path parsing
 * - Request header extraction
 * - Input sanitization (XSS protection)
 * - Content type detection
 * - AJAX/JSON request identification
 * - File upload handling
 * - Cookie management
 *
 * Security features:
 * - Automatic HTML entity encoding for string inputs
 * - Trim whitespace from input values
 * - Safe access with default values
 * - Header normalization
 *
 * Example usage:
 * ```php
 * $request = new Request();
 * $method = $request->getMethod();              // GET
 * $path = $request->getPath();                  // /api/users
 * $name = $request->input('name', 'Guest');     // Sanitized input
 * $isAjax = $request->isAjax();                 // true/false
 * ```
 *
 * @category  HTTP
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @version   1.0.0
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */
class Request
{
    /**
     * GET request parameters ($_GET superglobal)
     *
     * @var array<string,mixed>
     */
    private array $get;

    /**
     * POST request parameters ($_POST superglobal)
     *
     * @var array<string,mixed>
     */
    private array $post;

    /**
     * Server and execution environment information ($_SERVER superglobal)
     *
     * @var array<string,mixed>
     */
    private array $server;

    /**
     * Uploaded files information ($_FILES superglobal)
     *
     * @var array<string,mixed>
     */
    private array $files;

    /**
     * Cookie data ($_COOKIE superglobal)
     *
     * @var array<string,mixed>
     */
    private array $cookies;

    /**
     * HTTP request headers extracted from $_SERVER
     *
     * @var array<string,string>
     */
    private array $headers;

    /**
     * Initialize the request object with current HTTP request data
     *
     * Captures all relevant superglobals ($_GET, $_POST, $_SERVER, $_FILES, $_COOKIE)
     * and extracts HTTP headers from the $_SERVER array. This constructor should be
     * called once per request to encapsulate all request data.
     *
     * The headers are automatically extracted and normalized from the $_SERVER
     * array by the resolveHeaders() method.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        try {
            $this->get = $_GET ?? [];
            $this->post = $_POST ?? [];
            $this->server = $_SERVER ?? [];
            $this->files = $_FILES ?? [];
            $this->cookies = $_COOKIE ?? [];
            $this->headers = $this->resolveHeaders();
        } catch (Exception $e) {
            $error = new Error();
            $error->terminateWithError(
                'REQUEST_INITIALIZATION_FAILED',
                'Request initialization failed: ' . $e->getMessage(),
                Error::SEVERITY_CRITICAL
            );
        }
    }

    /**
     * Get the HTTP request method
     *
     * Returns the HTTP method used for the current request (GET, POST, PUT,
     * DELETE, PATCH, OPTIONS, HEAD, etc.). The method name is always returned
     * in uppercase for consistency.
     *
     * Fallback: Returns 'GET' if REQUEST_METHOD is not set in $_SERVER.
     *
     * @return string The HTTP method in uppercase (e.g., 'GET', 'POST', 'PUT')
     *
     * @since 1.0.0
     */
    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Check if the request method matches the specified method
     *
     * Performs a case-insensitive comparison between the current request
     * method and the provided method string.
     *
     * Example:
     * ```php
     * if ($request->isMethod('POST')) {
     *     // Handle POST request
     * }
     * ```
     *
     * @param string $method The HTTP method to check against (case-insensitive)
     *
     * @return bool True if the methods match, false otherwise
     *
     * @since 1.0.0
     */
    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    /**
     * Get the request URI path without query parameters
     *
     * Extracts and returns only the path portion of the REQUEST_URI, excluding
     * any query string parameters. This is useful for routing and path matching.
     *
     * Examples:
     * - /api/users?page=1 → /api/users
     * - /about → /about
     * - / → /
     *
     * @return string The request path without query string
     *
     * @since 1.0.0
     */
    public function getPath(): string
    {
        $path = $this->server['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');

        if ($position === false) {
            return $path;
        }

        return substr($path, 0, $position);
    }

    /**
     * Get the full URL of the current request
     *
     * Constructs and returns the complete URL including protocol, host, and URI.
     * Automatically detects HTTPS connections and adjusts the protocol accordingly.
     *
     * Example output:
     * - https://example.com/api/users?page=1
     * - http://localhost:8080/admin/dashboard
     *
     * @return string The complete URL of the current request
     *
     * @since 1.0.0
     */
    public function getUrl(): string
    {
        $protocol = (isset($this->server['HTTPS']) && $this->server['HTTPS'] === 'on')
            ? 'https'
            : 'http';
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        $uri = $this->server['REQUEST_URI'] ?? '/';

        return "$protocol://$host$uri";
    }

    /**
     * Retrieve all input data from GET and POST parameters
     *
     * Merges GET and POST arrays into a single array containing all input data.
     * POST data takes precedence over GET data if the same key exists in both.
     *
     * This method is useful when you need to access all user input regardless
     * of the HTTP method used.
     *
     * Example:
     * ```php
     * $allInputs = $request->all();
     * foreach ($allInputs as $key => $value) {
     *     // Process each input
     * }
     * ```
     *
     * @return array<string,mixed> Combined GET and POST parameters
     *
     * @since 1.0.0
     */
    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    /**
     * Retrieve a specific input value with automatic sanitization
     *
     * Safely retrieves an input value by key from merged GET and POST data.
     * Provides XSS protection by automatically sanitizing string values using
     * htmlspecialchars(). Trims whitespace from string values and returns the
     * default value if the key doesn't exist.
     *
     * Sanitization features:
     * - HTML entity encoding (ENT_QUOTES)
     * - UTF-8 encoding
     * - Whitespace trimming
     * - Default value fallback
     *
     * Example:
     * ```php
     * $name = $request->input('name', 'Guest');
     * $age = $request->input('age', 0);
     * $tags = $request->input('tags', []);
     * ```
     *
     * @param string $key     The input parameter name to retrieve
     * @param mixed  $default The default value if the key is not found (optional)
     *
     * @return mixed The sanitized input value or default value
     *
     * @since 1.0.0
     */
    public function input(string $key, mixed $default = null): mixed
    {
        $all = $this->all();
        $value = $all[$key] ?? $default;

        // Basic sanitization for strings
        if (is_string($value)) {
            return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }

        return $value;
    }

    /**
     * Check if a specific input parameter exists in the request
     *
     * Checks both GET and POST data for the existence of a parameter key.
     * Returns true if the key exists in either array, regardless of its value
     * (even if null or empty string).
     *
     * Example:
     * ```php
     * if ($request->has('email')) {
     *     $email = $request->input('email');
     * }
     * ```
     *
     * @param string $key The parameter name to check for
     *
     * @return bool True if the parameter exists, false otherwise
     *
     * @since 1.0.0
     */
    public function has(string $key): bool
    {
        return isset($this->get[$key]) || isset($this->post[$key]);
    }

    /**
     * Get information about an uploaded file
     *
     * Retrieves file upload information from the $_FILES superglobal.
     * Returns an array with file details (name, type, tmp_name, error, size)
     * or null if no file was uploaded with the given key.
     *
     * File array structure:
     * - name: Original filename
     * - type: MIME type
     * - tmp_name: Temporary file path
     * - error: Upload error code
     * - size: File size in bytes
     *
     * Example:
     * ```php
     * $file = $request->file('avatar');
     * if ($file && $file['error'] === UPLOAD_ERR_OK) {
     *     move_uploaded_file($file['tmp_name'], '/uploads/' . $file['name']);
     * }
     * ```
     *
     * @param string $key The file input field name
     *
     * @return array<string,mixed>|null File information array or null if not found
     *
     * @since 1.0.0
     *
     * @see https://www.php.net/manual/en/features.file-upload.post-method.php
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Determine if the request is an AJAX/XMLHttpRequest
     *
     * Checks for the X-Requested-With header commonly sent by AJAX requests
     * made with JavaScript libraries like jQuery, Axios, etc.
     *
     * This is useful for providing different responses for AJAX vs regular
     * page requests (e.g., JSON vs HTML).
     *
     * Example:
     * ```php
     * if ($request->isAjax()) {
     *     return json_encode(['status' => 'success']);
     * } else {
     *     return view('success.html');
     * }
     * ```
     *
     * @return bool True if the request is an AJAX request, false otherwise
     *
     * @since 1.0.0
     */
    public function isAjax(): bool
    {
        return isset($this->headers['X-Requested-With'])
            && $this->headers['X-Requested-With'] === 'XMLHttpRequest';
    }

    /**
     * Extract and normalize HTTP headers from $_SERVER array
     *
     * Attempts to get headers using getallheaders() if available (Apache/nginx).
     * Falls back to parsing $_SERVER array for headers starting with HTTP_.
     * Normalizes header names to standard format (e.g., Content-Type).
     *
     * Header normalization process:
     * 1. Strip HTTP_ prefix from $_SERVER keys
     * 2. Replace underscores with hyphens
     * 3. Convert to Title-Case format
     *
     * Example transformations:
     * - HTTP_CONTENT_TYPE → Content-Type
     * - HTTP_ACCEPT_LANGUAGE → Accept-Language
     * - HTTP_X_REQUESTED_WITH → X-Requested-With
     *
     * @return array<string,string> Normalized array of HTTP headers
     *
     * @since 1.0.0
     */
    private function resolveHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', strtolower(substr($key, 5)));
                $headerName = ucwords($headerName, '-');
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }

    /**
     * Get the client's IP address
     *
     * Attempts to determine the real client IP address, accounting for proxies
     * and load balancers. Checks multiple headers in priority order:
     * 1. HTTP_CLIENT_IP (less common, corporate proxies)
     * 2. HTTP_X_FORWARDED_FOR (standard proxy header)
     * 3. REMOTE_ADDR (direct connection)
     *
     * Security note: These headers can be spoofed. For security-critical
     * applications, consider additional validation or use REMOTE_ADDR only.
     *
     * Example:
     * ```php
     * $ip = $request->ip();
     * $logger->log("Login attempt from IP: $ip");
     * ```
     *
     * @return string The client IP address, or '0.0.0.0' if unavailable
     *
     * @since 1.0.0
     */
    public function ip(): string
    {
        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            return $this->server['HTTP_CLIENT_IP'];
        } elseif (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            return $this->server['HTTP_X_FORWARDED_FOR'];
        }
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Apply a transformer to every scalar value in the GET and POST data
     *
     * Recurses into nested arrays and replaces each non-array value with
     * the result of calling $transformer on it. Used by SanitizeMiddleware
     * to trim strings and convert empty strings to null across all input,
     * without touching non-string values (ints, bools, uploaded files, etc.).
     *
     * @param callable $transformer Callback invoked as $transformer(mixed $value): mixed
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function transformInput(callable $transformer): void
    {
        $this->get = $this->mapInputRecursive($this->get, $transformer);
        $this->post = $this->mapInputRecursive($this->post, $transformer);
    }

    /**
     * Recursively apply a transformer to every non-array value in an array
     *
     * @param array    $data        Input array to transform
     * @param callable $transformer Callback invoked as $transformer(mixed $value): mixed
     *
     * @return array Transformed array with the same structure
     *
     * @internal
     * @since 1.0.0
     */
    private function mapInputRecursive(array $data, callable $transformer): array
    {
        foreach ($data as $key => $value) {
            $data[$key] = is_array($value)
                ? $this->mapInputRecursive($value, $transformer)
                : $transformer($value);
        }

        return $data;
    }
}
