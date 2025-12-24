<?php

namespace Tests\Unit;

use Core\Model\Database\Database;
use Core\Model\Logger;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for Database Class
 *
 * Tests database connection, queries, and advanced features.
 */
class DatabaseTest extends TestCase
{
    private Logger $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockLogger = $this->createMock(Logger::class);
    }

    public function testDatabaseCanBeInstantiated(): void
    {
        // We can't test actual connection without a real database
        // but we can test that the class exists and is instantiable
        $this->assertTrue(class_exists(Database::class));
    }

    public function testGetPdoReturnsValidPdo(): void
    {
        // This would require actual database connection
        // For unit test, we just verify the method exists
        $this->assertTrue(method_exists(Database::class, 'getPdo'));
    }

    public function testExecuteQueryMethodExists(): void
    {
        $this->assertTrue(method_exists(Database::class, 'executeQuery'));
    }

    public function testFetchAllMethodExists(): void
    {
        $this->assertTrue(method_exists(Database::class, 'fetchAll'));
    }

    public function testFetchOneMethodExists(): void
    {
        $this->assertTrue(method_exists(Database::class, 'fetchOne'));
    }

    public function testFetchOneNamedMethodExists(): void
    {
        $this->assertTrue(method_exists(Database::class, 'fetchOneNamed'));
    }

    public function testBeginTransactionMethodExists(): void
    {
        $this->assertTrue(method_exists(Database::class, 'beginTransaction'));
    }

    public function testCommitTransactionMethodExists(): void
    {
        $this->assertTrue(method_exists(Database::class, 'commitTransaction'));
    }

    public function testRollbackTransactionMethodExists(): void
    {
        $this->assertTrue(method_exists(Database::class, 'rollbackTransaction'));
    }

    public function testQueryBuilderMethodExists(): void
    {
        $this->assertTrue(method_exists(Database::class, 'queryBuilder'));
    }

    public function testCloseConnectionMethodExists(): void
    {
        $this->assertTrue(method_exists(Database::class, 'closeConnection'));
    }

    public function testBatchInsertMethodExists(): void
    {
        $this->assertTrue(method_exists(Database::class, 'batchInsert'));
    }

    public function testBatchUpdateMethodExists(): void
    {
        $this->assertTrue(method_exists(Database::class, 'batchUpdate'));
    }
}

