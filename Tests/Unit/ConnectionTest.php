<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Database\Connection;
use PDO;
use PHPUnit\Framework\TestCase;

final class ConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        Connection::resetPool();
    }

    protected function tearDown(): void
    {
        Connection::resetPool();
    }

    public function testSqliteDsn(): void
    {
        $this->assertSame(
            'sqlite:/var/data/app.db',
            Connection::dsn(['DB_TYPE' => 'sqlite', 'DB_PATH' => '/var/data/app.db'])
        );
    }

    public function testSqliteDsnDefaultsToMemory(): void
    {
        $this->assertSame('sqlite::memory:', Connection::dsn(['DB_TYPE' => 'sqlite']));
    }

    public function testMysqlDsn(): void
    {
        $dsn = Connection::dsn([
            'DB_TYPE' => 'mysql',
            'DB_HOST' => 'db.example.com',
            'DB_PORT' => 3307,
            'DB_NAME' => 'shop',
            'DB_CHARSET' => 'utf8mb4',
        ]);

        $this->assertSame('mysql:host=db.example.com;port=3307;dbname=shop;charset=utf8mb4', $dsn);
    }

    public function testPgsqlDsn(): void
    {
        $dsn = Connection::dsn([
            'DB_TYPE' => 'pgsql',
            'DB_HOST' => 'localhost',
            'DB_PORT' => 5432,
            'DB_NAME' => 'analytics',
        ]);

        $this->assertSame('pgsql:host=localhost;port=5432;dbname=analytics', $dsn);
    }

    public function testUnsupportedDriverThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Connection::dsn(['DB_TYPE' => 'oracle']);
    }

    public function testMakeCreatesUsableSqlitePdo(): void
    {
        $pdo = Connection::make(['DB_TYPE' => 'sqlite', 'DB_PATH' => ':memory:']);

        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertSame(
            PDO::ERRMODE_EXCEPTION,
            $pdo->getAttribute(PDO::ATTR_ERRMODE)
        );
    }

    public function testMakeReturnsThePooledConnectionForAnIdenticalConfig(): void
    {
        $config = ['DB_TYPE' => 'sqlite', 'DB_PATH' => ':memory:'];

        $first = Connection::make($config);
        $second = Connection::make($config);

        $this->assertSame($first, $second);
        $this->assertSame(1, Connection::poolSize());
    }

    public function testMakeOpensADistinctConnectionForADifferentConfig(): void
    {
        $memory = Connection::make(['DB_TYPE' => 'sqlite', 'DB_PATH' => ':memory:']);
        $file = Connection::make(['DB_TYPE' => 'sqlite', 'DB_PATH' => sys_get_temp_dir() . '/omniophp-pool-test.sqlite']);

        $this->assertNotSame($memory, $file);
        $this->assertSame(2, Connection::poolSize());

        @unlink(sys_get_temp_dir() . '/omniophp-pool-test.sqlite');
    }

    public function testResetPoolClearsCachedConnections(): void
    {
        Connection::make(['DB_TYPE' => 'sqlite', 'DB_PATH' => ':memory:']);
        $this->assertSame(1, Connection::poolSize());

        Connection::resetPool();

        $this->assertSame(0, Connection::poolSize());
    }
}
