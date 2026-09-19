<?php

/**
 * Secure Session Manager with Flash Messages and CSRF Protection
 *
 * This file contains the Session class which provides comprehensive session
 * management, flash messaging system, and CSRF token protection with secure
 * configuration and automatic cleanup.
 *
 * PHP version 8.1
 *
 * @category  Security
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
 * Session Class
 *
 * Manages PHP sessions with enhanced security features including httpOnly
 * and secure cookies, CSRF token generation/validation, and a flash
 * messaging system for one-time user notifications.
 *
 * Features:
 * - Secure session configuration with httpOnly and secure flags
 * - Automatic session initialization
 * - Flash messaging system for temporary notifications
 * - CSRF token generation and validation
 * - Session regeneration to prevent fixation attacks
 * - Strict same-site cookie policy
 * - Configurable session parameters via App configuration
 * - Automatic flash message expiration
 *
 * Security features:
 * - HTTPOnly cookies to prevent XSS attacks
 * - Secure flag for HTTPS-only transmission
 * - SameSite=Strict to prevent CSRF attacks
 * - Session ID regeneration on privilege escalation
 * - Cryptographically secure CSRF tokens
 * - Timing-attack resistant token validation
 *
 * Flash message lifecycle:
 * - Set: Message stored with 'remove' flag = false
 * - Get: Message retrieved (flag unchanged)
 * - Age: Flag set to true on next request
 * - Age again: Message removed from session
 *
 * Configuration keys (in config.env):
 * - SESSION_FLASH_KEY: Key for storing flash messages (default: flash_messages)
 * - SESSION_CSRF_TOKEN_KEY: Key for CSRF token (default: csrf_token)
 *
 * Example usage:
 * ```php
 * $session = new Session();
 *
 * // Basic session operations
 * $session->set('user_id', 123);
 * $userId = $session->get('user_id');
 *
 * // Flash messages
 * $session->setFlash('success', 'Profile updated!');
 * $message = $session->getFlash('success'); // Next request
 *
 * // CSRF protection
 * $token = $session->generateCsrfToken();
 * if ($session->validateCsrfToken($_POST['csrf_token'])) {
 *     // Process form
 * }
 *
 * // Regenerate on login
 * $session->regenerate();
 *
 * // Destroy on logout
 * $session->destroy();
 * ```
 *
 * @category  Security
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */
class Session
{
    /**
     * Session key for storing flash messages array
     *
     * @var string
     */
    private string $flashKey;

    /**
     * Session key for storing CSRF token
     *
     * @var string
     */
    private string $csrfTokenKey;

    /**
     * Initialize secure session manager
     *
     * Constructs a new Session instance with secure configuration. Auto-
     * matically starts PHP session if not already active and configures
     * security parameters. Also performs cleanup of expired flash messages.
     * Loads configuration keys from App configuration.
     *
     * In test environments (CLI or TEST_ENV defined), uses relaxed
     * configuration to allow tests to run without errors.
     *
     * Initialization process:
     * 1. Loads configuration from App
     * 2. Checks if session is already started
     * 3. Configures secure session parameters (or test mode)
     * 4. Starts session with configured settings
     * 5. Removes expired flash messages
     *
     * Security configuration:
     * - HTTPOnly cookies (prevents JavaScript access)
     * - Secure cookies (HTTPS only when available)
     * - SameSite=Strict (prevents CSRF)
     * - Strict mode enabled
     * - Use only cookies (no URL parameters)
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        try {
            // Load configuration keys from App
            $this->flashKey = App::config('SESSION_FLASH_KEY', 'flash_messages');
            $this->csrfTokenKey = App::config('SESSION_CSRF_TOKEN_KEY', 'csrf_token');

            // Check if we're in test environment
            $isTestEnv = defined('TEST_ENV') || php_sapi_name() === 'cli';

            if (session_status() === PHP_SESSION_NONE) {
                if ($isTestEnv) {
                    // Relaxed configuration for tests
                    @session_start();
                } else {
                    // Full security configuration for production
                    $this->configureSession();

                    if (!session_start()) {
                        throw new Exception('Failed to start session');
                    }
                }
            }

            // Remove expired flash messages
            $this->ageFlashMessages();
        } catch (Exception $e) {
            // In test environment, don't terminate - just log
            if (defined('TEST_ENV') || php_sapi_name() === 'cli') {
                error_log('Session initialization warning: ' . $e->getMessage());
                return;
            }

            $error = new Error();
            $error->terminateWithError(
                'SESSION_INITIALIZATION_FAILED',
                'Session initialization failed: ' . $e->getMessage(),
                Error::SEVERITY_CRITICAL
            );
        }
    }

    /**
     * Configure secure session cookie parameters
     *
     * Sets PHP session configuration for maximum security. Configures
     * cookie parameters to prevent common session attacks including
     * XSS, CSRF, and session hijacking.
     *
     * Configuration applied:
     * - use_only_cookies: Prevents session ID in URLs
     * - use_strict_mode: Rejects uninitialized session IDs
     * - lifetime: 0 (session cookie, expires on browser close)
     * - httponly: true (prevents JavaScript access)
     * - secure: auto-detected based on HTTPS
     * - samesite: Strict (maximum CSRF protection)
     *
     * @return void
     *
     * @internal
     * @since 1.0.0
     */
    private function configureSession(): void
    {
        try {
            // In test environment, skip configuration that fails in CLI
            if (defined('TEST_ENV') || php_sapi_name() === 'cli') {
                return;
            }

            // ini_set() returns the *old* value on success, or false on
            // failure - a falsy old value (e.g. "0") must not be mistaken
            // for failure, so check explicitly against false.
            if (ini_set('session.use_only_cookies', '1') === false) {
                throw new Exception('Failed to set session.use_only_cookies');
            }

            if (ini_set('session.use_strict_mode', '1') === false) {
                throw new Exception('Failed to set session.use_strict_mode');
            }

            $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        } catch (Exception $e) {
            // In test environment, just log the error
            if (defined('TEST_ENV') || php_sapi_name() === 'cli') {
                error_log('Session configuration warning: ' . $e->getMessage());
                return;
            }

            $error = new Error();
            $error->terminateWithError(
                'SESSION_CONFIGURATION_FAILED',
                'Session configuration failed: ' . $e->getMessage(),
                Error::SEVERITY_CRITICAL
            );
        }
    }

    /**
     * Store value in session
     *
     * Sets or updates a value in the session storage using the specified
     * key. Supports any serializable PHP data type.
     *
     * @param string $key   Session key identifier
     * @param mixed  $value Value to store (must be serializable)
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $session->set('user_id', 123);
     * $session->set('preferences', ['theme' => 'dark']);
     * $session->set('cart', $cartObject);
     * ```
     */
    public function set(string $key, mixed $value): void
    {
        try {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                throw new Exception('Session is not active');
            }

            $_SESSION[$key] = $value;
        } catch (Exception $e) {
            $error = new Error();
            $error->terminateWithError(
                'SESSION_SET_FAILED',
                'Failed to set session value: ' . $e->getMessage(),
                Error::SEVERITY_ERROR
            );
        }
    }

    /**
     * Retrieve value from session
     *
     * Gets a value from session storage by key. Returns default value
     * if key doesn't exist, allowing safe retrieval without checking.
     *
     * @param string $key     Session key to retrieve
     * @param mixed  $default Value returned if key doesn't exist
     *
     * @return mixed Stored value or default if not found
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $userId = $session->get('user_id', 0);
     * $theme = $session->get('theme', 'light');
     * $cart = $session->get('cart', []);
     * ```
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session key exists
     *
     * Determines whether a specific key is present in the session,
     * regardless of its value (including null).
     *
     * @param string $key Session key to check
     *
     * @return bool True if key exists in session, false otherwise
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * if ($session->has('user_id')) {
     *     // User is logged in
     * }
     * ```
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove key from session
     *
     * Deletes a specific key and its value from the session storage.
     * No effect if key doesn't exist.
     *
     * @param string $key Session key to remove
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $session->remove('temp_data');
     * $session->remove('wizard_step');
     * ```
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Regenerate session identifier
     *
     * Creates a new session ID while preserving session data. Critical
     * security measure to prevent session fixation attacks. Should be
     * called whenever user privilege level changes (login, permission
     * elevation, etc.).
     *
     * Security benefit:
     * - Invalidates old session ID
     * - Prevents session fixation attacks
     * - Maintains all session data
     * - Deletes old session file
     *
     * Call this method:
     * - After successful login
     * - When user role/permissions change
     * - Periodically for sensitive operations
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // On successful login
     * if ($auth->verifyCredentials()) {
     *     $session->regenerate();
     *     $session->set('user_id', $userId);
     * }
     * ```
     */
    public function regenerate(): void
    {
        try {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                throw new Exception('Session is not active');
            }

            if (!session_regenerate_id(true)) {
                throw new Exception('Failed to regenerate session ID');
            }
        } catch (Exception $e) {
            $error = new Error();
            $error->terminateWithError(
                'SESSION_REGENERATE_FAILED',
                'Failed to regenerate session: ' . $e->getMessage(),
                Error::SEVERITY_ERROR
            );
        }
    }

    /**
     * Destroy session completely
     *
     * Terminates the current session, removes all session data, deletes
     * session cookie, and destroys session file. Use this for user logout
     * or complete session cleanup.
     *
     * Destruction process:
     * 1. Clears all session variables
     * 2. Removes session cookie from browser
     * 3. Destroys server-side session file
     *
     * After calling this method, the session is completely terminated
     * and cannot be recovered.
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // User logout
     * $session->destroy();
     * header('Location: /login');
     * exit;
     * ```
     */
    public function destroy(): void
    {
        try {
            $_SESSION = [];

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            if (!session_destroy()) {
                throw new Exception('Failed to destroy session');
            }
        } catch (Exception $e) {
            $error = new Error();
            $error->terminateWithError(
                'SESSION_DESTROY_FAILED',
                'Failed to destroy session: ' . $e->getMessage(),
                Error::SEVERITY_ERROR
            );
        }
    }

    // ---------------------------------------------------------------------
    // Flash Message Handling
    // ---------------------------------------------------------------------

    /**
     * Set flash message for next request
     *
     * Stores a temporary message that will be available on the next
     * request and automatically removed after being displayed. Perfect
     * for showing success/error messages after form submissions or
     * redirects.
     *
     * Flash message lifecycle:
     * - Current request: Set with remove=false
     * - Next request: Available for reading, marked remove=true
     * - Following request: Automatically removed
     *
     * Common keys:
     * - 'success': Success notifications
     * - 'error': Error messages
     * - 'warning': Warning messages
     * - 'info': Informational messages
     *
     * @param string $key     Message category/type identifier
     * @param string $message Message content to display
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // After form submission
     * $session->setFlash('success', 'Profile updated successfully!');
     * header('Location: /profile');
     *
     * // Error message
     * $session->setFlash('error', 'Invalid input provided');
     * ```
     */
    public function setFlash(string $key, string $message): void
    {
        $_SESSION[$this->flashKey][$key] = [
            'message' => $message,
            'remove' => false,
        ];
    }

    /**
     * Retrieve flash message
     *
     * Gets a flash message by key without removing it. Message will be
     * automatically removed after the current request completes.
     * Returns null if no message exists for the specified key.
     *
     * Note: This method doesn't remove the message immediately. Removal
     * happens automatically via the aging mechanism at the end of the
     * next request.
     *
     * @param string $key Message category/type identifier
     *
     * @return string|null Message content or null if not found
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // In template/view
     * $successMsg = $session->getFlash('success');
     * if ($successMsg) {
     *     echo "<div class='alert-success'>$successMsg</div>";
     * }
     * ```
     */
    public function getFlash(string $key): ?string
    {
        return $_SESSION[$this->flashKey][$key]['message'] ?? null;
    }

    /**
     * Age flash messages for automatic cleanup
     *
     * Internal method that manages flash message lifecycle. Marks
     * messages for removal after one request cycle and deletes
     * messages marked in previous cycle.
     *
     * Aging logic:
     * - New messages: remove = false
     * - After one request: remove = true
     * - After two requests: deleted
     *
     * This ensures messages survive exactly one full request cycle
     * after being set.
     *
     * @return void
     *
     * @internal
     * @since 1.0.0
     */
    private function ageFlashMessages(): void
    {
        $flashMessages = $_SESSION[$this->flashKey] ?? [];

        foreach ($flashMessages as $key => &$flashMessage) {
            if ($flashMessage['remove']) {
                unset($flashMessages[$key]);
            } else {
                $flashMessage['remove'] = true;
            }
        }

        $_SESSION[$this->flashKey] = $flashMessages;
    }

    // ---------------------------------------------------------------------
    // CSRF Protection
    // ---------------------------------------------------------------------

    /**
     * Generate CSRF protection token
     *
     * Creates or retrieves a cryptographically secure CSRF token for
     * the current session. Token is generated once per session and
     * reused for all forms. Use this token in forms to prevent Cross-
     * Site Request Forgery attacks.
     *
     * Token characteristics:
     * - 64 characters (32 bytes) hexadecimal
     * - Cryptographically secure via random_bytes()
     * - Persistent for entire session
     * - One token per session (not per-form)
     *
     * Implementation:
     * 1. Check if token already exists
     * 2. Generate new secure token if needed
     * 3. Store in session
     * 4. Return token for form inclusion
     *
     * @return string 64-character hexadecimal CSRF token
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // In form template
     * $csrfToken = $session->generateCsrfToken();
     * echo "<input type='hidden' name='csrf_token'
     *             value='$csrfToken'>";
     * ```
     */
    public function generateCsrfToken(): string
    {
        try {
            if (!$this->has($this->csrfTokenKey)) {
                $token = bin2hex(random_bytes(32));
                if ($token === false || strlen($token) !== 64) {
                    throw new Exception('Failed to generate secure random token');
                }
                $this->set($this->csrfTokenKey, $token);
            }
            return $this->get($this->csrfTokenKey);
        } catch (Exception $e) {
            $error = new Error();
            $error->terminateWithError(
                'CSRF_TOKEN_GENERATION_FAILED',
                'Failed to generate CSRF token: ' . $e->getMessage(),
                Error::SEVERITY_CRITICAL
            );
        }
    }

    /**
     * Validate CSRF token from form submission
     *
     * Verifies that the provided token matches the session CSRF token.
     * Uses timing-attack resistant comparison via hash_equals().
     * Returns false if token is missing, empty, or doesn't match.
     *
     * Security features:
     * - Timing-attack resistant comparison
     * - Null-safe validation
     * - Session token verification
     *
     * Usage pattern:
     * 1. Generate token when rendering form
     * 2. Include token as hidden field
     * 3. Validate token on form submission
     * 4. Reject request if validation fails
     *
     * @param string|null $token Token from form submission to validate
     *
     * @return bool True if token valid, false otherwise
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // Form processing
     * if (!$session->validateCsrfToken($_POST['csrf_token'])) {
     *     die('Invalid CSRF token');
     * }
     *
     * // Process form data safely
     * processFormData($_POST);
     * ```
     */
    public function validateCsrfToken(?string $token): bool
    {
        if (!$token || !$this->has($this->csrfTokenKey)) {
            return false;
        }
        return hash_equals($this->get($this->csrfTokenKey), $token);
    }
}
