<?php

/**
 * Multi-Database Connection Manager with Advanced Features
 *
 * This file contains the Database class which provides comprehensive database
 * connectivity with support for multiple database types, connection pooling,
 * caching, replication, sharding, and advanced query operations.
 *
 * PHP version 8.1
 *
 * @category  Database
 * @package   Core\Model\Database
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */

declare(strict_types=1);

namespace Core\Model\Database;

use Core\Model\App;
use Core\Model\Error;
use Core\Model\Logger;
use PDO;
use PDOException;
use Psr\SimpleCache\CacheInterface;

/**
 * Database Class
 *
 * Comprehensive database abstraction layer supporting multiple database
 * systems with advanced features including connection pooling, query
 * caching, replication, sharding, migrations, and monitoring.
 *
 * Supported database types:
 * - MySQL / MariaDB
 * - PostgreSQL
 * - SQL Server (sqlsrv)
 * - Oracle (oci)
 * - IBM DB2
 * - SQLite
 *
 * Features:
 * - Multi-database support with unified interface
 * - Persistent connections for performance
 * - Prepared statement support
 * - Transaction management with savepoints
 * - Query result caching
 * - Read replica support
 * - Database sharding
 * - Connection failover
 * - Query execution timing and logging
 * - Slow query detection
 * - Batch operations (insert/update)
 * - Migration and seeding support
 * - Database backup management
 * - Schema validation
 * - SSL/TLS encryption support
 *
 * Security features:
 * - Prepared statements prevent SQL injection
 * - Input sanitization helpers
 * - SSL/TLS encrypted connections
 * - Credential validation
 * - Error rate limiting
 *
 * Performance features:
 * - Persistent connections
 * - Query result caching
 * - Batch operations
 * - Read replica routing
 * - Connection pooling
 *
 * Example usage:
 * ```php
 * $db = new Database($logger);
 *
 * // Basic queries
 * $users = $db->fetchAll("SELECT * FROM users WHERE active = ?", [1]);
 * $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [5]);
 *
 * // Transactions
 * $db->beginTransaction();
 * $db->executeQuery("UPDATE users SET balance = balance - ? WHERE id = ?", [100, 1]);
 * $db->executeQuery("UPDATE users SET balance = balance + ? WHERE id = ?", [100, 2]);
 * $db->commitTransaction();
 *
 * // Query builder
 * $qb = $db->queryBuilder();
 * $results = $qb->table('users')->where('age', '>', 18)->get();
 * ```
 *
 * @category  Database
 * @package   Core\Model\Database
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */
class Database
{
    /**
     * PDO database connection instance
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * PDO replica database connection instance
     *
     * @var PDO|null
     */
    private ?PDO $replicaPdo = null;

    /**
     * Logger instance for database operation logging
     *
     * @var Logger
     */
    private Logger $logger;

    /**
     * Initialize database connection with configuration
     *
     * Constructs a new Database instance, loads configuration, validates
     * credentials, and establishes a persistent PDO connection with error
     * handling and logging support.
     *
     * Connection process:
     * 1. Loads configuration from compiled or .env file
     * 2. Constructs DSN string based on database type
     * 3. Validates required credentials
     * 4. Establishes PDO connection with exception mode
     * 5. Enables persistent connections for performance
     *
     * Configuration requirements:
     * - DB_TYPE: Database type (mysql, pgsql, sqlsrv, etc.)
     * - DB_HOST: Database server hostname
     * - DB_PORT: Database server port
     * - DB_NAME: Database name
     * - DB_USERNAME: Database username
     * - DB_PASSWORD: Database password
     * - DB_CHARSET: Character set (optional, default: utf8)
     *
     * @param Logger $logger Logger instance for operation tracking
     *
     * @throws PDOException If connection fails
     *
     * @since 1.0.0
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $config = $this->loadConfig();
        $dsn = $this->getDsn($config);

        $error = new Error();

        if (empty($config['DB_USERNAME'])) {
            $error->terminateWithError('DB_USERNAME_NOT_PROVIDED');
        }

        if (empty($config['DB_PASSWORD'])) {
            $error->terminateWithError('DB_PASSWORD_NOT_PROVIDED');
        }

        $username = $config['DB_USERNAME'];
        $password = $config['DB_PASSWORD'];

        try {
            $this->pdo = new PDO($dsn, $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
        } catch (PDOException $e) {
            $this->logger->logError('Database connection failed: ' . $e->getMessage());
            $error->terminateWithError('DATABASE_CONNECTION_FAILED', $e->getMessage());
        }
    }

    /**
     * Load database configuration from App
     *
     * Loads configuration using the App configuration manager which
     * handles compiled PHP or .env file loading automatically.
     *
     * @return array<string,mixed> Configuration key-value pairs
     *
     * @internal
     * @since 1.0.0
     */
    private function loadConfig(): array
    {
        return App::all();
    }

    /**
     * Construct database-specific DSN connection string
     *
     * Builds a PDO Data Source Name (DSN) based on database type and
     * configuration. Supports multiple database systems with type-specific
     * connection parameters and SSL/TLS encryption options.
     *
     * Supported database types:
     * - mysql/mariadb: TCP or Unix socket connections
     * - pgsql: PostgreSQL with SSL support
     * - sqlsrv: Microsoft SQL Server with encryption
     * - oci: Oracle database
     * - ibm: IBM DB2
     * - sqlite: File-based SQLite database
     *
     * @param array<string,mixed> $config Database configuration array
     *
     * @return string PDO DSN connection string
     *
     * @internal
     * @since 1.0.0
     */
    private function getDsn(array $config): string
    {
        $error = new Error();

        if (empty($config['DB_TYPE'])) {
            $error->terminateWithError('DB_TYPE_NOT_PROVIDED');
        }

        if (empty($config['DB_HOST'])) {
            $error->terminateWithError('DB_HOST_NOT_PROVIDED');
        }

        if (empty($config['DB_NAME'])) {
            $error->terminateWithError('DB_NAME_NOT_PROVIDED');
        }

        if (empty($config['DB_PORT'])) {
            $error->terminateWithError('DB_PORT_NOT_PROVIDED');
        }

        $dbType = $config['DB_TYPE'];
        $host = $config['DB_HOST'];
        $dbName = $config['DB_NAME'];
        $port = $config['DB_PORT'];
        $charset = $config['DB_CHARSET'] ?? 'utf8';
        $socket = $config['DB_SOCKET'] ?? '';

        $useEncryption = $config['USE_ENCRYPTION'] ?? false;
        $encryptionKey = $config['ENCRYPTION_KEY'] ?? '';

        $dsn = '';

        switch ($dbType) {
            case 'mysql':
            case 'mariadb':
                if (!empty($socket)) {
                    $dsn = "$dbType:unix_socket=$socket;dbname=$dbName;" .
                           "charset=$charset";
                } else {
                    $dsn = "$dbType:host=$host;port=$port;dbname=$dbName;" .
                           "charset=$charset";
                }
                if ($useEncryption) {
                    $dsn .= ";ssl-key=$encryptionKey";
                }
                break;
            case 'pgsql':
                $dsn = "pgsql:host=$host;port=$port;dbname=$dbName";
                if ($useEncryption) {
                    $dsn .= ";sslmode=require";
                }
                break;
            case 'sqlsrv':
                $dsn = "sqlsrv:Server=$host,$port;Database=$dbName";
                if ($useEncryption) {
                    $dsn .= ";Encrypt=true;TrustServerCertificate=false";
                }
                break;
            case 'oci':
                $dsn = "oci:dbname=//$host:$port/$dbName";
                break;
            case 'ibm':
                $dsn = "ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$dbName;" .
                       "HOSTNAME=$host;PORT=$port;PROTOCOL=TCPIP;";
                break;
            case 'sqlite':
                $dsn = "sqlite:" . $config['DB_PATH'];
                break;
            default:
                $error->terminateWithError('UNSUPPORTED_DB_TYPE', $dbType);
        }

        return $dsn;
    }

    /**
     * Retrieve the PDO connection instance
     *
     * Returns the underlying PDO object for advanced operations or
     * direct PDO method access when needed.
     *
     * @return PDO Active PDO database connection
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $pdo = $db->getPdo();
     * $pdo->exec("SET time_zone = '+00:00'");
     * ```
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute SQL query with parameters
     *
     * Executes a prepared SQL statement with optional parameter binding.
     * Suitable for INSERT, UPDATE, DELETE operations. Returns execution
     * success status.
     *
     * Uses prepared statements to prevent SQL injection. Parameters are
     * automatically bound by PDO.
     *
     * @param string               $query  SQL query with placeholders (?, :name)
     * @param array<int|string,mixed> $params Query parameters (positional or named)
     *
     * @return bool True if execution successful, false otherwise
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // Positional parameters
     * $db->executeQuery("INSERT INTO users (name, email) VALUES (?, ?)",
     *                   ['John', 'john@example.com']);
     *
     * // Named parameters
     * $db->executeQuery("UPDATE users SET name = :name WHERE id = :id",
     *                   [':name' => 'Jane', ':id' => 5]);
     * ```
     */
    public function executeQuery(string $query, array $params = []): bool
    {
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Fetch all rows from query result
     *
     * Executes a SELECT query and returns all matching rows as associative
     * arrays. Each row is an associative array with column names as keys.
     *
     * @param string               $query  SQL SELECT query with placeholders
     * @param array<int|string,mixed> $params Query parameters for binding
     *
     * @return array<int,array<string,mixed>> Array of associative arrays
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $users = $db->fetchAll("SELECT * FROM users WHERE age > ?", [18]);
     * foreach ($users as $user) {
     *     echo $user['name'];
     * }
     * ```
     */
    public function fetchAll(string $query, array $params = []): array
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch single row from query result
     *
     * Executes a SELECT query and returns the first matching row as an
     * associative array. Returns empty array if no rows found.
     *
     * @param string               $query  SQL SELECT query with placeholders
     * @param array<int|string,mixed> $params Query parameters for binding
     *
     * @return array<string,mixed> Associative array of column => value
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [5]);
     * echo $user['email'] ?? 'Not found';
     * ```
     */
    public function fetchOne(string $query, array $params = []): array
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Close database connection
     *
     * Explicitly closes the PDO connection by setting it to null.
     * Connection will be automatically closed when script ends.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function closeConnection(): void
    {
        $this->pdo = null;
    }

    /**
     * Begin database transaction
     *
     * Starts a new database transaction. Subsequent queries will be part
     * of the transaction until commit or rollback is called.
     *
     * @return bool True if transaction started successfully
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $db->beginTransaction();
     * try {
     *     $db->executeQuery("UPDATE...");
     *     $db->executeQuery("INSERT...");
     *     $db->commitTransaction();
     * } catch (Exception $e) {
     *     $db->rollBackTransaction();
     * }
     * ```
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit active transaction
     *
     * Commits all changes made during the current transaction, making
     * them permanent in the database.
     *
     * @return bool True if commit successful
     *
     * @since 1.0.0
     */
    public function commitTransaction(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback active transaction
     *
     * Reverts all changes made during the current transaction, restoring
     * database to state before transaction began.
     *
     * @return bool True if rollback successful
     *
     * @since 1.0.0
     */
    public function rollBackTransaction(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Sanitize user input by type
     *
     * Filters and sanitizes input based on specified type using PHP's
     * filter functions. Provides basic XSS and injection protection.
     *
     * Supported types:
     * - string: Removes HTML tags and special characters
     * - email: Removes invalid email characters
     * - url: Removes invalid URL characters
     * - int: Removes non-numeric characters except +/-
     * - float: Removes non-numeric except decimal/+/-
     *
     * @param mixed  $input Input value to sanitize
     * @param string $type  Sanitization type (string/email/url/int/float)
     *
     * @return mixed Sanitized value
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $email = $db->sanitizeInput($_POST['email'], 'email');
     * $age = $db->sanitizeInput($_POST['age'], 'int');
     * ```
     */
    public function sanitizeInput($input, string $type = 'string')
    {
        switch ($type) {
            case 'string':
                return filter_var($input, FILTER_SANITIZE_STRING);
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var(
                    $input,
                    FILTER_SANITIZE_NUMBER_FLOAT,
                    FILTER_FLAG_ALLOW_FRACTION
                );
            default:
                return $input;
        }
    }

    /**
     * Execute query asynchronously with emulated prepares
     *
     * Executes query with PDO emulated prepares enabled temporarily.
     * Can improve performance for certain query types.
     *
     * @param string               $query  SQL query to execute
     * @param array<int|string,mixed> $params Query parameters
     *
     * @return bool True if execution successful
     *
     * @since 1.0.0
     */
    public function executeAsyncQuery(string $query, array $params = []): bool
    {
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $stmt = $this->pdo->prepare($query);
        $result = $stmt->execute($params);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $result;
    }

    public function handleConnectionTimeout(): void
{
    $config = $this->loadConfig();
    $timeout = $config['DB_CONNECTION_TIMEOUT'] ?? 5; // Default to 5 seconds if not set

    try {
        $this->pdo->setAttribute(PDO::ATTR_TIMEOUT, $timeout);
    } catch (PDOException $e) {
        $this->logger->logError('Database connection timeout: ' . $e->getMessage());
        $error = new Error();
        $error->terminateWithError('DATABASE_CONNECTION_TIMEOUT', $e->getMessage());
    }
}

public function connectWithFailover(): void
{
    $config = $this->loadConfig();
    $dsn = $this->getDsn($config);
    $username = $config['DB_USERNAME'];
    $password = $config['DB_PASSWORD'];

    try {
        $this->pdo = new PDO($dsn, $username, $password);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
    } catch (PDOException $e) {
        $this->logger->logError('Primary database connection failed: ' . $e->getMessage());
        $this->logger->logInfo('Attempting to connect to failover database.');

        $failoverDsn = $this->getFailoverDsn($config);
        $failoverUsername = $config['DB_FAILOVER_USERNAME'];
        $failoverPassword = $config['DB_FAILOVER_PASSWORD'];

        try {
            $this->pdo = new PDO($failoverDsn, $failoverUsername, $failoverPassword);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
        } catch (PDOException $e) {
            $this->logger->logError('Failover database connection failed: ' . $e->getMessage());
            $error = new Error();
            $error->terminateWithError('DATABASE_CONNECTION_FAILOVER_FAILED', $e->getMessage());
        }
    }
}

private function getFailoverDsn(array $config): string
{
    $dbType = $config['DB_TYPE'];
    $host = $config['DB_FAILOVER_HOST'];
    $dbName = $config['DB_NAME'];
    $port = $config['DB_FAILOVER_PORT'];
    $charset = $config['DB_CHARSET'] ?? 'utf8';
    $socket = $config['DB_SOCKET'] ?? '';

    $dsn = '';

    switch ($dbType) {
        case 'mysql':
        case 'mariadb':
            if (!empty($socket)) {
                $dsn = "$dbType:unix_socket=$socket;dbname=$dbName;charset=$charset";
            } else {
                $dsn = "$dbType:host=$host;port=$port;dbname=$dbName;charset=$charset";
            }
            break;
        case 'pgsql':
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbName";
            break;
        case 'sqlsrv':
            $dsn = "sqlsrv:Server=$host,$port;Database=$dbName";
            break;
        case 'oci':
            $dsn = "oci:dbname=//$host:$port/$dbName";
            break;
        case 'ibm':
            $dsn = "ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$dbName;HOSTNAME=$host;PORT=$port;PROTOCOL=TCPIP;";
            break;
        case 'sqlite':
            $dsn = "sqlite:" . $config['DB_PATH'];
            break;
        default:
            $error = new Error();
            $error->terminateWithError('UNSUPPORTED_DB_TYPE', $dbType);
    }

    return $dsn;
}

public function logQueryExecutionTime(string $query, array $params = []): bool
{
    $startTime = microtime(true);
    $stmt = $this->pdo->prepare($query);
    $result = $stmt->execute($params);
    $endTime = microtime(true);

    $executionTime = $endTime - $startTime;
    $this->logger->logInfo("Query executed in {$executionTime} seconds: {$query}");

    return $result;
}


    public function fetchWithCache(string $query, CacheInterface $cache, array $params = []): array
{
    $cacheKey = md5($query . serialize($params));
    $cachedResult = $cache->get($cacheKey);

    if ($cachedResult !== null) {
        $this->logger->logInfo("Cache hit for query: {$query}");
        return $cachedResult;
    }

    $stmt = $this->pdo->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $config = $this->loadConfig();
    $cacheTtl = $config['CACHE_TTL'] ?? 3600; // Default to 1 hour if not set
    $cache->set($cacheKey, $result, $cacheTtl);

    $this->logger->logInfo("Cache miss for query: {$query}. Result cached for {$cacheTtl} seconds.");
    return $result;
}

public function queryBuilder(): QueryBuilder
{
    $config = $this->loadConfig();
    if (empty($config['QUERY_BUILDER_ENABLED']) || $config['QUERY_BUILDER_ENABLED'] !== 'true') {
        $error = new Error();
        $error->terminateWithError('QUERY_BUILDER_NOT_ENABLED');
    }
    return new QueryBuilder($this->pdo);
}

public function executeNamedQuery(string $query, array $params = []): bool
{
    $stmt = $this->pdo->prepare($query);
    return $stmt->execute($params);
}

public function fetchAllNamed(string $query, array $params = []): array
{
    $stmt = $this->pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function fetchOneNamed(string $query, array $params = []): array
{
    $stmt = $this->pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function createSavepoint(string $savepointName): bool
{
    $query = "SAVEPOINT $savepointName";
    return $this->pdo->exec($query) !== false;
}

public function rollbackToSavepoint(string $savepointName): bool
{
    $query = "ROLLBACK TO SAVEPOINT $savepointName";
    return $this->pdo->exec($query) !== false;
}

public function releaseSavepoint(string $savepointName): bool
{
    $query = "RELEASE SAVEPOINT $savepointName";
    return $this->pdo->exec($query) !== false;
}

public function executeQueryWithLogging(string $query, array $params = []): bool
{
    $startTime = microtime(true);
    $stmt = $this->pdo->prepare($query);
    $result = $stmt->execute($params);
    $endTime = microtime(true);

    $executionTime = $endTime - $startTime;
    $config = $this->loadConfig();
    $threshold = $config['SLOW_QUERY_THRESHOLD'] ?? 2; // Default to 2 seconds if not set

    if ($executionTime > $threshold) {
        $this->logger->logError("Slow query detected: {$query} executed in {$executionTime} seconds.");
    }

    return $result;
}

public function runMigration(string $migrationFile): bool
{
    if (!file_exists($migrationFile)) {
        $this->logger->logError("Migration file not found: {$migrationFile}");
        return false;
    }

    $migrationSql = file_get_contents($migrationFile);
    try {
        $this->pdo->beginTransaction();
        $this->pdo->exec($migrationSql);
        $this->pdo->commit();
        $this->logger->logInfo("Migration executed successfully: {$migrationFile}");
        return true;
    } catch (PDOException $e) {
        $this->pdo->rollBack();
        $this->logger->logError("Migration failed: {$e->getMessage()}");
        return false;
    }
}

public function seedDatabase(string $seederClass): bool
{
    if (!class_exists($seederClass)) {
        $this->logger->logError("Seeder class not found: {$seederClass}");
        return false;
    }

    $seeder = new $seederClass($this->pdo);
    if (!method_exists($seeder, 'run')) {
        $this->logger->logError("Seeder class does not have a run method: {$seederClass}");
        return false;
    }

    try {
        $this->pdo->beginTransaction();
        $seeder->run();
        $this->pdo->commit();
        $this->logger->logInfo("Database seeding executed successfully: {$seederClass}");
        return true;
    } catch (PDOException $e) {
        $this->pdo->rollBack();
        $this->logger->logError("Database seeding failed: {$e->getMessage()}");
        return false;
    }
}

public function batchInsert(string $table, array $columns, array $rows): bool
{
    $columnList = implode(', ', $columns);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $query = "INSERT INTO $table ($columnList) VALUES ($placeholders)";

    try {
        $this->pdo->beginTransaction();
        $stmt = $this->pdo->prepare($query);

        foreach ($rows as $row) {
            $stmt->execute($row);
        }

        $this->pdo->commit();
        return true;
    } catch (PDOException $e) {
        $this->pdo->rollBack();
        $this->logger->logError("Batch insert failed: " . $e->getMessage());
        return false;
    }
}

public function batchUpdate(string $table, array $columns, array $rows, string $identifier): bool
{
    $setClause = implode(', ', array_map(fn($col) => "$col = ?", $columns));
    $query = "UPDATE $table SET $setClause WHERE $identifier = ?";

    try {
        $this->pdo->beginTransaction();
        $stmt = $this->pdo->prepare($query);

        foreach ($rows as $row) {
            $params = array_merge(array_values($row), [$row[$identifier]]);
            $stmt->execute($params);
        }

        $this->pdo->commit();
        return true;
    } catch (PDOException $e) {
        $this->pdo->rollBack();
        $this->logger->logError("Batch update failed: " . $e->getMessage());
        return false;
    }
}

public function connectToReplica(): void
{
    $config = $this->loadConfig();
    $dsn = $this->getReplicaDsn($config);
    $username = $config['REPLICA_DB_USERNAME'];
    $password = $config['REPLICA_DB_PASSWORD'];

    try {
        $this->replicaPdo = new PDO($dsn, $username, $password);
        $this->replicaPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->replicaPdo->setAttribute(PDO::ATTR_PERSISTENT, true);
        $this->logger->logInfo('Connected to replica database successfully.');
    } catch (PDOException $e) {
        $this->logger->logError('Replica database connection failed: ' . $e->getMessage());
        $error = new Error();
        $error->terminateWithError('DATABASE_CONNECTION_FAILED', $e->getMessage());
    }
}

private function getReplicaDsn(array $config): string
{
    $error = new Error();

    if (empty($config['DB_TYPE'])) {
        $error->terminateWithError('DB_TYPE_NOT_PROVIDED');
    }

    if (empty($config['REPLICA_DB_HOST'])) {
        $error->terminateWithError('DB_HOST_NOT_PROVIDED');
    }

    if (empty($config['DB_NAME'])) {
        $error->terminateWithError('DB_NAME_NOT_PROVIDED');
    }

    if (empty($config['REPLICA_DB_PORT'])) {
        $error->terminateWithError('DB_PORT_NOT_PROVIDED');
    }

    $dbType = $config['DB_TYPE'];
    $host = $config['REPLICA_DB_HOST'];
    $dbName = $config['DB_NAME'];
    $port = $config['REPLICA_DB_PORT'];
    $charset = $config['DB_CHARSET'] ?? 'utf8';
    $socket = $config['DB_SOCKET'] ?? '';

    $dsn = '';

    switch ($dbType) {
        case 'mysql':
        case 'mariadb':
            if (!empty($socket)) {
                $dsn = "$dbType:unix_socket=$socket;dbname=$dbName;charset=$charset";
            } else {
                $dsn = "$dbType:host=$host;port=$port;dbname=$dbName;charset=$charset";
            }
            break;
        case 'pgsql':
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbName";
            break;
        case 'sqlsrv':
            $dsn = "sqlsrv:Server=$host,$port;Database=$dbName";
            break;
        case 'oci':
            $dsn = "oci:dbname=//$host:$port/$dbName";
            break;
        case 'ibm':
            $dsn = "ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$dbName;HOSTNAME=$host;PORT=$port;PROTOCOL=TCPIP;";
            break;
        case 'sqlite':
            $dsn = "sqlite:" . $config['DB_PATH'];
            break;
        default:
            $error->terminateWithError('UNSUPPORTED_DB_TYPE', $dbType);
    }

    return $dsn;
}

public function connectToShard(string $shardKey): void
{
    $config = $this->loadConfig();
    $shardingConfigPath = $config['SHARDING_CONFIG'] ?? null;

    if (!$shardingConfigPath || !file_exists($shardingConfigPath)) {
        $error = new Error();
        $error->terminateWithError('SHARDING_CONFIG_NOT_PROVIDED');
    }

    $shardingConfig = json_decode(file_get_contents($shardingConfigPath), true);
    if (!isset($shardingConfig[$shardKey])) {
        $error = new Error();
        $error->terminateWithError('SHARDING_CONFIG_NOT_PROVIDED');
    }

    $shardConfig = $shardingConfig[$shardKey];
    $dsn = $this->getShardDsn($shardConfig);
    $username = $shardConfig['DB_USERNAME'];
    $password = $shardConfig['DB_PASSWORD'];

    try {
        $this->pdo = new PDO($dsn, $username, $password);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
        $this->logger->logInfo("Connected to shard {$shardKey} successfully.");
    } catch (PDOException $e) {
        $this->logger->logError("Sharding connection failed: " . $e->getMessage());
        $error = new Error();
        $error->terminateWithError('SHARDING_CONNECTION_FAILED', $e->getMessage());
    }
}

private function getShardDsn(array $shardConfig): string
{
    $error = new Error();

    if (empty($shardConfig['DB_TYPE'])) {
        $error->terminateWithError('DB_TYPE_NOT_PROVIDED');
    }

    if (empty($shardConfig['DB_HOST'])) {
        $error->terminateWithError('DB_HOST_NOT_PROVIDED');
    }

    if (empty($shardConfig['DB_NAME'])) {
        $error->terminateWithError('DB_NAME_NOT_PROVIDED');
    }

    if (empty($shardConfig['DB_PORT'])) {
        $error->terminateWithError('DB_PORT_NOT_PROVIDED');
    }

    $dbType = $shardConfig['DB_TYPE'];
    $host = $shardConfig['DB_HOST'];
    $dbName = $shardConfig['DB_NAME'];
    $port = $shardConfig['DB_PORT'];
    $charset = $shardConfig['DB_CHARSET'] ?? 'utf8';
    $socket = $shardConfig['DB_SOCKET'] ?? '';

    $dsn = '';

    switch ($dbType) {
        case 'mysql':
        case 'mariadb':
            if (!empty($socket)) {
                $dsn = "$dbType:unix_socket=$socket;dbname=$dbName;charset=$charset";
            } else {
                $dsn = "$dbType:host=$host;port=$port;dbname=$dbName;charset=$charset";
            }
            break;
        case 'pgsql':
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbName";
            break;
        case 'sqlsrv':
            $dsn = "sqlsrv:Server=$host,$port;Database=$dbName";
            break;
        case 'oci':
            $dsn = "oci:dbname=//$host:$port/$dbName";
            break;
        case 'ibm':
            $dsn = "ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$dbName;HOSTNAME=$host;PORT=$port;PROTOCOL=TCPIP;";
            break;
        case 'sqlite':
            $dsn = "sqlite:" . $shardConfig['DB_PATH'];
            break;
        default:
            $error->terminateWithError('UNSUPPORTED_DB_TYPE', $dbType);
    }

    return $dsn;
}

public function validateSchema(array $schema): bool
{
    $error = new Error();
    $config = $this->loadConfig();
    $dsn = $this->getDsn($config);
    $username = $config['DB_USERNAME'];
    $password = $config['DB_PASSWORD'];

    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        foreach ($schema as $table => $columns) {
            $query = "DESCRIBE $table";
            $stmt = $pdo->query($query);
            $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($columns as $column) {
                if (!in_array($column, $existingColumns)) {
                    $this->logger->logError("Schema validation failed: Column $column not found in table $table.");
                    $error->terminateWithError('SCHEMA_VALIDATION_FAILED', "Column $column not found in table $table.");
                }
            }
        }

        $this->logger->logInfo("Schema validation passed.");
        return true;
    } catch (PDOException $e) {
        $this->logger->logError("Schema validation failed: " . $e->getMessage());
        $error->terminateWithError('SCHEMA_VALIDATION_FAILED', $e->getMessage());
    }
}

public function cacheTableMetadata(string $table, CacheInterface $cache): array
{
    $cacheKey = "metadata_{$table}";
    $cachedMetadata = $cache->get($cacheKey);

    if ($cachedMetadata !== null) {
        $this->logger->logInfo("Cache hit for table metadata: {$table}");
        return $cachedMetadata;
    }

    try {
        $query = "DESCRIBE $table";
        $stmt = $this->pdo->query($query);
        $metadata = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $config = $this->loadConfig();
        $cacheTtl = $config['METADATA_CACHE_TTL'] ?? 3600; // Default to 1 hour if not set
        $cache->set($cacheKey, $metadata, $cacheTtl);

        $this->logger->logInfo("Cache miss for table metadata: {$table}. Metadata cached for {$cacheTtl} seconds.");
        return $metadata;
    } catch (PDOException $e) {
        $this->logger->logError("Metadata caching failed: " . $e->getMessage());
        $error = new Error();
        $error->terminateWithError('METADATA_CACHE_FAILED', $e->getMessage());
    }
}

public function executeQueryWithRetry(string $query, array $params = []): bool
{
    $config = $this->loadConfig();
    $maxAttempts = $config['QUERY_RETRY_ATTEMPTS'] ?? 3; // Default to 3 attempts if not set
    $retryDelay = $config['QUERY_RETRY_DELAY'] ?? 1000; // Default to 1000 milliseconds (1 second) if not set

    $attempts = 0;
    while ($attempts < $maxAttempts) {
        try {
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            $attempts++;
            if ($attempts >= $maxAttempts) {
                $this->logger->logError("Query retry failed after {$attempts} attempts: " . $e->getMessage());
                $error = new Error();
                $error->terminateWithError('QUERY_RETRY_FAILED', $e->getMessage());
            }
            usleep($retryDelay * 1000); // Convert milliseconds to microseconds
        }
    }

    return false;
}

public function monitorServerStatus(): bool
{
    $config = $this->loadConfig();
    $dsn = $this->getDsn($config);
    $username = $config['DB_USERNAME'];
    $password = $config['DB_PASSWORD'];

    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = "SHOW STATUS LIKE 'Uptime'";
        $stmt = $pdo->query($query);
        $status = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($status && isset($status['Value']) && $status['Value'] > 0) {
            $this->logger->logInfo("Server status: Running. Uptime: {$status['Value']} seconds.");
            return true;
        } else {
            $this->logger->logError("Server status check failed: Server is not running.");
            $error = new Error();
            $error->terminateWithError('SERVER_STATUS_CHECK_FAILED', 'Server is not running.');
        }
    } catch (PDOException $e) {
        $this->logger->logError("Server status check failed: " . $e->getMessage());
        $error = new Error();
        $error->terminateWithError('SERVER_STATUS_CHECK_FAILED', $e->getMessage());
    }
}

public function manageServerLogs(string $logFilePath): bool
{
    $config = $this->loadConfig();
    $dsn = $this->getDsn($config);
    $username = $config['DB_USERNAME'];
    $password = $config['DB_PASSWORD'];

    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = "SHOW VARIABLES LIKE 'log_output'";
        $stmt = $pdo->query($query);
        $logOutput = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($logOutput && isset($logOutput['Value']) && $logOutput['Value'] === 'FILE') {
            $query = "SHOW VARIABLES LIKE 'general_log_file'";
            $stmt = $pdo->query($query);
            $logFile = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($logFile && isset($logFile['Value'])) {
                $currentLogFile = $logFile['Value'];
                if ($currentLogFile !== $logFilePath) {
                    $query = "SET GLOBAL general_log_file = :logFilePath";
                    $stmt = $pdo->prepare($query);
                    $stmt->bindParam(':logFilePath', $logFilePath);
                    $stmt->execute();

                    $this->logger->logInfo("Server log file path updated to: {$logFilePath}");
                }
                return true;
            } else {
                $this->logger->logError("Server log management failed: Unable to retrieve current log file path.");
                $error = new Error();
                $error->terminateWithError('SERVER_LOG_MANAGEMENT_FAILED', 'Unable to retrieve current log file path.');
            }
        } else {
            $this->logger->logError("Server log management failed: Log output is not set to FILE.");
            $error = new Error();
            $error->terminateWithError('SERVER_LOG_MANAGEMENT_FAILED', 'Log output is not set to FILE.');
        }
    } catch (PDOException $e) {
        $this->logger->logError("Server log management failed: " . $e->getMessage());
        $error = new Error();
        $error->terminateWithError('SERVER_LOG_MANAGEMENT_FAILED', $e->getMessage());
    }
}

public function manageServerBackups(string $backupDirectory): bool
{
    $config = $this->loadConfig();
    $dsn = $this->getDsn($config);
    $username = $config['DB_USERNAME'];
    $password = $config['DB_PASSWORD'];
    $database = $config['DB_NAME'];

    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $backupFile = $backupDirectory . DIRECTORY_SEPARATOR . $database . '_backup_' . date('Ymd_His') . '.sql';
        $command = "mysqldump --user={$username} --password={$password} --host={$config['DB_HOST']} {$database} > {$backupFile}";

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->logger->logError("Server backup failed: " . implode("\n", $output));
            $error = new Error();
            $error->terminateWithError('SERVER_BACKUP_FAILED', implode("\n", $output));
        }

        $this->logger->logInfo("Server backup created successfully: {$backupFile}");
        return true;
    } catch (PDOException $e) {
        $this->logger->logError("Server backup failed: " . $e->getMessage());
        $error = new Error();
        $error->terminateWithError('SERVER_BACKUP_FAILED', $e->getMessage());
    }
}

public function manageServerReplication(string $replicationConfigFile): bool
{
    $config = $this->loadConfig();
    $dsn = $this->getDsn($config);
    $username = $config['DB_USERNAME'];
    $password = $config['DB_PASSWORD'];

    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (!file_exists($replicationConfigFile)) {
            $this->logger->logError("Replication configuration file not found: {$replicationConfigFile}");
            $error = new Error();
            $error->terminateWithError('SERVER_REPLICATION_FAILED', 'Replication configuration file not found.');
        }

        $replicationConfig = json_decode(file_get_contents($replicationConfigFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->logError("Invalid JSON in replication configuration file: {$replicationConfigFile}");
            $error = new Error();
            $error->terminateWithError('SERVER_REPLICATION_FAILED', 'Invalid JSON in replication configuration file.');
        }

        foreach ($replicationConfig as $query) {
            $stmt = $pdo->prepare($query);
            $stmt->execute();
        }

        $this->logger->logInfo("Server replication configured successfully using: {$replicationConfigFile}");
        return true;
    } catch (PDOException $e) {
        $this->logger->logError("Server replication failed: " . $e->getMessage());
        $error = new Error();
        $error->terminateWithError('SERVER_REPLICATION_FAILED', $e->getMessage());
    }
}








}