<?php

/**
 * Schema Blueprint
 *
 * A fluent builder for a table definition, used inside Schema::create().
 * Accumulates columns and compiles them into a CREATE TABLE statement.
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
 * Blueprint Class
 *
 * Collects column definitions and compiles SQLite-compatible DDL.
 *
 * Example:
 * ```php
 * $schema->create('users', function (Blueprint $table) {
 *     $table->id();
 *     $table->string('email')->unique();
 *     $table->string('password');
 *     $table->boolean('is_admin')->default(false);
 *     $table->timestamps();
 * });
 * ```
 *
 * @category  Database
 * @package   Core\Model\Database
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
class Blueprint
{
    /**
     * Table name.
     *
     * @var string
     */
    private string $table;

    /**
     * Column definitions.
     *
     * @var array<int,ColumnDefinition>
     */
    private array $columns = [];

    /**
     * @param string $table Table name.
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Auto-incrementing integer primary key.
     *
     * @param string $name Column name.
     *
     * @return ColumnDefinition
     */
    public function id(string $name = 'id'): ColumnDefinition
    {
        return $this->addColumn($name, '')->autoIncrement();
    }

    /**
     * VARCHAR column.
     *
     * @param string $name   Column name.
     * @param int    $length Maximum length.
     *
     * @return ColumnDefinition
     */
    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn($name, "VARCHAR($length)");
    }

    /**
     * TEXT column.
     *
     * @param string $name Column name.
     *
     * @return ColumnDefinition
     */
    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TEXT');
    }

    /**
     * INTEGER column.
     *
     * @param string $name Column name.
     *
     * @return ColumnDefinition
     */
    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'INTEGER');
    }

    /**
     * BIGINT column.
     *
     * @param string $name Column name.
     *
     * @return ColumnDefinition
     */
    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'BIGINT');
    }

    /**
     * BOOLEAN column.
     *
     * @param string $name Column name.
     *
     * @return ColumnDefinition
     */
    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'BOOLEAN');
    }

    /**
     * DECIMAL column.
     *
     * @param string $name      Column name.
     * @param int    $precision Total digits.
     * @param int    $scale     Digits after the decimal point.
     *
     * @return ColumnDefinition
     */
    public function decimal(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn($name, "DECIMAL($precision,$scale)");
    }

    /**
     * DATE column.
     *
     * @param string $name Column name.
     *
     * @return ColumnDefinition
     */
    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'DATE');
    }

    /**
     * DATETIME column.
     *
     * @param string $name Column name.
     *
     * @return ColumnDefinition
     */
    public function dateTime(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'DATETIME');
    }

    /**
     * TIMESTAMP column.
     *
     * @param string $name Column name.
     *
     * @return ColumnDefinition
     */
    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TIMESTAMP');
    }

    /**
     * Add nullable created_at and updated_at TIMESTAMP columns.
     *
     * @return void
     */
    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    /**
     * Register a column definition.
     *
     * @param string $name Column name.
     * @param string $type SQL type.
     *
     * @return ColumnDefinition
     */
    private function addColumn(string $name, string $type): ColumnDefinition
    {
        $column = new ColumnDefinition($name, $type);
        $this->columns[] = $column;

        return $column;
    }

    /**
     * Compile the blueprint into a CREATE TABLE statement.
     *
     * @return string
     */
    public function toSql(): string
    {
        $definitions = array_map(
            static fn (ColumnDefinition $column): string => $column->toSql(),
            $this->columns
        );

        return "CREATE TABLE {$this->table} (\n    " . implode(",\n    ", $definitions) . "\n)";
    }
}
