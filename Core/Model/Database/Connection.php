<?php

/**
 * Database Connection Factory
 *
 * Builds a PDO instance and DSN string from a configuration array. Used by the
 * console migrate/seed commands so they can obtain a connection without the
 * full Database service.
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

use InvalidArgumentException;
use PDO;

/**
 * Connection Class
 *
 * Supports sqlite, mysql/mariadb, and pgsql DSNs. Pools PDO instances
 * in-process, keyed by DSN + username, and marks each underlying connection
 * as a PDO persistent connection (`PDO::ATTR_PERSISTENT`) so PHP's SAPI can
 * additionally reuse the OS-level connection across requests where the
 * runtime supports it (e.g. PHP-FPM workers). Together these avoid the cost
 * of re-authenticating a new connection every time something in the same
 * process (console commands, migrations, seeders) asks for the same
 * database.
 *
 * @category  Database
 * @package   Core\Model\Database
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
class Connection
{
    /**
     * In-process pool of PDO connections, keyed by DSN + username.
     *
     * @var array<string,PDO>
     */
    private static array $pool = [];

    /**
     * Build a DSN string from configuration.
     *
     * @param array<string,mixed> $config Configuration with DB_* keys.
     *
     * @return string
     *
     * @throws InvalidArgumentException When the driver is unsupported.
     */
    public static function dsn(array $config): string
    {
        $type = strtolower((string) ($config['DB_TYPE'] ?? 'mysql'));

        switch ($type) {
            case 'sqlite':
                return 'sqlite:' . ($config['DB_PATH'] ?? ':memory:');

            case 'mysql':
            case 'mariadb':
                $host = $config['DB_HOST'] ?? 'localhost';
                $port = $config['DB_PORT'] ?? 3306;
                $name = $config['DB_NAME'] ?? '';
                $charset = $config['DB_CHARSET'] ?? 'utf8mb4';

                return "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

            case 'pgsql':
                $host = $config['DB_HOST'] ?? 'localhost';
                $port = $config['DB_PORT'] ?? 5432;
                $name = $config['DB_NAME'] ?? '';

                return "pgsql:host={$host};port={$port};dbname={$name}";

            default:
                throw new InvalidArgumentException("Unsupported DB_TYPE: {$type}");
        }
    }

    /**
     * Get a pooled PDO connection for the given configuration, creating and
     * caching one on first use. Subsequent calls with an equivalent DSN and
     * username return the same PDO instance instead of opening a new
     * connection.
     *
     * @param array<string,mixed> $config Configuration with DB_* keys.
     *
     * @return PDO
     */
    public static function make(array $config): PDO
    {
        $dsn = self::dsn($config);
        $username = $config['DB_USERNAME'] ?? null;
        $password = $config['DB_PASSWORD'] ?? null;
        $key = $dsn . '|' . ($username ?? '');

        if (isset(self::$pool[$key])) {
            return self::$pool[$key];
        }

        $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_PERSISTENT => true]);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        self::$pool[$key] = $pdo;

        return $pdo;
    }

    /**
     * Number of distinct connections currently held in the pool.
     *
     * @return int
     */
    public static function poolSize(): int
    {
        return count(self::$pool);
    }

    /**
     * Clear the connection pool, releasing PHP's references to every pooled
     * PDO instance. Mainly useful for test isolation and long-running
     * processes (queue workers) that want to force reconnection.
     *
     * @return void
     */
    public static function resetPool(): void
    {
        self::$pool = [];
    }
}
