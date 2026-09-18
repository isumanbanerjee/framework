<?php

/**
 * Database Migrator
 *
 * Discovers migration files in a directory, applies pending ones, records
 * them in a "migrations" table, and rolls back the most recent batch.
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
use RuntimeException;

/**
 * Migrator Class
 *
 * Migration files are named "<timestamp>_<snake_case_name>.php" and must
 * define a class named as the StudlyCase of the name part. For example,
 * "2026_01_28_000001_create_users_table.php" must define CreateUsersTable.
 *
 * Applied migrations are tracked in a "migrations" table with a batch number
 * so that rollback() can reverse a whole batch at once.
 *
 * @category  Database
 * @package   Core\Model\Database
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
class Migrator
{
    /**
     * @var PDO
     */
    private PDO $pdo;

    /**
     * @var Schema
     */
    private Schema $schema;

    /**
     * @var string
     */
    private string $path;

    /**
     * @param PDO    $pdo  Database connection.
     * @param string $path Directory containing migration files.
     */
    public function __construct(PDO $pdo, string $path)
    {
        $this->pdo = $pdo;
        $this->schema = new Schema($pdo);
        $this->path = rtrim($path, '/\\');
        $this->ensureMigrationsTable();
    }

    /**
     * Apply all pending migrations as a single new batch.
     *
     * @return array<int,string> Names of the migrations that were applied.
     */
    public function run(): array
    {
        $applied = $this->appliedMigrations();
        $batch = $this->nextBatchNumber();
        $ran = [];

        foreach ($this->migrationFiles() as $file) {
            $name = basename($file, '.php');

            if (in_array($name, $applied, true)) {
                continue;
            }

            $migration = $this->resolveMigration($file, $name);
            $migration->up($this->schema);
            $this->recordMigration($name, $batch);
            $ran[] = $name;
        }

        return $ran;
    }

    /**
     * Roll back the most recently applied batch.
     *
     * @return array<int,string> Names of the migrations that were reversed.
     */
    public function rollback(): array
    {
        $batch = $this->lastBatchNumber();

        if ($batch === 0) {
            return [];
        }

        $statement = $this->pdo->prepare(
            'SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC'
        );
        $statement->execute([$batch]);
        $names = $statement->fetchAll(PDO::FETCH_COLUMN);

        $rolledBack = [];

        foreach ($names as $name) {
            $file = $this->path . '/' . $name . '.php';

            if (!is_file($file)) {
                throw new RuntimeException("Migration file missing for rollback: {$name}");
            }

            $migration = $this->resolveMigration($file, $name);
            $migration->down($this->schema);
            $this->forgetMigration($name);
            $rolledBack[] = $name;
        }

        return $rolledBack;
    }

    /**
     * Names of migrations that have already been applied.
     *
     * @return array<int,string>
     */
    public function appliedMigrations(): array
    {
        $statement = $this->pdo->query('SELECT migration FROM migrations ORDER BY id');

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Create the migrations tracking table if absent.
     *
     * @return void
     */
    private function ensureMigrationsTable(): void
    {
        if (!$this->schema->hasTable('migrations')) {
            $this->schema->create('migrations', function (Blueprint $table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
            });
        }
    }

    /**
     * Sorted list of migration files in the path.
     *
     * @return array<int,string>
     */
    private function migrationFiles(): array
    {
        $files = glob($this->path . '/*.php') ?: [];
        sort($files);

        return $files;
    }

    /**
     * Require a migration file and instantiate its class.
     *
     * @param string $file Absolute file path.
     * @param string $name Migration name (file basename without extension).
     *
     * @return Migration
     */
    private function resolveMigration(string $file, string $name): Migration
    {
        require_once $file;

        $class = $this->classNameFromMigration($name);

        if (!class_exists($class)) {
            throw new RuntimeException("Migration class {$class} not found in {$file}");
        }

        $migration = new $class();

        if (!$migration instanceof Migration) {
            throw new RuntimeException("Class {$class} must extend Migration");
        }

        return $migration;
    }

    /**
     * Derive the StudlyCase class name from a migration file name.
     *
     * Strips a leading numeric/date/timestamp prefix (segments that are all
     * digits) and StudlyCases the rest: "2026_01_28_000001_create_users_table"
     * becomes "CreateUsersTable".
     *
     * @param string $name Migration name.
     *
     * @return string
     */
    private function classNameFromMigration(string $name): string
    {
        $segments = explode('_', $name);

        while (!empty($segments) && ctype_digit($segments[0])) {
            array_shift($segments);
        }

        return implode('', array_map('ucfirst', $segments));
    }

    /**
     * Record an applied migration.
     *
     * @param string $name  Migration name.
     * @param int    $batch Batch number.
     *
     * @return void
     */
    private function recordMigration(string $name, int $batch): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO migrations (migration, batch) VALUES (?, ?)'
        );
        $statement->execute([$name, $batch]);
    }

    /**
     * Remove a migration record.
     *
     * @param string $name Migration name.
     *
     * @return void
     */
    private function forgetMigration(string $name): void
    {
        $statement = $this->pdo->prepare('DELETE FROM migrations WHERE migration = ?');
        $statement->execute([$name]);
    }

    /**
     * The batch number to use for the next run.
     *
     * @return int
     */
    private function nextBatchNumber(): int
    {
        return $this->lastBatchNumber() + 1;
    }

    /**
     * The highest recorded batch number, or 0 when none exist.
     *
     * @return int
     */
    private function lastBatchNumber(): int
    {
        $value = $this->pdo->query('SELECT MAX(batch) FROM migrations')->fetchColumn();

        return (int) $value;
    }
}
