<?php

/**
 * Application Error Handler and Manager
 *
 * This file contains the Error class which provides comprehensive error
 * management including environment-aware display, rate limiting, custom
 * templates, and notification handling.
 *
 * PHP version 8.1
 *
 * @category  ErrorHandling
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */

declare(strict_types=1);

namespace Core\Model;

use Core\Model\Monitoring\MonitorInterface;
use Core\Model\Monitoring\NullMonitor;
use JetBrains\PhpStorm\NoReturn;
use Throwable;

/**
 * Error Class
 *
 * Comprehensive error handler managing application errors with environment-
 * aware display, severity levels, rate limiting, and custom templates.
 * Supports both HTML and JSON error responses for API and web applications.
 *
 * Features:
 * - Environment-aware error display (production vs development)
 * - Six severity levels (DEBUG to FATAL)
 * - Stack trace capture and formatting
 * - Error rate limiting to prevent log flooding
 * - HTTP status code mapping
 * - Custom error templates
 * - Error context collection
 * - JSON and HTML error responses
 * - Automatic configuration loading
 * - Default error code fallbacks
 *
 * Security features:
 * - Sensitive details hidden in production
 * - Rate limiting prevents DoS via error logging
 * - Configurable error display
 * - Safe error template rendering
 *
 * Severity levels:
 * - DEBUG: Development debugging information
 * - INFO: Informational messages
 * - WARNING: Warning conditions
 * - ERROR: Error conditions
 * - CRITICAL: Critical conditions
 * - FATAL: Fatal errors requiring immediate attention
 *
 * Example usage:
 * ```php
 * $error = new Error('production', false);
 *
 * // Terminate with error
 * $error->terminateWithError('NOT_FOUND', 'User not found');
 *
 * // Log error without termination
 * $error->logError('DATABASE_ERROR', 'Connection failed', [
 *     'host' => 'localhost',
 *     'port' => 3306
 * ]);
 *
 * // Register global handlers
 * $error->registerGlobalHandlers();
 * ```
 *
 * @category  ErrorHandling
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */
class Error
{
    /**
     * Debug severity level - Detailed debugging information
     *
     * @var string
     */
    public const SEVERITY_DEBUG = 'DEBUG';

    /**
     * Info severity level - Informational messages
     *
     * @var string
     */
    public const SEVERITY_INFO = 'INFO';

    /**
     * Warning severity level - Warning conditions
     *
     * @var string
     */
    public const SEVERITY_WARNING = 'WARNING';

    /**
     * Error severity level - Error conditions
     *
     * @var string
     */
    public const SEVERITY_ERROR = 'ERROR';

    /**
     * Critical severity level - Critical conditions
     *
     * @var string
     */
    public const SEVERITY_CRITICAL = 'CRITICAL';

    /**
     * Fatal severity level - Fatal error conditions
     *
     * @var string
     */
    public const SEVERITY_FATAL = 'FATAL';

    /**
     * Error codes and corresponding user-friendly messages
     *
     * Loaded from error.env or error_compiled.php configuration files.
     * Maps error codes to human-readable messages.
     *
     * @var array<string,string>
     */
    private array $errorCodes;

    /**
     * Current application environment
     *
     * Determines error display verbosity. Common values:
     * - development: Full error details shown
     * - staging: Limited details shown
     * - production: Minimal details, generic messages only
     *
     * @var string
     */
    private string $environment;

    /**
     * Debug mode flag for detailed error output
     *
     * When true, displays full stack traces, variables, and context.
     * Should be false in production environments.
     *
     * @var bool
     */
    private bool $debugMode;

    /**
     * Error rate limiting cache
     *
     * Tracks error occurrence frequency to prevent log flooding.
     * Structure: ['error_code' => ['count' => int, 'first' => timestamp]]
     *
     * @var array<string,array<string,int>>
     */
    private array $errorRateLimit = [];

    /**
     * Maximum errors allowed per hour
     *
     * Rate limit threshold. When exceeded, additional errors are
     * suppressed to prevent log flooding and resource exhaustion.
     *
     * @var int
     */
    private int $maxErrorsPerHour = 100;

    /**
     * HTTP status code mapping for error codes
     *
     * Maps application error codes to appropriate HTTP status codes
     * for proper REST API and HTTP response handling.
     *
     * @var array<string,int>
     */
    private array $statusCodeMap = [
        'NOT_FOUND' => 404,
        'UNAUTHORIZED' => 401,
        'FORBIDDEN' => 403,
        'BAD_REQUEST' => 400,
        'METHOD_NOT_ALLOWED' => 405,
        'CONFLICT' => 409,
        'UNPROCESSABLE_ENTITY' => 422,
        'TOO_MANY_REQUESTS' => 429,
        'SERVICE_UNAVAILABLE' => 503,
        'INTERNAL_SERVER_ERROR' => 500,
    ];

    /**
     * Custom error notification handler callback
     *
     * Optional callable for sending error notifications (email, Slack,
     * logging service, etc.). Receives error data array as parameter.
     *
     * @var callable|null
     */
    private $notificationHandler = null;

    /**
     * Path to custom error template file
     *
     * When set, uses custom template instead of default error display.
     * Template receives error data as variables.
     *
     * @var string|null
     */
    private ?string $errorTemplatePath = null;

    /**
     * Monitoring/APM hook receiving captured exceptions
     *
     * Defaults to {@see NullMonitor} (a no-op) so the framework always has a
     * monitor to call. Wire a real service with {@see self::setMonitor()}.
     *
     * @var MonitorInterface
     */
    private MonitorInterface $monitor;

    /**
     * Initialize error handler with environment configuration
     *
     * Constructs a new Error instance, loads error codes from
     * configuration files, and applies environment settings from App
     * configuration. Configuration values from App override constructor
     * parameters.
     *
     * Configuration loading priority:
     * 1. Constructor parameters (if App config not available)
     * 2. App configuration settings (override constructor params)
     * 3. Configuration file settings
     * 4. Default values
     *
     * Error codes loaded from (in order):
     * 1. error_compiled.php (preferred, faster)
     * 2. error.env (fallback)
     * 3. Default hardcoded errors (last resort)
     *
     * Configuration keys (from config.env):
     * - ERROR_ENVIRONMENT: production/development/staging
     * - ERROR_DEBUG_MODE: true/false
     * - ERROR_RATE_LIMIT: Maximum errors per hour
     * - ERROR_TEMPLATE_PATH: Custom error template path
     *
     * @param string $environment Application environment name
     *                            (development/staging/production)
     * @param bool   $debugMode   Enable detailed error display
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // Production with debug off
     * $error = new Error('production', false);
     *
     * // Development with debug on
     * $error = new Error('development', true);
     *
     * // Using App configuration (recommended)
     * $error = new Error();
     * ```
     */
    public function __construct(
        string $environment = 'production',
        bool $debugMode = false
    ) {
        // Load from App configuration first, fall back to parameters
        $this->environment = App::config('ERROR_ENVIRONMENT', $environment);
        $this->debugMode = (bool)App::config('ERROR_DEBUG_MODE', $debugMode);
        $this->monitor = new NullMonitor();

        $this->loadErrorCodes();
        $this->loadConfiguration();
    }


    /**
     * Load configuration from App
     *
     * Loads additional error configuration from the App configuration
     * manager. Values loaded here override constructor parameters.
     *
     * Configuration keys:
     * - ERROR_ENVIRONMENT: Overrides environment setting
     * - ERROR_DEBUG_MODE: Overrides debug mode
     * - ERROR_RATE_LIMIT: Sets max errors per hour
     * - ERROR_TEMPLATE_PATH: Sets custom error template
     *
     * @return void
     *
     * @internal
     * @since 1.0.0
     */
    private function loadConfiguration(): void
    {
        // Load error rate limit if set
        if (App::has('ERROR_RATE_LIMIT')) {
            $this->maxErrorsPerHour = (int)App::config('ERROR_RATE_LIMIT', 100);
        }

        // Load custom error template path if set
        if (App::has('ERROR_TEMPLATE_PATH')) {
            $templatePath = App::config('ERROR_TEMPLATE_PATH');
            if (!empty($templatePath)) {
                $this->errorTemplatePath = $templatePath;
            }
        }
    }

    /**
     * Loads error codes from a compiled PHP file or an environment file.
     */
    private function loadErrorCodes(): void
    {
        $compiledFile = __DIR__ . '/../../Configuration/error_compiled.php';
        $envFile = __DIR__ . '/../../Configuration/error.env';

        if (file_exists($compiledFile)) {
            $this->errorCodes = include $compiledFile;
        } elseif (file_exists($envFile)) {
            $parsed = parse_ini_file($envFile);
            if ($parsed === false) {
                $this->errorCodes = [];
                error_log('Failed to parse error.env file');
            } else {
                $this->errorCodes = $parsed;
            }
        } else {
            // Fallback to default error codes
            $this->errorCodes = $this->getDefaultErrorCodes();
            error_log('Error configuration files not found, using defaults');
        }
    }

    /**
     * Get default error codes as fallback.
     *
     * @return array
     */
    private function getDefaultErrorCodes(): array
    {
        return [
            'INTERNAL_SERVER_ERROR' => 'An internal server error occurred. Please try again later.',
            'NOT_FOUND' => 'The requested resource was not found.',
            'UNAUTHORIZED' => 'You are not authorized to access this resource.',
            'FORBIDDEN' => 'Access to this resource is forbidden.',
            'BAD_REQUEST' => 'The request could not be understood or was missing required parameters.',
            'METHOD_NOT_ALLOWED' => 'The HTTP method is not allowed for this resource.',
            'SERVICE_UNAVAILABLE' => 'The service is temporarily unavailable. Please try again later.',
        ];
    }

    /**
     * Terminates the script with an error message and appropriate HTTP response code.
     * Detects AJAX/JSON requests and outputs JSON instead of HTML.
     * Environment-aware: shows details only in development mode.
     *
     * @param string $code The error code.
     * @param string $details Optional technical details (e.g., exception message).
     * @param string $severity Error severity level.
     * @param array $context Additional context information.
     */
    #[NoReturn] public function terminateWithError(
        string $code,
        string $details = '',
        string $severity = self::SEVERITY_ERROR,
        array $context = []
    ): never {
        $message = $this->getErrorMessage($code);
        $statusCode = $this->getHttpStatusCode($code);

        // Set HTTP response code
        if (!headers_sent()) {
            http_response_code($statusCode);
        }

        // Prepare error data
        $errorData = [
            'error' => true,
            'code' => $code,
            'message' => $message,
            'severity' => $severity,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        // Add details only in debug mode or development environment
        if ($this->shouldShowDetails()) {
            $errorData['details'] = $details;
            $errorData['context'] = $context;
            $errorData['environment'] = $this->environment;
        }

        // Handle JSON requests
        if ($this->isJsonRequest()) {
            $this->sendJsonError($errorData);
        } else {
            $this->sendHtmlError($errorData, $details);
        }

        exit;
    }

    /**
     * Send JSON error response.
     *
     * @param array $errorData
     */
    private function sendJsonError(array $errorData): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($errorData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Send HTML error response.
     *
     * @param array $errorData
     * @param string $details
     */
    private function sendHtmlError(array $errorData, string $details): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        // Use custom template if available
        if ($this->errorTemplatePath && file_exists($this->errorTemplatePath)) {
            include $this->errorTemplatePath;
            return;
        }

        // Default HTML error template
        $this->renderDefaultErrorTemplate($errorData, $details);
    }

    /**
     * Render the default error template.
     *
     * @param array $errorData
     * @param string $details
     */
    private function renderDefaultErrorTemplate(array $errorData, string $details): void
    {
        $severityColors = [
            self::SEVERITY_DEBUG => '#17a2b8',
            self::SEVERITY_INFO => '#007bff',
            self::SEVERITY_WARNING => '#ffc107',
            self::SEVERITY_ERROR => '#dc3545',
            self::SEVERITY_CRITICAL => '#c82333',
            self::SEVERITY_FATAL => '#bd2130',
        ];

        $color = $severityColors[$errorData['severity']] ?? '#dc3545';
        $code = htmlspecialchars($errorData['code']);
        $message = htmlspecialchars($errorData['message']);
        $severity = htmlspecialchars($errorData['severity']);

        echo "<!DOCTYPE html>
<html lang='en'>
<head>
	<meta charset='UTF-8'>
	<meta name='viewport' content='width=device-width, initial-scale=1.0'>
	<title>Error: {$code}</title>
	<style>
		body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
		.error-container { max-width: 800px; margin: 50px auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
		.error-header { background: {$color}; color: white; padding: 30px; }
		.error-header h1 { margin: 0; font-size: 24px; }
		.error-header p { margin: 10px 0 0; opacity: 0.9; }
		.error-body { padding: 30px; }
		.error-detail { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 15px 0; font-family: monospace; font-size: 14px; overflow-x: auto; }
		.error-label { font-weight: bold; color: #495057; margin-bottom: 5px; }
		.error-footer { padding: 20px 30px; background: #f8f9fa; border-top: 1px solid #dee2e6; font-size: 14px; color: #6c757d; }
	</style>
</head>
<body>
	<div class='error-container'>
		<div class='error-header'>
			<h1>Error: {$code}</h1>
			<p>Severity: {$severity}</p>
		</div>
		<div class='error-body'>
			<div class='error-label'>Message:</div>
			<p>{$message}</p>";

        if ($this->shouldShowDetails() && !empty($details)) {
            $detailsHtml = htmlspecialchars($details);
            echo "
			<div class='error-label'>Technical Details:</div>
			<div class='error-detail'>{$detailsHtml}</div>";
        }

        if ($this->shouldShowDetails() && !empty($errorData['context'])) {
            $contextHtml = htmlspecialchars(json_encode($errorData['context'], JSON_PRETTY_PRINT));
            echo "
			<div class='error-label'>Context:</div>
			<div class='error-detail'><pre>{$contextHtml}</pre></div>";
        }

        echo "
		</div>
		<div class='error-footer'>
			Timestamp: {$errorData['timestamp']}";

        if ($this->shouldShowDetails()) {
            echo " | Environment: {$this->environment}";
        }

        echo '
		</div>
	</div>
</body>
</html>';
    }

    /**
     * Determine if detailed error information should be shown.
     *
     * @return bool
     */
    private function shouldShowDetails(): bool
    {
        return $this->debugMode || $this->environment === 'development' || $this->environment === 'staging';
    }

    /**
     * Get HTTP status code for error code.
     *
     * @param string $code
     * @return int
     */
    private function getHttpStatusCode(string $code): int
    {
        return $this->statusCodeMap[$code] ?? 500;
    }

    /**
     * Displays an error message.
     *
     * @param string $code The error code.
     * @param string $severity Error severity level.
     */
    public function displayError(string $code, string $severity = self::SEVERITY_ERROR): void
    {
        echo $this->getErrorMessage($code);
    }

    /**
     * Handle an exception and log it.
     *
     * @param Throwable $exception
     * @param Logger|null $logger
     * @param string $severity
     * @return array Exception data
     */
    public function handleException(
        Throwable $exception,
        ?Logger $logger = null,
        string $severity = self::SEVERITY_ERROR
    ): array {
        $exceptionData = $this->extractExceptionData($exception);

        // Log the exception if logger is provided
        if ($logger !== null && $this->checkRateLimit($exception->getCode())) {
            $logger->logError(
                $exceptionData['message'],
                $exceptionData
            );
        }

        // Call notification handler if configured
        if ($this->notificationHandler !== null && $severity >= self::SEVERITY_CRITICAL) {
            call_user_func($this->notificationHandler, $exceptionData, $severity);
        }

        // Report to the monitoring/APM hook for critical and fatal errors
        if (in_array($severity, [self::SEVERITY_CRITICAL, self::SEVERITY_FATAL], true)) {
            $this->monitor->captureException($exception, ['severity' => $severity] + $exceptionData);
        }

        return $exceptionData;
    }

    /**
     * Extract structured data from an exception.
     *
     * @param Throwable $exception
     * @return array
     */
    private function extractExceptionData(Throwable $exception): array
    {
        return [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->formatStackTrace($exception->getTrace()),
            'previous' => $exception->getPrevious() ? $this->extractExceptionData($exception->getPrevious()) : null,
        ];
    }

    /**
     * Format stack trace for better readability.
     *
     * @param array $trace
     * @return array
     */
    private function formatStackTrace(array $trace): array
    {
        $formatted = [];
        foreach ($trace as $index => $frame) {
            $formatted[] = [
                'index' => $index,
                'file' => $frame['file'] ?? 'unknown',
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? 'unknown',
                'class' => $frame['class'] ?? null,
                'type' => $frame['type'] ?? null,
            ];
        }
        return $formatted;
    }

    /**
     * Check if error should be logged based on rate limiting.
     *
     * @param mixed $errorKey
     * @return bool
     */
    private function checkRateLimit($errorKey): bool
    {
        $key = md5(serialize($errorKey));
        $now = time();

        // Clean old entries
        $this->errorRateLimit = array_filter(
            $this->errorRateLimit,
            fn ($timestamp) => ($now - $timestamp) < 3600
        );

        // Count occurrences in last hour
        $count = count(array_filter(
            $this->errorRateLimit,
            fn ($timestamp, $k) => $k === $key,
            ARRAY_FILTER_USE_BOTH
        ));

        if ($count >= $this->maxErrorsPerHour) {
            return false;
        }

        $this->errorRateLimit[$key] = $now;
        return true;
    }

    /**
     * Log an error with context.
     *
     * @param string $code
     * @param string $message
     * @param array $context
     * @param Logger|null $logger
     * @param string $severity
     */
    public function logError(
        string $code,
        string $message,
        array $context = [],
        ?Logger $logger = null,
        string $severity = self::SEVERITY_ERROR
    ): void {
        if ($logger === null || !$this->checkRateLimit($code)) {
            return;
        }

        $logContext = array_merge([
            'error_code' => $code,
            'severity' => $severity,
            'environment' => $this->environment,
        ], $context);

        $logger->logError($message, $logContext);

        // Trigger notification for critical errors
        if ($this->notificationHandler !== null &&
            in_array($severity, [self::SEVERITY_CRITICAL, self::SEVERITY_FATAL])) {
            call_user_func($this->notificationHandler, [
                'code' => $code,
                'message' => $message,
                'context' => $logContext,
            ], $severity);
        }
    }

    /**
     * Retrieves the error message corresponding to the given error code.
     *
     * @param string $code The error code.
     * @return string The error message.
     */
    private function getErrorMessage(string $code): string
    {
        return $this->errorCodes[$code] ?? 'Unknown error occurred.';
    }

    /**
     * Registers this class as the global exception and error handler.
     *
     * @param Logger $logger An instance of Logger to log the exceptions.
     */
    public function registerHandlers(Logger $logger): void
    {
        // Handle Uncaught Exceptions
        set_exception_handler(function (Throwable $e) use ($logger) {
            $exceptionData = $this->handleException($e, $logger, self::SEVERITY_CRITICAL);

            $this->terminateWithError(
                'INTERNAL_SERVER_ERROR',
                $exceptionData['message'],
                self::SEVERITY_CRITICAL,
                $this->shouldShowDetails() ? $exceptionData : []
            );
        });

        // Handle PHP Errors (Warnings, Notices, etc.)
        set_error_handler(function ($severity, $message, $file, $line) use ($logger) {
            if (!(error_reporting() & $severity)) {
                // This error code is not included in error_reporting
                return;
            }

            $errorSeverity = $this->mapPhpErrorSeverity($severity);
            $context = [
                'file' => $file,
                'line' => $line,
                'php_error_level' => $severity,
            ];

            $this->logError(
                'PHP_ERROR',
                "PHP Error [$severity]: $message",
                $context,
                $logger,
                $errorSeverity
            );

            // Terminate on fatal errors
            if (in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
                $this->terminateWithError(
                    'INTERNAL_SERVER_ERROR',
                    $message,
                    $errorSeverity,
                    $context
                );
            }
        });

        // Handle fatal errors
        register_shutdown_function(function () use ($logger) {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
                $this->logError(
                    'FATAL_ERROR',
                    "Fatal Error: {$error['message']}",
                    [
                        'file' => $error['file'],
                        'line' => $error['line'],
                        'type' => $error['type'],
                    ],
                    $logger,
                    self::SEVERITY_FATAL
                );

                if (!$this->isJsonRequest()) {
                    $this->terminateWithError(
                        'INTERNAL_SERVER_ERROR',
                        $error['message'],
                        self::SEVERITY_FATAL,
                        $this->shouldShowDetails() ? $error : []
                    );
                }
            }
        });
    }

    /**
     * Map PHP error severity to custom severity levels.
     *
     * @param int $phpSeverity
     * @return string
     */
    private function mapPhpErrorSeverity(int $phpSeverity): string
    {
        return match ($phpSeverity) {
            E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR => self::SEVERITY_FATAL,
            E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING => self::SEVERITY_WARNING,
            E_NOTICE, E_USER_NOTICE => self::SEVERITY_INFO,
            E_DEPRECATED, E_USER_DEPRECATED => self::SEVERITY_DEBUG,
            default => self::SEVERITY_ERROR,
        };
    }

    /**
     * Check if the request expects a JSON response.
     *
     * @return bool
     */
    private function isJsonRequest(): bool
    {
        // Check Content-Type header
        if (isset($_SERVER['HTTP_CONTENT_TYPE']) && str_contains($_SERVER['HTTP_CONTENT_TYPE'], 'application/json')) {
            return true;
        }
        // Check Accept header
        if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            return true;
        }
        // Check X-Requested-With (Standard AJAX header)
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            return true;
        }

        return false;
    }

    /**
     * Set a custom notification handler for critical errors.
     *
     * @param callable $handler Function to call when critical errors occur
     * @return self
     */
    public function setNotificationHandler(callable $handler): self
    {
        $this->notificationHandler = $handler;
        return $this;
    }

    /**
     * Set the monitoring/APM hook that receives captured exceptions.
     *
     * @param MonitorInterface $monitor Monitor to report critical/fatal
     *                                  exceptions to (see Core\Model\Monitoring).
     * @return self
     */
    public function setMonitor(MonitorInterface $monitor): self
    {
        $this->monitor = $monitor;
        return $this;
    }

    /**
     * Set a custom error template path.
     *
     * @param string $templatePath Path to custom error template
     * @return self
     */
    public function setErrorTemplate(string $templatePath): self
    {
        $this->errorTemplatePath = $templatePath;
        return $this;
    }

    /**
     * Set custom HTTP status code mapping.
     *
     * @param array $mapping Array of error codes to HTTP status codes
     * @return self
     */
    public function setStatusCodeMap(array $mapping): self
    {
        $this->statusCodeMap = array_merge($this->statusCodeMap, $mapping);
        return $this;
    }

    /**
     * Set the maximum errors per hour for rate limiting.
     *
     * @param int $maxErrors Maximum number of errors per hour
     * @return self
     */
    public function setMaxErrorsPerHour(int $maxErrors): self
    {
        $this->maxErrorsPerHour = $maxErrors;
        return $this;
    }

    /**
     * Get the current environment.
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }

    /**
     * Set debug mode.
     *
     * @param bool $debugMode
     * @return self
     */
    public function setDebugMode(bool $debugMode): self
    {
        $this->debugMode = $debugMode;
        return $this;
    }

    /**
     * Get all error codes.
     *
     * @return array
     */
    public function getErrorCodes(): array
    {
        return $this->errorCodes;
    }

    /**
     * Add or update an error code.
     *
     * @param string $code Error code
     * @param string $message Error message
     * @return self
     */
    public function addErrorCode(string $code, string $message): self
    {
        $this->errorCodes[$code] = $message;
        return $this;
    }

    /**
     * Check if an error code exists.
     *
     * @param string $code Error code
     * @return bool
     */
    public function hasErrorCode(string $code): bool
    {
        return isset($this->errorCodes[$code]);
    }

    /**
     * Clear error rate limit cache.
     *
     * @return self
     */
    public function clearRateLimit(): self
    {
        $this->errorRateLimit = [];
        return $this;
    }

    /**
     * Get error statistics.
     *
     * @return array
     */
    public function getErrorStats(): array
    {
        $now = time();
        $recentErrors = array_filter(
            $this->errorRateLimit,
            fn ($timestamp) => ($now - $timestamp) < 3600
        );

        return [
            'total_tracked_errors' => count($recentErrors),
            'unique_error_types' => count(array_unique(array_keys($recentErrors))),
            'rate_limit_active' => count($recentErrors) >= $this->maxErrorsPerHour,
            'max_errors_per_hour' => $this->maxErrorsPerHour,
        ];
    }
}
