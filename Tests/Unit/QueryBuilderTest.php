<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Database\QueryBuilder;
use PDO;
use PHPUnit\Framework\TestCase;

final class QueryBuilderTest extends TestCase
{
    private function builder(): QueryBuilder
    {
        return new QueryBuilder(new PDO('sqlite::memory:'));
    }

    public function testBasicSelect(): void
    {
        $qb = $this->builder()->table('users');
        $this->assertSame('SELECT * FROM users', $qb->toSql());
        $this->assertSame([], $qb->getBindings());
    }

    public function testSelectWithColumnsAndDistinct(): void
    {
        $qb = $this->builder()->table('users')->select(['id', 'name'])->distinct();
        $this->assertSame('SELECT DISTINCT id, name FROM users', $qb->toSql());
    }

    public function testWhereShorthandDefaultsToEquals(): void
    {
        $qb = $this->builder()->table('users')->where('status', 'active');
        $this->assertSame('SELECT * FROM users WHERE status = ?', $qb->toSql());
        $this->assertSame(['active'], $qb->getBindings());
    }

    public function testWhereShorthandAcceptsNonStringValue(): void
    {
        $qb = $this->builder()->table('users')->where('id', 1);
        $this->assertSame('SELECT * FROM users WHERE id = ?', $qb->toSql());
        $this->assertSame([1], $qb->getBindings());
    }

    public function testOrWhereShorthandAcceptsNonStringValue(): void
    {
        $qb = $this->builder()->table('users')
            ->where('age', '>', 18)
            ->orWhere('id', 5);

        $this->assertSame('SELECT * FROM users WHERE age > ? OR id = ?', $qb->toSql());
        $this->assertSame([18, 5], $qb->getBindings());
    }

    public function testWhereWithOperator(): void
    {
        $qb = $this->builder()->table('users')->where('age', '>', 18);
        $this->assertSame('SELECT * FROM users WHERE age > ?', $qb->toSql());
        $this->assertSame([18], $qb->getBindings());
    }

    public function testMultipleWheresChainWithAndByDefault(): void
    {
        $qb = $this->builder()->table('users')
            ->where('age', '>', 18)
            ->where('active', '=', 1);

        $this->assertSame('SELECT * FROM users WHERE age > ? AND active = ?', $qb->toSql());
        $this->assertSame([18, 1], $qb->getBindings());
    }

    public function testOrWhere(): void
    {
        $qb = $this->builder()->table('users')
            ->where('age', '>', 18)
            ->orWhere('vip', '=', 1);

        $this->assertSame('SELECT * FROM users WHERE age > ? OR vip = ?', $qb->toSql());
    }

    public function testWhereInAndNotIn(): void
    {
        $qb = $this->builder()->table('users')->whereIn('id', [1, 2, 3]);
        $this->assertSame('SELECT * FROM users WHERE id IN (?, ?, ?)', $qb->toSql());
        $this->assertSame([1, 2, 3], $qb->getBindings());

        $qb = $this->builder()->table('users')->whereNotIn('id', [1, 2]);
        $this->assertSame('SELECT * FROM users WHERE id NOT IN (?, ?)', $qb->toSql());
    }

    public function testWhereNullAndNotNull(): void
    {
        $qb = $this->builder()->table('users')->whereNull('deleted_at');
        $this->assertSame('SELECT * FROM users WHERE deleted_at IS NULL', $qb->toSql());

        $qb = $this->builder()->table('users')->whereNotNull('deleted_at');
        $this->assertSame('SELECT * FROM users WHERE deleted_at IS NOT NULL', $qb->toSql());
    }

    public function testWhereBetween(): void
    {
        $qb = $this->builder()->table('users')->whereBetween('age', [18, 30]);
        $this->assertSame('SELECT * FROM users WHERE age BETWEEN ? AND ?', $qb->toSql());
        $this->assertSame([18, 30], $qb->getBindings());
    }

    public function testWhereRaw(): void
    {
        $qb = $this->builder()->table('users')->whereRaw('age > ? AND age < ?', [10, 50]);
        $this->assertSame('SELECT * FROM users WHERE age > ? AND age < ?', $qb->toSql());
        $this->assertSame([10, 50], $qb->getBindings());
    }

    public function testJoins(): void
    {
        $qb = $this->builder()->table('posts')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
            ->rightJoin('tags', 'posts.id', '=', 'tags.post_id');

        $this->assertSame(
            'SELECT * FROM posts INNER JOIN users ON posts.user_id = users.id'
            . ' LEFT JOIN comments ON posts.id = comments.post_id'
            . ' RIGHT JOIN tags ON posts.id = tags.post_id',
            $qb->toSql()
        );
    }

    public function testGroupByAndHaving(): void
    {
        $qb = $this->builder()->table('orders')
            ->groupBy('customer_id')
            ->having('total', '>', 100);

        $this->assertSame(
            'SELECT * FROM orders GROUP BY customer_id HAVING total > ?',
            $qb->toSql()
        );
        $this->assertSame([100], $qb->getBindings());
    }

    public function testOrderByLatestOldestAndRandom(): void
    {
        $qb = $this->builder()->table('users')->orderBy('name');
        $this->assertSame('SELECT * FROM users ORDER BY name ASC', $qb->toSql());

        $qb = $this->builder()->table('posts')->latest();
        $this->assertSame('SELECT * FROM posts ORDER BY created_at DESC', $qb->toSql());

        $qb = $this->builder()->table('posts')->oldest();
        $this->assertSame('SELECT * FROM posts ORDER BY created_at ASC', $qb->toSql());

        $qb = $this->builder()->table('posts')->inRandomOrder();
        $this->assertSame('SELECT * FROM posts ORDER BY RAND() ASC', $qb->toSql());
    }

    public function testLimitAndOffset(): void
    {
        $qb = $this->builder()->table('users')->limit(10)->offset(20);
        $this->assertSame('SELECT * FROM users LIMIT 10 OFFSET 20', $qb->toSql());
    }

    public function testBindingOrderAcrossClauses(): void
    {
        $qb = $this->builder()->table('orders')
            ->where('status', '=', 'paid')
            ->groupBy('customer_id')
            ->having('total', '>', 100);

        $this->assertSame(['paid', 100], $qb->getBindings());
    }

    public function testComplexQueryToSql(): void
    {
        $qb = $this->builder()->table('posts')
            ->select(['posts.*', 'users.name'])
            ->leftJoin('users', 'posts.user_id', '=', 'users.id')
            ->where('posts.published', '=', 1)
            ->whereIn('posts.category_id', [1, 2, 3])
            ->orderBy('posts.created_at', 'DESC')
            ->limit(10);

        $this->assertSame(
            'SELECT posts.*, users.name FROM posts'
            . ' LEFT JOIN users ON posts.user_id = users.id'
            . ' WHERE posts.published = ? AND posts.category_id IN (?, ?, ?)'
            . ' ORDER BY posts.created_at DESC LIMIT 10',
            $qb->toSql()
        );
        $this->assertSame([1, 1, 2, 3], $qb->getBindings());
    }
}
