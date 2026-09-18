<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Database\Blueprint;
use Core\Model\Database\Schema;
use PDO;
use PHPUnit\Framework\TestCase;

final class SchemaTest extends TestCase
{
    private PDO $pdo;
    private Schema $schema;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->schema = new Schema($this->pdo);
    }

    public function testCreateAndHasTable(): void
    {
        $this->assertFalse($this->schema->hasTable('users'));

        $this->schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
        });

        $this->assertTrue($this->schema->hasTable('users'));
    }

    public function testCreatedTableEnforcesConstraints(): void
    {
        $this->schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
        });

        $this->pdo->exec("INSERT INTO users (email) VALUES ('a@example.com')");

        $this->expectException(\PDOException::class);
        $this->pdo->exec("INSERT INTO users (email) VALUES ('a@example.com')");
    }

    public function testDropIfExists(): void
    {
        $this->schema->create('temp', fn (Blueprint $table) => $table->id());
        $this->assertTrue($this->schema->hasTable('temp'));

        $this->schema->dropIfExists('temp');
        $this->assertFalse($this->schema->hasTable('temp'));

        // Should not throw when the table is already gone.
        $this->schema->dropIfExists('temp');
        $this->assertFalse($this->schema->hasTable('temp'));
    }

    public function testDefaultValuesApplied(): void
    {
        $this->schema->create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('enabled')->default(true);
        });

        $this->pdo->exec("INSERT INTO settings (name) VALUES ('feature')");
        $row = $this->pdo->query('SELECT enabled FROM settings')->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals(1, $row['enabled']);
    }
}
