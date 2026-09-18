<?php

/**
 * Seeder Base Class
 *
 * Extend to populate tables with initial or test data.
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

/**
 * Seeder Class
 *
 * Receives a PDO connection (and a QueryBuilder helper) for inserting data.
 *
 * Example:
 * ```php
 * class UserSeeder extends Seeder
 * {
 *     public function run(): void
 *     {
 *         $this->table('users')->insert([
 *             'email' => 'admin@example.com',
 *             'password' => password_hash('secret', PASSWORD_DEFAULT),
 *         ]);
 *     }
 * }
 * ```
 *
 * @category  Database
 * @package   Core\Model\Database
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
abstract class Seeder
{
    /**
     * @var PDO
     */
    protected PDO $pdo;

    /**
     * @param PDO $pdo Database connection.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Run the seeder.
     *
     * @return void
     */
    abstract public function run(): void;

    /**
     * Start a query builder for a table.
     *
     * @param string $table Table name.
     *
     * @return QueryBuilder
     */
    protected function table(string $table): QueryBuilder
    {
        return (new QueryBuilder($this->pdo))->table($table);
    }
}
