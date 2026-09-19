<?php

declare(strict_types=1);

namespace Tests\Integration;

use Core\Model\App;
use Core\Model\Auth;
use Core\Model\Database\Database;
use Core\Model\Logger;
use Core\Model\Session;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Exercises Auth::register()/login()/identityExists() against a real MySQL
 * connection - the success paths Tests/Feature/AuthRoutesTest.php explicitly
 * can't cover without a live database.
 *
 * Requires the `mysql` docker-compose service (or an equivalent reachable at
 * Configuration/config.env.testing's DB_HOST/DB_PORT). Database's
 * constructor calls Error::terminateWithError() - which exits the process -
 * on connection failure, so this class probes connectivity with a raw PDO
 * first and self-skips instead of ever letting Database touch an
 * unreachable server.
 */
final class AuthIntegrationTest extends TestCase
{
    private static bool $available = false;

    private Database $db;
    private Auth $auth;

    public static function setUpBeforeClass(): void
    {
        $host = App::config('DB_HOST');
        $port = App::config('DB_PORT');
        $name = App::config('DB_NAME');
        $user = App::config('DB_USERNAME');
        $pass = App::config('DB_PASSWORD');

        try {
            $pdo = new PDO(
                "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
                $user,
                $pass,
                [PDO::ATTR_TIMEOUT => 2]
            );

            // Database/Migration's Schema+Blueprint DDL builder only targets
            // SQLite (see ColumnDefinition::toSql()'s "INTEGER PRIMARY KEY
            // AUTOINCREMENT"), so the users table is created here with plain
            // MySQL-flavored SQL mirroring
            // database/migrations/2026_09_19_120000_create_users_table.php
            // instead of going through Schema.
            $pdo->exec('DROP TABLE IF EXISTS users');
            $pdo->exec(<<<'SQL'
                CREATE TABLE users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    remember_token VARCHAR(255) NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL
                )
                SQL);
        } catch (PDOException $e) {
            self::$available = false;
            return;
        }

        self::$available = true;
    }

    protected function setUp(): void
    {
        if (!self::$available) {
            self::markTestSkipped('No MySQL connection available (see Configuration/config.env.testing).');
        }

        $this->db = new Database(new Logger());
        $this->db->executeQuery('DELETE FROM users');
        $this->auth = new Auth($this->db, new Session());
    }

    public function testRegisterCreatesUserAndHashesPassword(): void
    {
        $userId = $this->auth->register([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'secret123',
        ]);

        $this->assertIsInt($userId);

        $row = $this->db->fetchOneNamed(
            'SELECT * FROM users WHERE id = :id',
            [':id' => $userId]
        );
        $this->assertSame('ada@example.com', $row['email']);
        $this->assertNotSame('secret123', $row['password']);
        $this->assertTrue(password_verify('secret123', $row['password']));
    }

    public function testIdentityExistsReflectsRegisteredUsers(): void
    {
        $this->assertFalse($this->auth->identityExists('ada@example.com'));

        $this->auth->register([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'secret123',
        ]);

        $this->assertTrue($this->auth->identityExists('ada@example.com'));
        $this->assertFalse($this->auth->identityExists('nobody@example.com'));
    }

    public function testLoginSucceedsWithCorrectCredentialsAndFailsWithWrongPassword(): void
    {
        $this->auth->register([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'secret123',
        ]);

        $this->assertFalse($this->auth->login('ada@example.com', 'wrong-password'));
        $this->assertTrue($this->auth->login('ada@example.com', 'secret123'));
        $this->assertTrue($this->auth->check());

        $user = $this->auth->user();
        $this->assertSame('ada@example.com', $user['email']);
    }
}
