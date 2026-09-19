<?php

/**
 * Application Logger with Log Rotation
 *
 * This file contains the Logger class which provides comprehensive logging
 * functionality with automatic log rotation support.
 *
 * PHP version 8.1
 *
 * @category  Logging
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
 * Logger Class
 *
 * Handles application logging with automatic log file rotation based on file size.
 * Supports multiple log levels (ERROR, INFO) and contextual information logging.
 * Provides thread-safe file writing with append mode.
 *
 * Features:
 * - Multiple log levels (ERROR, INFO, etc.)
 * - Automatic log rotation when file size exceeds threshold
 * - Contextual data logging with JSON encoding
 * - Configurable datetime format
 * - Thread-safe file operations
 * - Automatic directory creation
 * - Timestamp-based log rotation
 *
 * Default configuration:
 * - Log file: framework_error.log in Model directory
 * - Max file size: 5MB (5,242,880 bytes)
 * - Datetime format: Y-m-d H:i:s (configurable via config)
 *
 * Example usage:
 * ```php
 * $logger = new Logger('/path/to/app.log', 10485760);
 * $logger->logError('Database connection failed', ['host' => 'localhost']);
 * $logger->logInfo('User logged in', ['user_id' => 123]);
 * ```
 *
 * @category  Logging
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @version   1.0.0
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */
class Logger
{
    /**
     * Numeric severity ranking for each supported log level, lowest first.
     *
     * @var array<string,int>
     */
    private const LEVELS = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4,
    ];

    /**
     * The absolute path to the log file
     *
     * @var string
     */
    private string $logFile;

    /**
     * Minimum severity level that will be written to the log file.
     *
     * Entries below this level are silently discarded. Loaded from the
     * LOG_LEVEL configuration setting; defaults to DEBUG (log everything)
     * when unset.
     *
     * @var int
     */
    private int $minLevel;

    /**
     * The datetime format string used for log timestamps
     *
     * Uses PHP date() format. Can be customized via configuration file.
     * Default: 'Y-m-d H:i:s'
     *
     * @var string
     */
    private string $dateTimeFormat;

    /**
     * Maximum log file size in bytes before rotation occurs
     *
     * When the log file exceeds this size, it will be renamed with a
     * timestamp suffix and a new log file will be created.
     * Default: 5MB (5,242,880 bytes)
     *
     * @var int
     */
    private int $maxFileSize;

    /**
     * Initialize the logger with file path and rotation settings
     *
     * Creates a new Logger instance with specified log file path and maximum
     * file size for rotation. Automatically creates the log directory if it
     * doesn't exist and loads the datetime format from configuration.
     *
     * The logger will automatically rotate logs when the file size exceeds
     * maxFileSize, renaming the old file with a timestamp suffix.
     *
     * @param string $logFile     Absolute path to the log file. Defaults to
     *                            'framework_error.log' in the Model directory.
     * @param int    $maxFileSize Maximum file size in bytes before rotation.
     *                            Default is 5MB (5,242,880 bytes).
     *
     * @since 1.0.0
     */
    public function __construct(
        string $logFile = __DIR__ . '/framework_error.log',
        int $maxFileSize = 5242880
    ) {
        $this->logFile = $logFile;
        $this->maxFileSize = $maxFileSize;
        $this->ensureDirectoryExists();
        $this->loadDateTimeFormat();
        $this->loadMinLevel();
    }

    /**
     * Override the minimum log level at runtime.
     *
     * @param string $level One of DEBUG, INFO, WARNING, ERROR, CRITICAL (case-insensitive).
     *
     * @return self
     *
     * @since 1.0.0
     */
    public function setMinLevel(string $level): self
    {
        $normalized = strtoupper($level);

        if (isset(self::LEVELS[$normalized])) {
            $this->minLevel = self::LEVELS[$normalized];
        }

        return $this;
    }

    /**
     * Load the minimum log level from configuration
     *
     * Attempts to load the LOG_LEVEL setting from the compiled configuration
     * file. Falls back to DEBUG (log everything) if configuration is missing,
     * doesn't contain the setting, or the value isn't a recognized level.
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function loadMinLevel(): void
    {
        $this->minLevel = self::LEVELS['DEBUG'];

        $configFile = __DIR__ . '/../../Configuration/config_compiled.php';
        if (file_exists($configFile)) {
            $config = include $configFile;
            if (isset($config['LOG_LEVEL'])) {
                $normalized = strtoupper((string) $config['LOG_LEVEL']);
                if (isset(self::LEVELS[$normalized])) {
                    $this->minLevel = self::LEVELS[$normalized];
                }
            }
        }
    }

    /**
     * Load datetime format from configuration file
     *
     * Attempts to load the DATETIME_FORMAT setting from the compiled
     * configuration file. Falls back to 'Y-m-d H:i:s' if configuration
     * is missing or doesn't contain the format setting.
     *
     * This method prevents dependency loops by providing a simple fallback
     * mechanism when configuration files are not available.
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function loadDateTimeFormat(): void
    {
        // Simple fallback to avoid dependency loops if config is missing
        $this->dateTimeFormat = 'Y-m-d H:i:s';

        $configFile = __DIR__ . '/../../Configuration/config_compiled.php';
        if (file_exists($configFile)) {
            $config = include $configFile;
            if (isset($config['DATETIME_FORMAT'])) {
                $this->dateTimeFormat = $config['DATETIME_FORMAT'];
            }
        }
    }

    /**
     * Log an error message with optional context
     *
     * Writes an ERROR level log entry to the log file. This method should be
     * used for logging error conditions, exceptions, and critical issues that
     * require attention.
     *
     * The message will be formatted with timestamp, level, and any provided
     * context information. Context data is JSON-encoded for structured logging.
     *
     * Example:
     * ```php
     * $logger->logError('Database query failed', [
     *     'query' => 'SELECT * FROM users',
     *     'error' => $e->getMessage()
     * ]);
     * ```
     *
     * @param string               $message The error message to log
     * @param array<string,mixed> $context Optional associative array of contextual
     *                                      information (e.g., variables, state)
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @see log() For the underlying logging mechanism
     */
    public function logError(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * Log an informational message with optional context
     *
     * Writes an INFO level log entry to the log file. This method should be
     * used for logging general informational messages, user actions, and
     * system events that are useful for monitoring and debugging.
     *
     * The message will be formatted with timestamp, level, and any provided
     * context information. Context data is JSON-encoded for structured logging.
     *
     * Example:
     * ```php
     * $logger->logInfo('User login successful', [
     *     'user_id' => 123,
     *     'ip' => '192.168.1.1'
     * ]);
     * ```
     *
     * @param string               $message The informational message to log
     * @param array<string,mixed> $context Optional associative array of contextual
     *                                      information (e.g., user data, request info)
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @see log() For the underlying logging mechanism
     */
    public function logInfo(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * Log a debug message with optional context
     *
     * Writes a DEBUG level log entry, the lowest severity. Intended for
     * verbose, development-time diagnostics that are typically filtered
     * out in staging/production via the LOG_LEVEL configuration setting.
     *
     * @param string               $message The debug message to log
     * @param array<string,mixed> $context Optional contextual information
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @see log() For the underlying logging mechanism
     */
    public function logDebug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Log a warning message with optional context
     *
     * Writes a WARNING level log entry — for conditions that are not
     * errors but are worth investigating.
     *
     * @param string               $message The warning message to log
     * @param array<string,mixed> $context Optional contextual information
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @see log() For the underlying logging mechanism
     */
    public function logWarning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * Log a critical message with optional context
     *
     * Writes a CRITICAL level log entry, the highest severity — for
     * conditions requiring immediate attention (e.g. service outages).
     *
     * @param string               $message The critical message to log
     * @param array<string,mixed> $context Optional contextual information
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @see log() For the underlying logging mechanism
     */
    public function logCritical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    /**
     * Write a log entry to the log file
     *
     * Core logging method that handles the actual writing of log entries.
     * Checks for log rotation necessity, formats the log message with
     * timestamp, level, message, and context, then appends to the log file.
     *
     * Log format: [YYYY-MM-DD HH:MM:SS] [LEVEL] Message {"context":"data"}
     *
     * This method is thread-safe as it uses FILE_APPEND flag which performs
     * atomic append operations on most filesystems.
     *
     * @param string               $level   The log level (e.g., 'ERROR', 'INFO', 'WARNING')
     * @param string               $message The log message to write
     * @param array<string,mixed> $context Optional contextual data to include
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @see rotateLogIfNeeded() For automatic log rotation
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if ((self::LEVELS[$level] ?? 0) < $this->minLevel) {
            return;
        }

        $this->rotateLogIfNeeded();

        $date = date($this->dateTimeFormat);
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[$date] [$level] $message$contextStr" . PHP_EOL;

        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Check log file size and rotate if necessary
     *
     * Performs automatic log rotation when the current log file exceeds the
     * configured maximum file size. The existing log file is renamed with a
     * Unix timestamp suffix, and a new empty log file is created.
     *
     * Rotation process:
     * 1. Check if log file exists and size exceeds maxFileSize
     * 2. Rename current log file to: filename.log.TIMESTAMP
     * 3. Create a new empty log file
     *
     * Example rotation:
     * - Before: framework_error.log (5.5MB)
     * - After:  framework_error.log.1703345678 (5.5MB, archived)
     *           framework_error.log (0 bytes, new)
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function rotateLogIfNeeded(): void
    {
        if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxFileSize) {
            $backupFile = $this->logFile . '.' . time();
            rename($this->logFile, $backupFile);
            touch($this->logFile); // Create fresh file
        }
    }

    /**
     * Ensure the log file directory exists
     *
     * Creates the directory structure for the log file if it doesn't already
     * exist. Uses recursive directory creation with 0777 permissions (subject
     * to umask).
     *
     * This method is called during construction to guarantee the log file
     * can be written without directory-related errors.
     *
     * @return void
     *
     * @throws \RuntimeException If directory creation fails
     *
     * @since 1.0.0
     */
    private function ensureDirectoryExists(): void
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}
