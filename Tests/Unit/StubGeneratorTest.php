<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\StubGenerator;
use PHPUnit\Framework\TestCase;

final class StubGeneratorTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/stubs_' . uniqid();
        mkdir($this->dir);
        file_put_contents($this->dir . '/greeting.stub', 'Hello {{name}}, welcome to {{app}}!');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') as $file) {
            unlink($file);
        }
        rmdir($this->dir);
    }

    public function testRenderReplacesPlaceholders(): void
    {
        $generator = new StubGenerator($this->dir);
        $result = $generator->render('greeting', ['name' => 'Sam', 'app' => 'OmnioPHP']);

        $this->assertSame('Hello Sam, welcome to OmnioPHP!', $result);
    }

    public function testRenderLeavesUnknownPlaceholdersIntact(): void
    {
        $generator = new StubGenerator($this->dir);
        $result = $generator->render('greeting', ['name' => 'Sam']);

        $this->assertSame('Hello Sam, welcome to {{app}}!', $result);
    }

    public function testMissingStubThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        (new StubGenerator($this->dir))->render('nonexistent', []);
    }

    public function testStudlyFromSnakeCase(): void
    {
        $this->assertSame('CreateUsersTable', StubGenerator::studly('create_users_table'));
        $this->assertSame('UserController', StubGenerator::studly('user-controller'));
        $this->assertSame('Widget', StubGenerator::studly('widget'));
    }

    public function testMigrationFileName(): void
    {
        $this->assertSame(
            '2026_01_28_000001_create_users_table',
            StubGenerator::migrationFileName('create_users_table', '2026_01_28_000001')
        );
    }

    public function testRealControllerStubRenders(): void
    {
        $generator = new StubGenerator(__DIR__ . '/../../resources/stubs');
        $code = $generator->render('controller', ['name' => 'ProductController']);

        $this->assertStringContainsString('class ProductController', $code);
        $this->assertStringContainsString('namespace System\\Controller;', $code);
    }

    public function testRealMigrationStubRenders(): void
    {
        $generator = new StubGenerator(__DIR__ . '/../../resources/stubs');
        $code = $generator->render('migration', ['name' => 'CreateUsersTable', 'table' => 'users']);

        $this->assertStringContainsString('class CreateUsersTable extends Migration', $code);
        $this->assertStringContainsString("\$schema->create('users'", $code);
    }
}
