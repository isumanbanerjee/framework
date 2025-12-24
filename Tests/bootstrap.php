<?php
/**
 * PHPUnit Bootstrap File
 *
 * This file is executed before running tests to set up the testing environment.
 */

// Load Composer's autoloader
require_once __DIR__ . '/../resources/vendor/autoload.php';

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define test constants
define('TEST_ENV', true);
define('TEST_ROOT', __DIR__);
define('PROJECT_ROOT', dirname(__DIR__));


// Mock $_SERVER for testing
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/';
}
if (!isset($_SERVER['HTTPS'])) {
    $_SERVER['HTTPS'] = 'off';
}

// Ensure session is not started (for testing)
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

