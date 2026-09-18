<?php

/**
 * Schema Builder Facade
 *
 * Executes DDL against a PDO connection using fluent Blueprint definitions.
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

use PDO;
use Throwable;

/**
 * Schema Class
 *
 * Provides create/drop table operations driven by Blueprint. Instances are
 * bound to a single PDO connection, so the same builder works with SQLite in
 * tests and MySQL/Postgres in production.
 *
 * Example:
 * ```php
 * $schema = new Schema($pdo);
 * $schema->create('users', function (Blueprint $table) {
 *     $table->id();
 *     $table->string('email')->unique();
 * });
 * ```
 *
 * @category  Database
 * @package   Core\Model\Database
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
class Schema
{
    /**
     * @var PDO
     */
    private PDO $pdo;

    /**
     * @param PDO $pdo Database connection.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a table from a Blueprint definition.
     *
     * @param string   $table    Table name.
     * @param callable $callback Receives the Blueprint to define columns.
     *
     * @return void
     */
    public function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $this->pdo->exec($blueprint->toSql());
    }

    /**
     * Drop a table.
     *
     * @param string $table Table name.
     *
     * @return void
     */
    public function drop(string $table): void
    {
        $this->pdo->exec("DROP TABLE {$table}");
    }

    /**
     * Drop a table if it exists.
     *
     * @param string $table Table name.
     *
     * @return void
     */
    public function dropIfExists(string $table): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS {$table}");
    }

    /**
     * Determine whether a table exists.
     *
     * Portable across drivers: attempts a trivial select and treats failure
     * as "table absent".
     *
     * @param string $table Table name.
     *
     * @return bool
     */
    public function hasTable(string $table): bool
    {
        try {
            $this->pdo->query("SELECT 1 FROM {$table} LIMIT 1");

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
