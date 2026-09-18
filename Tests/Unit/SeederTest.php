<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Database\Seeder;
use PDO;
use PHPUnit\Framework\TestCase;

class SampleUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->table('users')->insert([
            ['email' => 'admin@example.com', 'role' => 'admin'],
            ['email' => 'user@example.com', 'role' => 'member'],
        ]);
    }
}

final class SeederTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT, role TEXT)');
    }

    public function testSeederInsertsRows(): void
    {
        (new SampleUserSeeder($this->pdo))->run();

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $this->assertSame(2, $count);

        $admin = $this->pdo->query("SELECT role FROM users WHERE email = 'admin@example.com'")
            ->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('admin', $admin['role']);
    }
}
