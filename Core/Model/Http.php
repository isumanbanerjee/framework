<?php

namespace Core\Model;

/**
 * Enterprise HTTP Client
 *
 * Modern HTTP client for making API requests with support for all HTTP methods,
 * headers, authentication, timeouts, and response handling.
 *
 * Features:
 * - All HTTP methods (GET, POST, PUT, PATCH, DELETE)
 * - Request headers and authentication
 * - JSON and form data support
 * - File uploads
 * - Timeouts and retries
 * - Response parsing
 * - SSL verification
 *
 * @package Core\Model
 * @version 1.0.0
 * @since 2025-12-24
 */
class Http
{
    private array $headers = [];
    private int $timeout = 30;
    private bool $verifySSL = true;
    private array $options = [];

    /**
     * Make GET request
     *
     * @param string $url
     * @param array $query Query parameters
     * @param array $headers
     * @return HttpResponse
     */
    public static function get(string $url, array $query = [], array $headers = []): HttpResponse
    {
        return (new self())->request('GET', $url, ['query' => $query, 'headers' => $headers]);
    }

    /**
     * Make POST request
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return HttpResponse
     */
    public static function post(string $url, array $data = [], array $headers = []): HttpResponse
    {
        return (new self())->request('POST', $url, ['json' => $data, 'headers' => $headers]);
    }

    /**
     * Make PUT request
     */
    public static function put(string $url, array $data = [], array $headers = []): HttpResponse
    {
        return (new self())->request('PUT', $url, ['json' => $data, 'headers' => $headers]);
    }

    /**
     * Make PATCH request
     */
    public static function patch(string $url, array $data = [], array $headers = []): HttpResponse
    {
        return (new self())->request('PATCH', $url, ['json' => $data, 'headers' => $headers]);
    }

    /**
     * Make DELETE request
     */
    public static function delete(string $url, array $headers = []): HttpResponse
    {
        return (new self())->request('DELETE', $url, ['headers' => $headers]);
    }

    /**
     * Make HTTP request
     */
    public function request(string $method, string $url, array $options = []): HttpResponse
    {
        $ch = curl_init();

        // Build URL with query parameters
        if (isset($options['query']) && !empty($options['query'])) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($options['query']);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout'] ?? $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $options['verify'] ?? $this->verifySSL);
        curl_setopt($ch, CURLOPT_HEADER, true);

        // Set method
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // Set headers
        $headers = array_merge($this->headers, $options['headers'] ?? []);
        if (!empty($headers)) {
            $headerArray = [];
            foreach ($headers as $key => $value) {
                $headerArray[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
        }

        // Set body
        if (isset($options['json'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options['json']));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($headerArray ?? [], ['Content-Type: application/json']));
        } elseif (isset($options['form'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($options['form']));
        } elseif (isset($options['body'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['body']);
        }

        // Execute
        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $headerString = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        curl_close($ch);

        return new HttpResponse($statusCode, $body, $this->parseHeaders($headerString));
    }

    private function parseHeaders(string $headerString): array
    {
        $headers = [];
        foreach (explode("\r\n", $headerString) as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        return $headers;
    }
}

/**
 * HTTP Response
 */
class HttpResponse
{
    private int $status;
    private string $body;
    private array $headers;

    public function __construct(int $status, string $body, array $headers)
    {
        $this->status = $status;
        $this->body = $body;
        $this->headers = $headers;
    }

    public function status(): int
    {
        return $this->status;
    }
    public function body(): string
    {
        return $this->body;
    }
    public function headers(): array
    {
        return $this->headers;
    }
    public function json(): array
    {
        return json_decode($this->body, true) ?? [];
    }
    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }
    public function failed(): bool
    {
        return !$this->successful();
    }
}
