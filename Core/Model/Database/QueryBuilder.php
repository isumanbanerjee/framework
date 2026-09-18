<?php

/**
 * Fluent SQL Query Builder
 *
 * This file contains the QueryBuilder class which provides a fluent,
 * chainable interface for building and executing SQL queries without
 * writing raw SQL strings.
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
use PDOException;

/**
 * QueryBuilder Class
 *
 * Provides a fluent, expressive interface for building SQL queries in a
 * database-agnostic way. Supports SELECT, INSERT, UPDATE, DELETE operations
 * with joins, where clauses, ordering, grouping, and more.
 *
 * Features:
 * - Fluent, chainable method syntax
 * - Automatic parameter binding (SQL injection prevention)
 * - Support for all SQL clauses (WHERE, JOIN, GROUP BY, HAVING, ORDER BY)
 * - Complex where conditions (IN, BETWEEN, NULL checks)
 * - Multiple join types (INNER, LEFT, RIGHT)
 * - Aggregate functions (COUNT, SUM, AVG, MIN, MAX)
 * - Pagination support (LIMIT, OFFSET)
 * - Raw SQL support when needed
 * - DISTINCT query support
 * - Batch operations
 *
 * Query types supported:
 * - SELECT with all clauses
 * - INSERT single and batch
 * - UPDATE with conditions
 * - DELETE with conditions
 * - Aggregate queries
 * - Joined queries
 *
 * Example usage:
 * ```php
 * $qb = new QueryBuilder($pdo);
 *
 * // Simple select
 * $users = $qb->table('users')
 *     ->where('age', '>', 18)
 *     ->orderBy('name')
 *     ->get();
 *
 * // Complex query with joins
 * $posts = $qb->table('posts')
 *     ->select(['posts.*', 'users.name'])
 *     ->leftJoin('users', 'posts.user_id', '=', 'users.id')
 *     ->where('posts.published', '=', 1)
 *     ->whereIn('posts.category_id', [1, 2, 3])
 *     ->orderBy('posts.created_at', 'DESC')
 *     ->limit(10)
 *     ->get();
 *
 * // Insert
 * $qb->table('users')->insert([
 *     'name' => 'John',
 *     'email' => 'john@example.com'
 * ]);
 *
 * // Update
 * $qb->table('users')
 *     ->where('id', '=', 5)
 *     ->update(['status' => 'active']);
 *
 * // Delete
 * $qb->table('users')->where('inactive', '=', 1)->delete();
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
class QueryBuilder
{
    /**
     * PDO database connection instance
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Table name for the query
     *
     * @var string
     */
    private string $table = '';

    /**
     * Columns to select in query
     *
     * @var array<int,string>
     */
    private array $columns = ['*'];

    /**
     * JOIN clauses for the query
     *
     * @var array<int,string>
     */
    private array $joins = [];

    /**
     * WHERE clauses for the query
     *
     * @var array<int,array<string,mixed>>
     */
    private array $wheres = [];

    /**
     * GROUP BY columns
     *
     * @var array<int,string>
     */
    private array $groups = [];

    /**
     * HAVING clauses for grouped queries
     *
     * @var array<int,array<string,mixed>>
     */
    private array $havings = [];

    /**
     * ORDER BY clauses
     *
     * @var array<int,array<string,string>>
     */
    private array $orders = [];

    /**
     * LIMIT clause value
     *
     * @var int|null
     */
    private ?int $limit = null;

    /**
     * OFFSET clause value
     *
     * @var int|null
     */
    private ?int $offset = null;

    /**
     * Whether to use DISTINCT in SELECT
     *
     * @var bool
     */
    private bool $distinct = false;

    /**
     * Parameter bindings organized by clause type
     *
     * Separating bindings ensures correct parameter order in final query.
     *
     * @var array<string,array<int,mixed>>
     */
    private array $bindings = [
        'select' => [],
        'join'   => [],
        'where'  => [],
        'having' => [],
        'order'  => [],
    ];

    /**
     * Initialize query builder with PDO connection
     *
     * @param PDO $pdo PDO database connection instance
     *
     * @since 1.0.0
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Set the table for the query
     *
     * Specifies which database table the query will operate on.
     * Must be called before executing the query.
     *
     * @param string $table Table name
     *
     * @return self Returns self for method chaining
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $qb->table('users')->where('active', 1)->get();
     * ```
     */
    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    // ---------------------------------------------------------------------
    // SELECT & MODIFIERS
    // ---------------------------------------------------------------------

    /**
     * Specify columns to select
     *
     * Sets which columns to retrieve in SELECT query. Accepts array or
     * multiple string arguments. Defaults to '*' (all columns).
     *
     * @param array<int,string>|string $columns Column names or array of names
     *
     * @return self Returns self for method chaining
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $qb->table('users')->select(['id', 'name', 'email'])->get();
     * $qb->table('users')->select('id', 'name', 'email')->get();
     * ```
     */
    public function select(array|string $columns = ['*']): self
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Apply DISTINCT to SELECT query
     *
     * Ensures only unique rows are returned in the result set.
     *
     * @return self Returns self for method chaining
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $qb->table('orders')->select('customer_id')->distinct()->get();
     * ```
     */
    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    // ---------------------------------------------------------------------
    // JOINS
    // ---------------------------------------------------------------------

    /**
     * Add JOIN clause to query
     *
     * Joins another table using specified condition. Supports INNER, LEFT,
     * RIGHT, and other join types.
     *
     * @param string $table    Table name to join
     * @param string $first    First column in join condition
     * @param string $operator Comparison operator (=, !=, <, >, etc.)
     * @param string $second   Second column in join condition
     * @param string $type     Join type (INNER, LEFT, RIGHT, etc.)
     *
     * @return self Returns self for method chaining
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $qb->table('posts')
     *    ->join('users', 'posts.user_id', '=', 'users.id', 'INNER')
     *    ->get();
     * ```
     */
    public function join(
        string $table,
        string $first,
        string $operator,
        string $second,
        string $type = 'INNER'
    ): self
    {
        $this->joins[] = "$type JOIN $table ON $first $operator $second";
        return $this;
    }

    /**
     * Add LEFT JOIN clause
     *
     * Shorthand for LEFT JOIN. Returns all rows from left table and
     * matching rows from right table.
     *
     * @param string $table    Table to join
     * @param string $first    First column in condition
     * @param string $operator Comparison operator
     * @param string $second   Second column in condition
     *
     * @return self Returns self for method chaining
     *
     * @since 1.0.0
     */
    public function leftJoin(
        string $table,
        string $first,
        string $operator,
        string $second
    ): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add RIGHT JOIN clause
     *
     * Shorthand for RIGHT JOIN. Returns all rows from right table and
     * matching rows from left table.
     *
     * @param string $table    Table to join
     * @param string $first    First column in condition
     * @param string $operator Comparison operator
     * @param string $second   Second column in condition
     *
     * @return self Returns self for method chaining
     *
     * @since 1.0.0
     */
    public function rightJoin(
        string $table,
        string $first,
        string $operator,
        string $second
    ): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    // ---------------------------------------------------------------------
    // WHERE CLAUSES
    // ---------------------------------------------------------------------

    /**
     * Add WHERE clause to query
     *
     * Adds a where condition with automatic parameter binding. Supports
     * shorthand where('col', value) which defaults to equality comparison.
     *
     * @param string $column   Column name
     * @param string $operator Comparison operator or value if 2 args
     * @param mixed  $value    Value to compare (optional if operator is value)
     * @param string $boolean  Logical operator (AND/OR)
     *
     * @return self Returns self for method chaining
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $qb->where('age', '>', 18);
     * $qb->where('status', 'active'); // Defaults to =
     * $qb->where('price', '<=', 100);
     * ```
     */
    public function where(
        string $column,
        mixed $operator,
        mixed $value = null,
        string $boolean = 'AND'
    ): self
	{
		// Allow shorthand: where('id', 1) defaults to where('id', '=', 1)
		if (func_num_args() === 2) {
			$value = $operator;
			$operator = '=';
		}

		$this->wheres[] = [
			'type' => 'Basic',
			'column' => $column,
			'operator' => (string) $operator,
			'boolean' => $boolean
		];
		$this->bindings['where'][] = $value;

		return $this;
	}

	public function orWhere(string $column, mixed $operator, mixed $value = null): self
	{
		if (func_num_args() === 2) {
			$value = $operator;
			$operator = '=';
		}
		return $this->where($column, (string) $operator, $value, 'OR');
	}
	
	public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): self
	{
		$type = $not ? 'NotIn' : 'In';
		$placeholders = implode(', ', array_fill(0, count($values), '?'));
		
		$this->wheres[] = [
			'type' => $type,
			'column' => $column,
			'placeholders' => $placeholders,
			'boolean' => $boolean
		];
		
		foreach ($values as $value) {
			$this->bindings['where'][] = $value;
		}
		
		return $this;
	}
	
	public function whereNotIn(string $column, array $values, string $boolean = 'AND'): self
	{
		return $this->whereIn($column, $values, $boolean, true);
	}
	
	public function whereNull(string $column, string $boolean = 'AND', bool $not = false): self
	{
		$type = $not ? 'NotNull' : 'Null';
		$this->wheres[] = [
			'type' => $type,
			'column' => $column,
			'boolean' => $boolean
		];
		return $this;
	}
	
	public function whereNotNull(string $column, string $boolean = 'AND'): self
	{
		return $this->whereNull($column, $boolean, true);
	}
	
	public function whereBetween(string $column, array $values, string $boolean = 'AND', bool $not = false): self
	{
		$type = $not ? 'NotBetween' : 'Between';
		$this->wheres[] = [
			'type' => $type,
			'column' => $column,
			'boolean' => $boolean
		];
		$this->bindings['where'][] = $values[0];
		$this->bindings['where'][] = $values[1];
		return $this;
	}
	
	public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): self
	{
		$this->wheres[] = [
			'type' => 'Raw',
			'sql' => $sql,
			'boolean' => $boolean
		];
		$this->bindings['where'] = array_merge($this->bindings['where'], $bindings);
		return $this;
	}
	
	// -------------------------------------------------------------------------
	// GROUP BY & HAVING
	// -------------------------------------------------------------------------
	
	public function groupBy(string ...$groups): self
	{
		$this->groups = array_merge($this->groups, $groups);
		return $this;
	}
	
	public function having(string $column, string $operator, mixed $value, string $boolean = 'AND'): self
	{
		$this->havings[] = compact('column', 'operator', 'boolean');
		$this->bindings['having'][] = $value;
		return $this;
	}
	
	// -------------------------------------------------------------------------
	// ORDERING, LIMIT & OFFSET
	// -------------------------------------------------------------------------
	
	public function orderBy(string $column, string $direction = 'ASC'): self
	{
		$this->orders[] = compact('column', 'direction');
		return $this;
	}
	
	public function latest(string $column = 'created_at'): self
	{
		return $this->orderBy($column, 'DESC');
	}
	
	public function oldest(string $column = 'created_at'): self
	{
		return $this->orderBy($column, 'ASC');
	}
	
	public function inRandomOrder(): self
	{
		return $this->orderBy('RAND()');
	}
	
	public function limit(int $value): self
	{
		$this->limit = $value;
		return $this;
	}
	
	public function offset(int $value): self
	{
		$this->offset = $value;
		return $this;
	}
	
	// -------------------------------------------------------------------------
	// EXECUTION: READ
	// -------------------------------------------------------------------------
	
	public function get(): array
	{
		$statement = $this->executeQuery();
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function first(): ?array
	{
		$this->limit(1);
		$result = $this->get();
		return $result[0] ?? null;
	}
	
	public function find(mixed $id, string $primaryKey = 'id'): ?array
	{
		return $this->where($primaryKey, '=', $id)->first();
	}
	
	public function value(string $column): mixed
	{
		$result = $this->first();
		return $result ? $result[$column] : null;
	}
	
	public function pluck(string $column, ?string $key = null): array
	{
		$results = $this->get();
		$plucked = [];
		foreach ($results as $row) {
			if ($key && isset($row[$key])) {
				$plucked[$row[$key]] = $row[$column];
			} else {
				$plucked[] = $row[$column];
			}
		}
		return $plucked;
	}
	
	public function exists(): bool
	{
		$this->limit(1);
		return !empty($this->get());
	}
	
	public function paginate(int $perPage = 15, int $page = 1): array
	{
		// Get total count (ignoring limit/offset)
		$totalBuilder = clone $this;
		$total = $totalBuilder->count();
		
		// Get paginated results
		$this->limit($perPage);
		$this->offset(($page - 1) * $perPage);
		$data = $this->get();
		
		return [
			'data' => $data,
			'total' => $total,
			'per_page' => $perPage,
			'current_page' => $page,
			'last_page' => ceil($total / $perPage)
		];
	}
	
	// -------------------------------------------------------------------------
	// AGGREGATES
	// -------------------------------------------------------------------------
	
	public function count(string $column = '*'): int
	{
		return (int) $this->aggregate('COUNT', $column);
	}
	
	public function max(string $column): mixed
	{
		return $this->aggregate('MAX', $column);
	}
	
	public function min(string $column): mixed
	{
		return $this->aggregate('MIN', $column);
	}
	
	public function avg(string $column): mixed
	{
		return $this->aggregate('AVG', $column);
	}
	
	public function sum(string $column): mixed
	{
		return $this->aggregate('SUM', $column);
	}
	
	private function aggregate(string $function, string $column): mixed
	{
		$this->columns = ["$function($column) as aggregate"];
		$result = $this->get();
		return $result[0]['aggregate'] ?? 0;
	}
	
	// -------------------------------------------------------------------------
	// EXECUTION: WRITE (CRUD)
	// -------------------------------------------------------------------------
	
	public function insert(array $values): bool
	{
		if (empty($values)) return false;
		
		// Handle single or batch insert
		$isBatch = isset($values[0]) && is_array($values[0]);
		$rows = $isBatch ? $values : [$values];
		$columns = array_keys($rows[0]);
		
		$columnList = implode(', ', $columns);
		$placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
		$placeholderList = implode(', ', array_fill(0, count($rows), $placeholders));
		
		$sql = "INSERT INTO {$this->table} ($columnList) VALUES $placeholderList";
		
		$bindings = [];
		foreach ($rows as $row) {
			foreach ($row as $value) {
				$bindings[] = $value;
			}
		}
		
		$stmt = $this->pdo->prepare($sql);
		return $stmt->execute($bindings);
	}
	
	public function insertGetId(array $values): int|string|false
	{
		if ($this->insert($values)) {
			return $this->pdo->lastInsertId();
		}
		return false;
	}
	
	public function update(array $values): bool
	{
		$setClause = implode(', ', array_map(fn($col) => "$col = ?", array_keys($values)));
		$sql = "UPDATE {$this->table} SET $setClause";
		
		$sql .= $this->compileWheres();
		
		// Update values come first, then where bindings
		$bindings = array_merge(array_values($values), $this->bindings['where']);
		
		$stmt = $this->pdo->prepare($sql);
		return $stmt->execute($bindings);
	}
	
	public function delete(): bool
	{
		$sql = "DELETE FROM {$this->table}" . $this->compileWheres();
		$stmt = $this->pdo->prepare($sql);
		return $stmt->execute($this->bindings['where']);
	}
	
	public function truncate(): bool
	{
		$stmt = $this->pdo->prepare("TRUNCATE TABLE {$this->table}");
		return $stmt->execute();
	}
	
	// -------------------------------------------------------------------------
	// INTERNALS & COMPILATION
	// -------------------------------------------------------------------------
	
	private function executeQuery(): \PDOStatement
	{
		$sql = $this->toSql();
		$bindings = $this->getBindings();
		
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($bindings);
		return $stmt;
	}
	
	public function toSql(): string
	{
		$sql = "SELECT " . ($this->distinct ? 'DISTINCT ' : '') . implode(', ', $this->columns) . " FROM {$this->table}";
		
		$sql .= $this->compileJoins();
		$sql .= $this->compileWheres();
		$sql .= $this->compileGroups();
		$sql .= $this->compileHavings();
		$sql .= $this->compileOrders();
		$sql .= $this->compileLimit();
		
		return $sql;
	}
	
	public function getBindings(): array
	{
		return array_merge(
			$this->bindings['join'],
			$this->bindings['where'],
			$this->bindings['having'],
			$this->bindings['order']
		);
	}
	
	/**
	 * Debugging helper to dump SQL and bindings.
	 */
	public function dump(): void
	{
		echo "SQL: " . $this->toSql() . "\n";
		echo "Bindings: " . print_r($this->getBindings(), true);
		die;
	}
	
	private function compileJoins(): string
	{
		return empty($this->joins) ? '' : ' ' . implode(' ', $this->joins);
	}
	
	private function compileWheres(): string
	{
		if (empty($this->wheres)) {
			return '';
		}
		
		$sql = [];
		foreach ($this->wheres as $i => $where) {
			// First clause doesn't need boolean (AND/OR)
			$boolean = $i === 0 ? 'WHERE' : $where['boolean'];
			
			$sql[] = match ($where['type']) {
				'Basic' => "$boolean {$where['column']} {$where['operator']} ?",
				'In' => "$boolean {$where['column']} IN ({$where['placeholders']})",
				'NotIn' => "$boolean {$where['column']} NOT IN ({$where['placeholders']})",
				'Null' => "$boolean {$where['column']} IS NULL",
				'NotNull' => "$boolean {$where['column']} IS NOT NULL",
				'Between' => "$boolean {$where['column']} BETWEEN ? AND ?",
				'NotBetween' => "$boolean {$where['column']} NOT BETWEEN ? AND ?",
				'Raw' => "$boolean {$where['sql']}",
				default => ''
			};
		}
		
		return ' ' . implode(' ', $sql);
	}
	
	private function compileGroups(): string
	{
		return empty($this->groups) ? '' : ' GROUP BY ' . implode(', ', $this->groups);
	}
	
	private function compileHavings(): string
	{
		if (empty($this->havings)) return '';
		
		$sql = [];
		foreach ($this->havings as $i => $having) {
			$boolean = $i === 0 ? 'HAVING' : $having['boolean'];
			$sql[] = "$boolean {$having['column']} {$having['operator']} ?";
		}
		return ' ' . implode(' ', $sql);
	}
	
	private function compileOrders(): string
	{
		if (empty($this->orders)) return '';
		
		$sql = [];
		foreach ($this->orders as $order) {
			$sql[] = "{$order['column']} {$order['direction']}";
		}
		return ' ORDER BY ' . implode(', ', $sql);
	}
	
	private function compileLimit(): string
	{
		$sql = '';
		if ($this->limit) {
			$sql .= " LIMIT {$this->limit}";
		}
		if ($this->offset) {
			$sql .= " OFFSET {$this->offset}";
		}
		return $sql;
	}
}