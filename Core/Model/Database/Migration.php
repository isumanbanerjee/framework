<?php

/**
 * Migration Base Class
 *
 * Extend to define reversible schema changes via up() and down().
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

/**
 * Migration Class
 *
 * Example:
 * ```php
 * class CreateUsersTable extends Migration
 * {
 *     public function up(Schema $schema): void
 *     {
 *         $schema->create('users', function (Blueprint $table) {
 *             $table->id();
 *             $table->string('email')->unique();
 *             $table->timestamps();
 *         });
 *     }
 *
 *     public function down(Schema $schema): void
 *     {
 *         $schema->dropIfExists('users');
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
abstract class Migration
{
    /**
     * Apply the migration.
     *
     * @param Schema $schema Schema builder bound to the active connection.
     *
     * @return void
     */
    abstract public function up(Schema $schema): void;

    /**
     * Reverse the migration.
     *
     * @param Schema $schema Schema builder bound to the active connection.
     *
     * @return void
     */
    abstract public function down(Schema $schema): void;
}
