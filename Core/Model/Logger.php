<?php
/*
 * COPYRIGHT (c) [SUMAN BANERJEE] - All Rights Reserved
 * SUMAN BANERJEE <contact@isumanbanerjee.com>
 * Project Name: FRAMEWORK
 * Created by: Suman Banerjee <contact@isumanbanerjee.com>.
 */

namespace Core\Model;

/**
 * Class Logger
 * Handles logging of error and informational messages to a log file.
 *
 * @package Core\Model
 */
class Logger
{
    /**
     * @var string The path to the log file.
     */
    private string $logFile;

    /**
     * @var string The date and time format for log entries.
     */
    private string $dateTimeFormat;

    /**
     * Logger constructor.
     *
     * @param string $logFile The path to the log file.
     */
    public function __construct(string $logFile = __DIR__ . '/framework_error.log')
    {
        $this->logFile = $logFile;
        if (!file_exists($this->logFile)) {
            touch($this->logFile);
        }
        $this->loadDateTimeFormat();
    }

    /**
     * Loads the date and time format from the configuration file.
     */
    private function loadDateTimeFormat(): void
    {
        if (file_exists(__DIR__ . '/../../Configuration/config_compiled.php')) {
            $config = include __DIR__ . '/../../Configuration/config_compiled.php';
        } else {
            $config = parse_ini_file(__DIR__ . '/../../Configuration/config.env');
        }
        $this->dateTimeFormat = $config['DATETIME_FORMAT'] ?? 'Y-m-d H:i:s';
    }

    /**
     * Logs an error message to the log file.
     *
     * @param string $message The error message to log.
     */
    public function logError(string $message): void
    {
        $this->log('ERROR', $message);
    }

    /**
     * Logs an informational message to the log file.
     *
     * @param string $message The informational message to log.
     */
    public function logInfo(string $message): void
    {
        $this->log('INFO', $message);
    }

    /**
     * Logs a message to the log file with the specified level.
     *
     * @param string $level The log level (e.g., ERROR, INFO).
     * @param string $message The message to log.
     */
    private function log(string $level, string $message): void
    {
        $date = date($this->dateTimeFormat);
        $logMessage = "[$date] [$level] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
}