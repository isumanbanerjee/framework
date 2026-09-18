<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Database\Blueprint;
use PHPUnit\Framework\TestCase;

final class BlueprintTest extends TestCase
{
    public function testIdProducesAutoIncrementPrimaryKey(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->id();

        $this->assertStringContainsString('id INTEGER PRIMARY KEY AUTOINCREMENT', $blueprint->toSql());
    }

    public function testStringColumnWithLength(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('email', 191);

        $this->assertStringContainsString('email VARCHAR(191) NOT NULL', $blueprint->toSql());
    }

    public function testNullableModifier(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('nickname')->nullable();

        $this->assertStringContainsString('nickname VARCHAR(255) NULL', $blueprint->toSql());
    }

    public function testUniqueModifier(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('email')->unique();

        $this->assertStringContainsString('email VARCHAR(255) NOT NULL UNIQUE', $blueprint->toSql());
    }

    public function testDefaultValues(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->boolean('is_admin')->default(false);
        $blueprint->integer('score')->default(10);
        $blueprint->string('role')->default('member');

        $sql = $blueprint->toSql();
        $this->assertStringContainsString('is_admin BOOLEAN NOT NULL DEFAULT 0', $sql);
        $this->assertStringContainsString('score INTEGER NOT NULL DEFAULT 10', $sql);
        $this->assertStringContainsString("role VARCHAR(255) NOT NULL DEFAULT 'member'", $sql);
    }

    public function testDefaultRawCurrentTimestamp(): void
    {
        $blueprint = new Blueprint('logs');
        $blueprint->timestamp('created_at')->default('CURRENT_TIMESTAMP');

        $this->assertStringContainsString('created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP', $blueprint->toSql());
    }

    public function testTimestampsHelperAddsBothColumns(): void
    {
        $blueprint = new Blueprint('posts');
        $blueprint->timestamps();

        $sql = $blueprint->toSql();
        $this->assertStringContainsString('created_at TIMESTAMP NULL', $sql);
        $this->assertStringContainsString('updated_at TIMESTAMP NULL', $sql);
    }

    public function testColumnTypes(): void
    {
        $blueprint = new Blueprint('mixed');
        $blueprint->text('body');
        $blueprint->bigInteger('views');
        $blueprint->decimal('price', 10, 2);
        $blueprint->date('published_on');
        $blueprint->dateTime('reviewed_at');

        $sql = $blueprint->toSql();
        $this->assertStringContainsString('body TEXT', $sql);
        $this->assertStringContainsString('views BIGINT', $sql);
        $this->assertStringContainsString('price DECIMAL(10,2)', $sql);
        $this->assertStringContainsString('published_on DATE', $sql);
        $this->assertStringContainsString('reviewed_at DATETIME', $sql);
    }

    public function testCreateTableWrapping(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->id();
        $blueprint->string('email');

        $sql = $blueprint->toSql();
        $this->assertStringStartsWith('CREATE TABLE users (', $sql);
        $this->assertStringEndsWith(')', $sql);
    }
}
