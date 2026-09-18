<?php

/**
 * Schema Column Definition
 *
 * A fluent description of a single table column, produced by Blueprint and
 * compiled to SQL. Modifiers (nullable, default, unique) chain off the
 * column-creating call.
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
 * ColumnDefinition Class
 *
 * Records a column's name, SQL type, and modifiers, then compiles them into a
 * column clause for a CREATE TABLE statement (SQLite-compatible DDL).
 *
 * @category  Database
 * @package   Core\Model\Database
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
class ColumnDefinition
{
    private string $name;
    private string $type;
    private bool $nullable = false;
    private bool $unique = false;
    private bool $autoIncrement = false;
    private bool $hasDefault = false;
    private mixed $default = null;

    /**
     * @param string $name Column name.
     * @param string $type SQL type (e.g. "VARCHAR(255)").
     */
    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * Allow NULL values for this column.
     *
     * @param bool $value Whether the column is nullable.
     *
     * @return self
     */
    public function nullable(bool $value = true): self
    {
        $this->nullable = $value;

        return $this;
    }

    /**
     * Set a default value for this column.
     *
     * @param mixed $value Default value (scalar, or raw SQL like CURRENT_TIMESTAMP).
     *
     * @return self
     */
    public function default(mixed $value): self
    {
        $this->hasDefault = true;
        $this->default = $value;

        return $this;
    }

    /**
     * Mark this column as UNIQUE.
     *
     * @param bool $value Whether the column is unique.
     *
     * @return self
     */
    public function unique(bool $value = true): self
    {
        $this->unique = $value;

        return $this;
    }

    /**
     * Mark this column as an auto-incrementing primary key.
     *
     * @return self
     */
    public function autoIncrement(): self
    {
        $this->autoIncrement = true;

        return $this;
    }

    /**
     * Compile the column into its SQL clause.
     *
     * @return string
     */
    public function toSql(): string
    {
        // SQLite requires "INTEGER PRIMARY KEY AUTOINCREMENT" verbatim.
        if ($this->autoIncrement) {
            return "{$this->name} INTEGER PRIMARY KEY AUTOINCREMENT";
        }

        $sql = "{$this->name} {$this->type}";

        $sql .= $this->nullable ? ' NULL' : ' NOT NULL';

        if ($this->hasDefault) {
            $sql .= ' DEFAULT ' . $this->formatDefault($this->default);
        }

        if ($this->unique) {
            $sql .= ' UNIQUE';
        }

        return $sql;
    }

    /**
     * Format a default value for inclusion in DDL.
     *
     * @param mixed $value Default value.
     *
     * @return string
     */
    private function formatDefault(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value === null) {
            return 'NULL';
        }

        // Allow raw SQL keywords through unquoted.
        if (in_array(strtoupper((string) $value), ['CURRENT_TIMESTAMP', 'NULL'], true)) {
            return strtoupper((string) $value);
        }

        return "'" . str_replace("'", "''", (string) $value) . "'";
    }
}
