<?php
/*
 * COPYRIGHT (c) [SUMAN BANERJEE] - All Rights Reserved
 * SUMAN BANERJEE <contact@isumanbanerjee.com>
 * Project Name: FRAMEWORK
 * Created by: Suman Banerjee <contact@isumanbanerjee.com>.
 */

namespace Core\Model;

use JetBrains\PhpStorm\NoReturn;

/**
 * Class Error
 * Handles error management and display for the application.
 *
 * @package Core\Model
 */
class Error
{
    /**
     * @var array The array of error codes and their corresponding messages.
     */
    private array $errorCodes;

    /**
     * Error constructor.
     * Initializes the error codes by loading them from a file.
     */
    public function __construct()
    {
        $this->loadErrorCodes();
    }

    /**
     * Loads error codes from a compiled PHP file or an environment file.
     */
    private function loadErrorCodes(): void
    {
        if (file_exists(__DIR__ . '/../../Configuration/error_compiled.php')) {
            $this->errorCodes = include __DIR__ . '/../../Configuration/error_compiled.php';
        } else {
            $this->errorCodes = parse_ini_file(__DIR__ . '/../../Configuration/error.env');
        }
    }

    /**
     * Terminates the script with an error message and a 500 HTTP response code.
     *
     * @param string $code The error code.
     */
    #[NoReturn] public function terminateWithError(string $code): void
    {
        $message = $this->getErrorMessage($code);
        http_response_code(500);
        die($message);
    }

    /**
     * Displays an error message.
     *
     * @param string $code The error code.
     */
    public function displayError(string $code): void
    {
        $message = $this->getErrorMessage($code);
        echo $message;
    }

    /**
     * Retrieves the error message corresponding to the given error code.
     *
     * @param string $code The error code.
     * @return string The error message.
     */
    private function getErrorMessage(string $code): string
    {
        return $this->errorCodes[$code] ?? 'Unknown error';
    }
}