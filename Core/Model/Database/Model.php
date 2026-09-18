<?php

/**
 * Active Record Model
 *
 * A lightweight base model providing CRUD, mass assignment, timestamps, and
 * simple relationships on top of the QueryBuilder.
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
 * Model Class
 *
 * Example:
 * ```php
 * class User extends Model
 * {
 *     protected array $fillable = ['name', 'email'];
 * }
 *
 * Model::setConnection($pdo);
 *
 * $user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
 * $found = User::find($user->id);
 * $found->name = 'Ada L.';
 * $found->save();
 * ```
 *
 * @category  Database
 * @package   Core\Model\Database
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
abstract class Model
{
    /**
     * Shared database connection for all models.
     *
     * @var PDO|null
     */
    protected static ?PDO $connection = null;

    /**
     * Explicit table name; inferred from the class name when empty.
     *
     * @var string
     */
    protected string $table = '';

    /**
     * Primary key column.
     *
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Mass-assignable attributes.
     *
     * @var array<int,string>
     */
    protected array $fillable = [];

    /**
     * Whether the model maintains created_at/updated_at timestamps.
     *
     * @var bool
     */
    protected bool $timestamps = true;

    /**
     * Current attribute values.
     *
     * @var array<string,mixed>
     */
    protected array $attributes = [];

    /**
     * Whether this instance corresponds to a persisted row.
     *
     * @var bool
     */
    protected bool $exists = false;

    /**
     * @param array<string,mixed> $attributes Initial attributes.
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Set the shared PDO connection.
     *
     * @param PDO $pdo Database connection.
     *
     * @return void
     */
    public static function setConnection(PDO $pdo): void
    {
        static::$connection = $pdo;
    }

    /**
     * Get the shared PDO connection.
     *
     * @return PDO
     *
     * @throws RuntimeException When no connection has been set.
     */
    public static function getConnection(): PDO
    {
        if (static::$connection === null) {
            throw new RuntimeException('No database connection set on Model.');
        }

        return static::$connection;
    }

    /**
     * Resolve the table name for this model.
     *
     * @return string
     */
    public function getTable(): string
    {
        if ($this->table !== '') {
            return $this->table;
        }

        $class = (new \ReflectionClass($this))->getShortName();

        return $this->pluralize($this->snake($class));
    }

    /**
     * Start a query builder scoped to this model's table.
     *
     * @return QueryBuilder
     */
    public static function query(): QueryBuilder
    {
        $instance = new static();

        return (new QueryBuilder(static::getConnection()))->table($instance->getTable());
    }

    /**
     * Mass-assign attributes (respecting $fillable when defined).
     *
     * @param array<string,mixed> $attributes Attributes to assign.
     *
     * @return static
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if (empty($this->fillable) || in_array($key, $this->fillable, true) || $key === $this->primaryKey) {
                $this->attributes[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Hydrate a model instance from a database row (marked as existing).
     *
     * @param array<string,mixed> $row Raw row data.
     *
     * @return static
     */
    public static function hydrate(array $row): static
    {
        $model = new static();
        $model->attributes = $row;
        $model->exists = true;

        return $model;
    }

    /**
     * Create and persist a new model.
     *
     * @param array<string,mixed> $attributes Attributes to assign.
     *
     * @return static
     */
    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();

        return $model;
    }

    /**
     * Find a model by primary key.
     *
     * @param mixed $id Primary key value.
     *
     * @return static|null
     */
    public static function find(mixed $id): ?static
    {
        $instance = new static();
        $row = static::query()->where($instance->primaryKey, $id)->first();

        return $row === null ? null : static::hydrate($row);
    }

    /**
     * Retrieve all models.
     *
     * @return array<int,static>
     */
    public static function all(): array
    {
        return array_map(
            static fn (array $row): static => static::hydrate($row),
            static::query()->get()
        );
    }

    /**
     * Persist the model (insert when new, update when existing).
     *
     * @return bool
     */
    public function save(): bool
    {
        $now = date('Y-m-d H:i:s');

        if ($this->timestamps) {
            if (!$this->exists) {
                $this->attributes['created_at'] = $this->attributes['created_at'] ?? $now;
            }
            $this->attributes['updated_at'] = $now;
        }

        $builder = (new QueryBuilder(static::getConnection()))->table($this->getTable());

        if ($this->exists) {
            $values = $this->attributes;
            unset($values[$this->primaryKey]);
            $result = $builder->where($this->primaryKey, $this->attributes[$this->primaryKey])->update($values);
        } else {
            $id = $builder->insertGetId($this->attributes);
            if ($id !== false) {
                $this->attributes[$this->primaryKey] = is_numeric($id) ? (int) $id : $id;
            }
            $this->exists = true;
            $result = true;
        }

        return $result;
    }

    /**
     * Delete the model from the database.
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $result = (new QueryBuilder(static::getConnection()))
            ->table($this->getTable())
            ->where($this->primaryKey, $this->attributes[$this->primaryKey])
            ->delete();

        $this->exists = false;

        return $result;
    }

    /**
     * Define a one-to-many relationship.
     *
     * @param class-string<Model> $related    Related model class.
     * @param string              $foreignKey Foreign key on the related table.
     *
     * @return array<int,Model>
     */
    public function hasMany(string $related, string $foreignKey): array
    {
        /** @var Model $instance */
        $instance = new $related();
        $rows = (new QueryBuilder(static::getConnection()))
            ->table($instance->getTable())
            ->where($foreignKey, $this->attributes[$this->primaryKey])
            ->get();

        return array_map(
            static fn (array $row): Model => $related::hydrate($row),
            $rows
        );
    }

    /**
     * Define an inverse one-to-one/many relationship.
     *
     * @param class-string<Model> $related    Related model class.
     * @param string              $foreignKey Foreign key on this model.
     *
     * @return Model|null
     */
    public function belongsTo(string $related, string $foreignKey): ?Model
    {
        if (!isset($this->attributes[$foreignKey])) {
            return null;
        }

        return $related::find($this->attributes[$foreignKey]);
    }

    /**
     * Whether the model exists in the database.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->exists;
    }

    /**
     * Get all attributes as an array.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Magic getter for attributes.
     *
     * @param string $name Attribute name.
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Magic setter for attributes.
     *
     * @param string $name  Attribute name.
     * @param mixed  $value Attribute value.
     *
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Magic isset for attributes.
     *
     * @param string $name Attribute name.
     *
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Convert StudlyCase/camelCase to snake_case.
     *
     * @param string $value Input string.
     *
     * @return string
     */
    private function snake(string $value): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }

    /**
     * Naive English pluralization for table-name inference.
     *
     * @param string $value snake_case singular noun.
     *
     * @return string
     */
    private function pluralize(string $value): string
    {
        if (preg_match('/(s|x|z|ch|sh)$/', $value)) {
            return $value . 'es';
        }

        if (preg_match('/[^aeiou]y$/', $value)) {
            return substr($value, 0, -1) . 'ies';
        }

        return $value . 's';
    }
}
