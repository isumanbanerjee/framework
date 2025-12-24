<?php

/**
 * HTTP Response Handler
 *
 * This file contains the Response class which manages HTTP responses including
 * status codes, headers, and various content types (JSON, HTML, text).
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

use JsonException;
use Core\Model\Error;
use Exception;

/**
 * HTTP Response Class
 *
 * Provides convenient methods for sending HTTP responses with proper headers,
 * status codes, and content formatting. Supports JSON, HTML, plain text responses,
 * and URL redirections.
 *
 * Features:
 * - HTTP status code management
 * - Custom header manipulation
 * - JSON response with automatic encoding
 * - HTML response generation
 * - Plain text responses
 * - URL redirections
 * - Content-Type header management
 * - Pretty-printed JSON output
 *
 * All response methods (json, html, text, redirect) terminate script execution
 * after sending the response to prevent additional output.
 *
 * Example usage:
 * ```php
 * $response = new Response();
 *
 * // JSON API response
 * $response->json(['status' => 'success', 'data' => $results]);
 *
 * // HTML response
 * $response->html('<h1>Welcome</h1>', 200);
 *
 * // Redirect
 * $response->redirect('/dashboard');
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
class Response
{
    /**
     * Set the HTTP response status code
     *
     * Changes the HTTP status code for the current response. Common codes include:
     * - 200: OK
     * - 201: Created
     * - 204: No Content
     * - 301: Moved Permanently
     * - 302: Found (Temporary Redirect)
     * - 400: Bad Request
     * - 401: Unauthorized
     * - 403: Forbidden
     * - 404: Not Found
     * - 500: Internal Server Error
     * - 503: Service Unavailable
     *
     * This method must be called before any output is sent to the client.
     *
     * Example:
     * ```php
     * $response->setStatusCode(404);
     * $response->setStatusCode(201);
     * ```
     *
     * @param int $code The HTTP status code (100-599)
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
     */
    public function setStatusCode(int $code): void
    {
        http_response_code($code);
    }

    /**
     * Set a custom HTTP response header
     *
     * Adds or replaces an HTTP header in the response. Common headers include:
     * - Content-Type: Specifies response content type
     * - Cache-Control: Caching directives
     * - Location: Redirect URL
     * - X-Custom-Header: Custom application headers
     *
     * Must be called before any output is sent. Headers are case-insensitive
     * but conventionally use Title-Case format.
     *
     * Example:
     * ```php
     * $response->setHeader('Content-Type', 'application/json');
     * $response->setHeader('Cache-Control', 'no-cache, must-revalidate');
     * $response->setHeader('X-API-Version', '1.0');
     * ```
     *
     * @param string $key   The header name (e.g., 'Content-Type')
     * @param string $value The header value (e.g., 'application/json')
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function setHeader(string $key, string $value): void
    {
        header("$key: $value");
    }

    /**
     * Remove a previously set HTTP header
     *
     * Removes a specific header from the response. Useful for clearing default
     * headers or headers set earlier in the request lifecycle.
     *
     * Example:
     * ```php
     * $response->removeHeader('X-Powered-By');
     * $response->removeHeader('Set-Cookie');
     * ```
     *
     * @param string $key The header name to remove
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function removeHeader(string $key): void
    {
        header_remove($key);
    }

    /**
     * Send a JSON response and terminate execution
     *
     * Encodes the provided data as JSON with pretty-printing and sends it
     * with appropriate Content-Type header. Automatically sets the HTTP
     * status code and terminates script execution after sending.
     *
     * JSON encoding options:
     * - JSON_THROW_ON_ERROR: Throws exception on encoding errors
     * - JSON_PRETTY_PRINT: Makes output human-readable
     *
     * Ideal for RESTful API endpoints and AJAX responses.
     *
     * Example:
     * ```php
     * // Success response
     * $response->json([
     *     'status' => 'success',
     *     'data' => ['id' => 1, 'name' => 'John'],
     *     'message' => 'User created successfully'
     * ], 201);
     *
     * // Error response
     * $response->json([
     *     'status' => 'error',
     *     'message' => 'Invalid input'
     * ], 400);
     * ```
     *
     * @param mixed $data       The data to encode as JSON (array, object, scalar)
     * @param int   $statusCode The HTTP status code (default: 200)
     *
     * @return void This method terminates script execution
     *
     * @throws JsonException If JSON encoding fails
     *
     * @since 1.0.0
     */
    public function json(mixed $data, int $statusCode = 200): void
    {
        try {
            $this->setStatusCode($statusCode);
            $this->setHeader('Content-Type', 'application/json; charset=utf-8');
            
            $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            
            if ($json === false) {
                throw new Exception('Failed to encode JSON: ' . json_last_error_msg());
            }
            
            echo $json;
            exit;
        } catch (JsonException $e) {
            $error = new Error();
            $error->terminateWithError(
                'JSON_ENCODING_FAILED',
                'JSON encoding failed: ' . $e->getMessage(),
                Error::SEVERITY_ERROR
            );
        } catch (Exception $e) {
            $error = new Error();
            $error->terminateWithError(
                'RESPONSE_JSON_FAILED',
                'JSON response failed: ' . $e->getMessage(),
                Error::SEVERITY_ERROR
            );
        }
    }

    /**
     * Redirect the client to a different URL
     *
     * Sends a Location header to redirect the browser to a new URL and
     * terminates script execution. The browser will automatically navigate
     * to the specified URL.
     *
     * The default HTTP status code is 302 (temporary redirect). To use a
     * permanent redirect (301), call setStatusCode(301) before this method.
     *
     * Example:
     * ```php
     * // Temporary redirect (302)
     * $response->redirect('/login');
     *
     * // Permanent redirect (301)
     * $response->setStatusCode(301);
     * $response->redirect('/new-location');
     *
     * // Redirect to external URL
     * $response->redirect('https://example.com/page');
     * ```
     *
     * @param string $url The target URL (relative or absolute)
     *
     * @return void This method terminates script execution
     *
     * @since 1.0.0
     */
    public function redirect(string $url): void
    {
        $this->setHeader('Location', $url);
        exit;
    }

    /**
     * Send a plain text response
     *
     * Outputs plain text content with the text/plain Content-Type header.
     * Useful for simple messages, logs, or non-HTML/JSON responses.
     * Terminates script execution after sending.
     *
     * Example:
     * ```php
     * $response->text('Success', 200);
     * $response->text('Error: File not found', 404);
     * $response->text("Name: John\nEmail: john@example.com");
     * ```
     *
     * @param string $text       The plain text content to send
     * @param int    $statusCode The HTTP status code (default: 200)
     *
     * @return void This method terminates script execution
     *
     * @since 1.0.0
     */
    public function text(string $text, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'text/plain; charset=utf-8');
        echo $text;
        exit;
    }

    /**
     * Send an HTML response
     *
     * Outputs HTML content with the text/html Content-Type header.
     * Note: For production applications, consider using a templating engine
     * or view system instead of directly outputting HTML strings.
     *
     * Terminates script execution after sending the response.
     *
     * Example:
     * ```php
     * $response->html('<h1>Welcome</h1><p>Hello, World!</p>');
     *
     * $response->html('
     *     <!DOCTYPE html>
     *     <html>
     *     <head><title>Page</title></head>
     *     <body><h1>Content</h1></body>
     *     </html>
     * ', 200);
     * ```
     *
     * @param string $html       The HTML content to send
     * @param int    $statusCode The HTTP status code (default: 200)
     *
     * @return void This method terminates script execution
     *
     * @since 1.0.0
     */
    public function html(string $html, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'text/html; charset=utf-8');
        echo $html;
        exit;
    }
}

