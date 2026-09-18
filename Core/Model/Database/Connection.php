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
 * Supports sqlite, mysql/mariadb, and pgsql DSNs.
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
     * Create a PDO connection from configuration.
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

        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}
