<?php

/**
 * User Authentication and Authorization Manager
 *
 * This file contains the Auth class which provides comprehensive user
 * authentication, registration, session management, and remember-me
 * functionality with secure password hashing.
 *
 * PHP version 8.1
 *
 * @category  Authentication
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */

declare(strict_types=1);

namespace Core\Model;

use Core\Model\Database\Database;
use Exception;

/**
 * Auth Class
 *
 * Comprehensive authentication manager providing secure user login,
 * registration, session management, and persistent authentication
 * through remember-me tokens.
 *
 * Features:
 * - Secure password hashing using PHP's password_hash()
 * - Session-based authentication with regeneration
 * - Persistent "Remember Me" functionality
 * - Configurable database table and column names via App configuration
 * - Force login by user ID
 * - Session fixation prevention
 * - Secure cookie handling with httpOnly and secure flags
 *
 * Security measures:
 * - Passwords are hashed using PASSWORD_DEFAULT algorithm
 * - Remember tokens are SHA-256 hashed before storage
 * - Session regeneration on login to prevent fixation
 * - Secure and httpOnly cookies for remember-me tokens
 * - Token-based persistent authentication
 *
 * Configuration keys (from config.env):
 * - AUTH_TABLE: Database table name (default: users)
 * - AUTH_PRIMARY_KEY: Primary key column (default: id)
 * - AUTH_IDENTITY_COLUMN: Identity column (default: email)
 * - AUTH_PASSWORD_COLUMN: Password column (default: password)
 * - AUTH_REMEMBER_TOKEN_COLUMN: Token column (default: remember_token)
 * - AUTH_REMEMBER_DURATION: Remember duration in seconds (default: 2592000 = 30 days)
 *
 * Example usage:
 * ```php
 * $auth = new Auth($database, $session);
 *
 * // Login user
 * if ($auth->login('user@example.com', 'password', true)) {
 *     echo "Logged in successfully";
 * }
 *
 * // Register new user
 * $userId = $auth->register([
 *     'email' => 'new@example.com',
 *     'password' => 'securepass',
 *     'name' => 'John Doe'
 * ]);
 *
 * // Check if authenticated
 * if ($auth->check()) {
 *     $user = $auth->user();
 * }
 *
 * // Logout
 * $auth->logout();
 * ```
 *
 * @category  Authentication
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */
class Auth
{
    /**
     * Database instance for executing queries
     *
     * @var Database
     */
    private Database $db;

    /**
     * Session instance for managing user sessions
     *
     * @var Session
     */
    private Session $session;

    /**
     * Database table name storing user records
     *
     * @var string
     */
    private string $table;

    /**
     * Primary key column name in the users table
     *
     * @var string
     */
    private string $primaryKey;

    /**
     * Column name for user identity (email/username)
     *
     * @var string
     */
    private string $identityColumn;

    /**
     * Column name for user password hash
     *
     * @var string
     */
    private string $passwordColumn;

    /**
     * Column name for remember-me token hash
     *
     * @var string
     */
    private string $rememberTokenColumn;

    /**
     * Remember-me token duration in seconds
     *
     * @var int
     */
    private int $rememberDuration;

    /**
     * Rate limiter guarding login() against brute-force attempts
     *
     * Keyed per identity+IP pair; defaults to the 'login' preset (5
     * attempts per minute, see {@see RateLimit::for()}).
     *
     * @var RateLimit
     */
    private RateLimit $rateLimiter;

    /**
     * Initialize the authentication manager
     *
     * Constructs a new Auth instance with required dependencies for
     * database operations and session management. Loads configuration
     * from App configuration manager.
     *
     * @param Database $db      Database instance for user data queries
     * @param Session  $session Session instance for auth state mgmt
     *
     * @since 1.0.0
     */
    public function __construct(Database $db, Session $session)
    {
        try {
            $this->db = $db;
            $this->session = $session;

            // Load configuration from App
            $this->table = App::config('AUTH_TABLE', 'users');
            $this->primaryKey = App::config('AUTH_PRIMARY_KEY', 'id');
            $this->identityColumn = App::config('AUTH_IDENTITY_COLUMN', 'email');
            $this->passwordColumn = App::config('AUTH_PASSWORD_COLUMN', 'password');
            $this->rememberTokenColumn = App::config('AUTH_REMEMBER_TOKEN_COLUMN', 'remember_token');
            $this->rememberDuration = (int)App::config('AUTH_REMEMBER_DURATION', 2592000);
            $this->rateLimiter = RateLimit::for('login');

            // Validate configuration
            if (empty($this->table) || empty($this->primaryKey)) {
                throw new Exception('Invalid auth configuration: table and primary key required');
            }
        } catch (Exception $e) {
            $error = new Error();
            $error->terminateWithError(
                'AUTH_INITIALIZATION_FAILED',
                'Auth initialization failed: ' . $e->getMessage(),
                Error::SEVERITY_CRITICAL
            );
        }
    }

    /**
     * Authenticate user with credentials
     *
     * Attempts to authenticate a user using their identity (email/username)
     * and password. Verifies credentials, creates session, and optionally
     * sets a persistent remember-me token.
     *
     * Process flow:
     * 1. Fetches user record by identity column
     * 2. Verifies password against stored hash
     * 3. Creates authenticated session on success
     * 4. Generates remember-me token if requested
     *
     * Security features:
     * - Uses password_verify for timing-attack resistant verification
     * - Regenerates session ID to prevent fixation
     * - Creates secure httpOnly remember-me cookie
     * - Stores SHA-256 hashed token in database
     *
     * @param string $identity User's email or username for authentication
     * @param string $password Plain text password to verify
     * @param bool   $remember Whether to create persistent login token
     *                         (30 day duration)
     *
     * @return bool True if authentication successful, false otherwise
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // Simple login
     * $success = $auth->login('user@example.com', 'password123');
     *
     * // Login with remember me
     * $success = $auth->login('user@example.com', 'pass', true);
     * ```
     */
    public function login(
        string $identity,
        string $password,
        bool $remember = false
    ): bool {
        try {
            // Validate inputs
            if (empty($identity) || empty($password)) {
                throw new Exception('Identity and password are required');
            }

            $throttleKey = $this->throttleKey($identity);

            if ($this->rateLimiter->tooManyAttempts($throttleKey)) {
                $error = new Error();
                $error->terminateWithError(
                    'RATE_LIMIT_EXCEEDED',
                    'Too many login attempts',
                    Error::SEVERITY_WARNING,
                    ['identity' => $identity, 'retry_after' => $this->rateLimiter->availableIn($throttleKey)]
                );
            }

            // 1. Fetch user by identity
            $query = "SELECT * FROM {$this->table} " .
                     "WHERE {$this->identityColumn} = :identity LIMIT 1";
            $user = $this->db->fetchOneNamed($query, [':identity' => $identity]);

            if (!$user) {
                $this->rateLimiter->hit($throttleKey);
                return false;
            }

            // 2. Verify Password
            if (!password_verify($password, $user[$this->passwordColumn])) {
                $this->rateLimiter->hit($throttleKey);
                return false;
            }

            // 3. Login Success: Set Session
            $this->rateLimiter->resetAttempts($throttleKey);
            $this->loginUser($user);

            // 4. Handle "Remember Me"
            if ($remember) {
                $this->createRememberToken($user[$this->primaryKey]);
            }

            return true;
        } catch (Exception $e) {
            $error = new Error();
            $error->terminateWithError(
                'AUTH_LOGIN_FAILED',
                'Login failed: ' . $e->getMessage(),
                Error::SEVERITY_ERROR,
                ['identity' => $identity]
            );
        }
    }

    /**
     * Set a custom rate limiter for login() throttling.
     *
     * @param RateLimit $rateLimiter Rate limiter to guard login attempts with.
     *
     * @return self
     */
    public function setRateLimiter(RateLimit $rateLimiter): self
    {
        $this->rateLimiter = $rateLimiter;
        return $this;
    }

    /**
     * Remaining login attempts before the given identity is throttled.
     *
     * @param string $identity User's email or username as passed to login().
     *
     * @return int
     */
    public function loginAttemptsRemaining(string $identity): int
    {
        return $this->rateLimiter->retriesLeft($this->throttleKey($identity));
    }

    /**
     * Build the rate limiter key for a login attempt, scoped to identity + client IP.
     *
     * @param string $identity
     *
     * @return string
     */
    private function throttleKey(string $identity): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        return 'login:' . strtolower($identity) . '|' . $ip;
    }

    /**
     * Force authentication by user identifier
     *
     * Authenticates a user directly using their unique identifier without
     * password verification. Useful for automated logins, user
     * impersonation by admins, or post-registration auto-login.
     *
     * Warning: This bypasses password verification. Use only in trusted
     * contexts where user identity is already verified through other means.
     *
     * Process flow:
     * 1. Fetches user record by primary key
     * 2. Creates authenticated session if user exists
     *
     * Use cases:
     * - Post-registration automatic login
     * - Administrative user impersonation
     * - Token-based authentication systems
     * - Social login providers
     *
     * @param int|string $id User's primary key identifier
     *
     * @return bool True if user found and logged in, false otherwise
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // Login user after registration
     * $userId = $auth->register($userData);
     * $auth->loginById($userId);
     *
     * // Admin impersonation
     * if ($currentUser->isAdmin()) {
     *     $auth->loginById($targetUserId);
     * }
     * ```
     */
    public function loginById(int|string $id): bool
    {
        $query = "SELECT * FROM {$this->table} " .
                 "WHERE {$this->primaryKey} = :id LIMIT 1";
        $user = $this->db->fetchOneNamed($query, [':id' => $id]);

        if ($user) {
            $this->loginUser($user);
            return true;
        }
        return false;
    }

    /**
     * Check whether a user already exists for the given identity
     *
     * Useful for pre-flight duplicate checks before calling register(),
     * e.g. to show a friendly "email already taken" message instead of
     * letting the INSERT fail on a unique constraint.
     *
     * @param string $identity User's email/username, as used by login()
     *
     * @return bool True if a matching user row exists
     *
     * @since 1.0.0
     */
    public function identityExists(string $identity): bool
    {
        $query = "SELECT {$this->primaryKey} FROM {$this->table} " .
                 "WHERE {$this->identityColumn} = :identity LIMIT 1";

        return (bool) $this->db->fetchOneNamed($query, [':identity' => $identity]);
    }

    /**
     * Register a new user account
     *
     * Creates a new user record with automatic password hashing. Accepts
     * an associative array of column-value pairs to insert into the user
     * table. Password field is automatically detected and hashed securely.
     *
     * Process flow:
     * 1. Detects password field and hashes it using PASSWORD_DEFAULT
     * 2. Constructs INSERT query dynamically from data array
     * 3. Executes insert operation
     * 4. Returns newly created user's identifier
     *
     * Password handling:
     * - Automatically hashes password using password_hash()
     * - Uses PASSWORD_DEFAULT algorithm (currently bcrypt)
     * - Cost factor managed by PHP defaults
     *
     * @param array $data Associative array of column => value pairs
     *                    Must include password field for hashing
     *                    Example: ['email'=>'user@test.com','password'=>'pass']
     *
     * @return bool|int Returns new user ID on success, false on failure
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // Basic registration
     * $userId = $auth->register([
     *     'email' => 'newuser@example.com',
     *     'password' => 'securePassword123',
     *     'name' => 'John Doe',
     *     'created_at' => date('Y-m-d H:i:s')
     * ]);
     *
     * if ($userId) {
     *     echo "User registered with ID: $userId";
     * }
     * ```
     */
    public function register(array $data): bool|int
    {
        try {
            // Validate input
            if (empty($data)) {
                throw new Exception('Registration data cannot be empty');
            }

            // Hash the password automatically if present
            if (isset($data[$this->passwordColumn])) {
                $hashedPassword = password_hash(
                    $data[$this->passwordColumn],
                    PASSWORD_DEFAULT
                );

                if ($hashedPassword === false) {
                    throw new Exception('Failed to hash password');
                }

                $data[$this->passwordColumn] = $hashedPassword;
            }

            $columns = array_keys($data);
            $values = array_values($data);

            // Construct INSERT query
            $columnList = implode(', ', $columns);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));

            $query = "INSERT INTO {$this->table} ($columnList) " .
                     "VALUES ($placeholders)";

            if ($this->db->executeQuery($query, $values)) {
                // Retrieve the last inserted ID
                $lastId = $this->db->fetchOne('SELECT LAST_INSERT_ID() as id');
                return $lastId['id'] ?? true;
            }

            throw new Exception('Failed to execute registration query');
        } catch (Exception $e) {
            $error = new Error();
            $error->terminateWithError(
                'AUTH_REGISTRATION_FAILED',
                'Registration failed: ' . $e->getMessage(),
                Error::SEVERITY_ERROR
            );
        }
    }

    /**
     * Terminate user session and clear authentication
     *
     * Logs out the currently authenticated user by destroying session data,
     * removing remember-me tokens, and clearing authentication cookies.
     * Ensures complete cleanup of all authentication artifacts.
     *
     * Cleanup process:
     * 1. Clears remember token from database
     * 2. Removes remember_me cookie
     * 3. Destroys session and all session data
     *
     * Security considerations:
     * - Removes all traces of authentication
     * - Invalidates persistent login tokens
     * - Prevents session hijacking post-logout
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // Simple logout
     * $auth->logout();
     *
     * // Redirect after logout
     * $auth->logout();
     * header('Location: /login');
     * ```
     */
    public function logout(): void
    {
        try {
            // Clear Remember Me Token from DB if it exists
            if ($this->check()) {
                $id = $this->id();
                $this->db->executeQuery(
                    "UPDATE {$this->table} " .
                    "SET {$this->rememberTokenColumn} = NULL " .
                    "WHERE {$this->primaryKey} = ?",
                    [$id]
                );
            }

            // Remove Remember Me Cookie
            if (isset($_COOKIE['remember_me'])) {
                if (!setcookie('remember_me', '', time() - 3600, '/')) {
                    throw new Exception('Failed to remove remember_me cookie');
                }
            }

            // Destroy Session
            $this->session->destroy();
        } catch (Exception $e) {
            $error = new Error();
            $error->terminateWithError(
                'AUTH_LOGOUT_FAILED',
                'Logout failed: ' . $e->getMessage(),
                Error::SEVERITY_ERROR
            );
        }
    }

    /**
     * Verify if user is currently authenticated
     *
     * Checks whether a user is currently logged in by examining session
     * data or validating remember-me token. Provides dual-layer
     * authentication verification.
     *
     * Verification process:
     * 1. Checks for active session with user ID
     * 2. Falls back to remember-me token validation
     * 3. Auto-logs in user if valid token exists
     *
     * @return bool True if user is authenticated, false otherwise
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * if ($auth->check()) {
     *     // User is logged in
     *     $user = $auth->user();
     * } else {
     *     // Redirect to login
     *     header('Location: /login');
     * }
     * ```
     */
    public function check(): bool
    {
        if ($this->session->has('auth_user_id')) {
            return true;
        }

        // Check for Remember Me cookie if session is expired
        return $this->checkRememberToken();
    }

    /**
     * Retrieve authenticated user's identifier
     *
     * Returns the primary key value of the currently authenticated user.
     * Returns null if no user is authenticated.
     *
     * @return int|string|null User's primary key or null if not authenticated
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $userId = $auth->id();
     * if ($userId) {
     *     echo "Current user ID: $userId";
     * }
     * ```
     */
    public function id(): int|string|null
    {
        return $this->session->get('auth_user_id');
    }

    /**
     * Retrieve authenticated user's complete data
     *
     * Fetches all database fields for the currently authenticated user.
     * Returns null if no user is authenticated or user record not found.
     *
     * @return array|null Associative array of user data or null
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $user = $auth->user();
     * if ($user) {
     *     echo "Welcome, " . $user['name'];
     *     echo "Email: " . $user['email'];
     * }
     * ```
     */
    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        $id = $this->id();
        $query = "SELECT * FROM {$this->table} " .
                 "WHERE {$this->primaryKey} = :id LIMIT 1";
        return $this->db->fetchOneNamed($query, [':id' => $id]);
    }

    // ---------------------------------------------------------------------
    // Internal Helper Methods
    // ---------------------------------------------------------------------

    /**
     * Create authenticated session for user
     *
     * Internal method to establish user session with security measures.
     * Regenerates session ID and stores user authentication data.
     *
     * @param array $user User record from database
     *
     * @return void
     *
     * @internal
     * @since 1.0.0
     */
    private function loginUser(array $user): void
    {
        $this->session->regenerate(); // Prevent session fixation
        $this->session->set('auth_user_id', $user[$this->primaryKey]);
        $this->session->set('auth_user_email', $user[$this->identityColumn]);
        $this->session->set('last_activity', time());
    }

    /**
     * Generate and store remember-me token
     *
     * Creates a cryptographically secure token, hashes it, stores hash
     * in database, and sets cookie with original token using configured
     * duration from App configuration.
     *
     * @param int|string $userId User's primary key identifier
     *
     * @return void
     *
     * @internal
     * @since 1.0.0
     */
    private function createRememberToken(int|string $userId): void
    {
        try {
            $token = bin2hex(random_bytes(32));
            if (strlen($token) !== 64) {
                throw new Exception('Failed to generate secure token');
            }

            $hash = hash('sha256', $token);

            // Store hash in DB
            $result = $this->db->executeQuery(
                "UPDATE {$this->table} " .
                "SET {$this->rememberTokenColumn} = ? " .
                "WHERE {$this->primaryKey} = ?",
                [$hash, $userId]
            );

            if (!$result) {
                throw new Exception('Failed to store remember token in database');
            }

            // Store token in cookie with configured duration
            $cookieValue = "$userId|$token";
            $cookieSet = setcookie(
                'remember_me',
                $cookieValue,
                time() + $this->rememberDuration,
                '/',
                '',
                true,
                true
            );

            if (!$cookieSet) {
                throw new Exception('Failed to set remember_me cookie');
            }
        } catch (Exception $e) {
            $error = new Error();
            $error->terminateWithError(
                'AUTH_REMEMBER_TOKEN_FAILED',
                'Failed to create remember token: ' . $e->getMessage(),
                Error::SEVERITY_ERROR
            );
        }
    }

    /**
     * Validate remember-me token and auto-login
     *
     * Checks for valid remember-me cookie, validates token against
     * database hash, and automatically logs in user if valid.
     *
     * @return bool True if token valid and user logged in, false otherwise
     *
     * @internal
     * @since 1.0.0
     */
    private function checkRememberToken(): bool
    {
        if (!isset($_COOKIE['remember_me'])) {
            return false;
        }

        [$userId, $token] = explode('|', $_COOKIE['remember_me'], 2)
                            + [null, null];

        if (!$userId || !$token) {
            return false;
        }

        $hash = hash('sha256', $token);

        $query = "SELECT * FROM {$this->table} " .
                 "WHERE {$this->primaryKey} = :id " .
                 "AND {$this->rememberTokenColumn} = :token LIMIT 1";
        $user = $this->db->fetchOneNamed(
            $query,
            [':id' => $userId, ':token' => $hash]
        );

        if ($user) {
            $this->loginUser($user);
            return true;
        }

        return false;
    }
}
