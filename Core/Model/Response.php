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

use Exception;
use JsonException;

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
     * Whether ETag/If-None-Match support is enabled for this response.
     *
     * @var bool
     */
    private bool $etagEnabled = false;

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
            $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

            if ($json === false) {
                throw new Exception('Failed to encode JSON: ' . json_last_error_msg());
            }

            $this->sendBody($json, 'application/json; charset=utf-8', $statusCode);
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
        $this->sendBody($text, 'text/plain; charset=utf-8', $statusCode);
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
        $this->sendBody(DebugBar::inject($html, $statusCode), 'text/html; charset=utf-8', $statusCode);
    }

    /**
     * Build the default set of security headers
     *
     * Returns a hardened baseline of HTTP security headers as a
     * name => value map. Kept as a pure method (no side effects) so the
     * policy can be inspected and unit-tested without emitting headers.
     *
     * Included headers:
     * - X-Frame-Options: Clickjacking protection
     * - X-Content-Type-Options: Prevent MIME-type sniffing
     * - Referrer-Policy: Limit referrer leakage
     * - Content-Security-Policy: Restrict resource origins
     * - Permissions-Policy: Disable sensitive browser features
     *
     * @return array<string,string> Header name => value pairs
     *
     * @since 1.0.0
     */
    public function securityHeaders(): array
    {
        return [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'",
            'Permissions-Policy' => 'geolocation=(), microphone=()',
        ];
    }

    /**
     * Emit the default security headers to the client
     *
     * Applies every header returned by securityHeaders() via setHeader().
     * Must be called before any output is sent.
     *
     * Example:
     * ```php
     * $response->sendSecurityHeaders();
     * $response->json(['status' => 'ok']);
     * ```
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @see securityHeaders()
     */
    public function sendSecurityHeaders(): void
    {
        foreach ($this->securityHeaders() as $key => $value) {
            $this->setHeader($key, $value);
        }
    }

    /**
     * Enable or disable ETag/If-None-Match support for this response.
     *
     * When enabled, json()/html()/text() compute an ETag from the response
     * body, send it as a header, and short-circuit with a bodyless 304 Not
     * Modified when the client's If-None-Match header already matches —
     * disabled by default since hashing every response body has a cost not
     * every route wants to pay.
     *
     * Example:
     * ```php
     * $response->withEtag()->json($data);
     * ```
     *
     * @param bool $enabled Whether to enable ETag support (default: true)
     *
     * @return self
     *
     * @since 1.0.0
     */
    public function withEtag(bool $enabled = true): self
    {
        $this->etagEnabled = $enabled;

        return $this;
    }

    /**
     * Compute a strong ETag for a response body.
     *
     * Pure and side-effect-free so it can be unit tested without needing to
     * simulate a full HTTP response cycle.
     *
     * @param string $body Response body to hash.
     *
     * @return string Quoted ETag value, e.g. `"5d41402abc4b2a76b9719d911017c592"`.
     *
     * @since 1.0.0
     */
    public function computeEtag(string $body): string
    {
        return '"' . md5($body) . '"';
    }

    /**
     * Whether the client's If-None-Match header already matches the given
     * ETag, per RFC 7232 §3.2 (supports "*", comma-separated lists, and
     * weak "W/" validators).
     *
     * Pure and side-effect-free: reads $_SERVER directly rather than
     * requiring a Request instance, so it can be unit tested by setting
     * $_SERVER['HTTP_IF_NONE_MATCH'] directly.
     *
     * @param string $etag The ETag computed for the current response body.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function ifNoneMatchSatisfiedBy(string $etag): bool
    {
        $header = $_SERVER['HTTP_IF_NONE_MATCH'] ?? null;

        if ($header === null || $header === '') {
            return false;
        }

        if (trim($header) === '*') {
            return true;
        }

        foreach (explode(',', $header) as $candidate) {
            $candidate = preg_replace('/^W\//', '', trim($candidate));

            if ($candidate === $etag) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send a response body with its Content-Type header, applying ETag
     * negotiation when enabled, and terminate execution.
     *
     * Only 200 responses participate in ETag negotiation — a 304 in
     * response to a redirect or error status code would be meaningless.
     *
     * @param string $body       Response body.
     * @param string $contentType Content-Type header value.
     * @param int    $statusCode  HTTP status code.
     *
     * @return void This method terminates script execution
     *
     * @internal
     * @since 1.0.0
     */
    private function sendBody(string $body, string $contentType, int $statusCode): void
    {
        $this->setHeader('Content-Type', $contentType);

        if ($this->etagEnabled && $statusCode === 200) {
            $etag = $this->computeEtag($body);
            $this->setHeader('ETag', $etag);

            if ($this->ifNoneMatchSatisfiedBy($etag)) {
                $this->setStatusCode(304);
                exit;
            }
        }

        $this->setStatusCode($statusCode);
        echo $body;
        exit;
    }
}
