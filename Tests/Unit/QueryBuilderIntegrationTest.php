<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Database\QueryBuilder;
use PDO;
use PHPUnit\Framework\TestCase;

final class QueryBuilderIntegrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, age INTEGER)');
    }

    private function builder(): QueryBuilder
    {
        return new QueryBuilder($this->pdo);
    }

    public function testInsertAndGet(): void
    {
        $this->builder()->table('users')->insert(['name' => 'Alice', 'age' => 30]);

        $rows = $this->builder()->table('users')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
    }

    public function testInsertGetId(): void
    {
        $id = $this->builder()->table('users')->insertGetId(['name' => 'Bob', 'age' => 25]);
        $this->assertEquals(1, $id);
    }

    public function testBatchInsert(): void
    {
        $this->builder()->table('users')->insert([
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Bob', 'age' => 25],
        ]);

        $this->assertSame(2, $this->builder()->table('users')->count());
    }

    public function testFindAndValue(): void
    {
        $id = $this->builder()->table('users')->insertGetId(['name' => 'Carol', 'age' => 40]);

        $found = $this->builder()->table('users')->find($id);
        $this->assertSame('Carol', $found['name']);

        $name = $this->builder()->table('users')->where('id', $id)->value('name');
        $this->assertSame('Carol', $name);
    }

    public function testUpdate(): void
    {
        $id = $this->builder()->table('users')->insertGetId(['name' => 'Dave', 'age' => 20]);
        $this->builder()->table('users')->where('id', $id)->update(['age' => 21]);

        $age = $this->builder()->table('users')->where('id', $id)->value('age');
        $this->assertSame(21, $age);
    }

    public function testDelete(): void
    {
        $id = $this->builder()->table('users')->insertGetId(['name' => 'Eve', 'age' => 50]);
        $this->builder()->table('users')->where('id', $id)->delete();

        $this->assertSame(0, $this->builder()->table('users')->count());
    }

    public function testExists(): void
    {
        $this->assertFalse($this->builder()->table('users')->where('name', 'Ghost')->exists());
        $this->builder()->table('users')->insert(['name' => 'Ghost', 'age' => 99]);
        $this->assertTrue($this->builder()->table('users')->where('name', 'Ghost')->exists());
    }

    public function testAggregates(): void
    {
        $this->builder()->table('users')->insert([
            ['name' => 'A', 'age' => 10],
            ['name' => 'B', 'age' => 20],
            ['name' => 'C', 'age' => 30],
        ]);

        $this->assertSame(3, $this->builder()->table('users')->count());
        $this->assertEquals(30, $this->builder()->table('users')->max('age'));
        $this->assertEquals(10, $this->builder()->table('users')->min('age'));
        $this->assertEquals(60, $this->builder()->table('users')->sum('age'));
        $this->assertEquals(20, $this->builder()->table('users')->avg('age'));
    }

    public function testPaginate(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->builder()->table('users')->insert(['name' => "User$i", 'age' => $i]);
        }

        $result = $this->builder()->table('users')->paginate(10, 2);

        $this->assertCount(10, $result['data']);
        $this->assertSame(25, $result['total']);
        $this->assertEquals(3, $result['last_page']);
        $this->assertSame(2, $result['current_page']);
    }
}
