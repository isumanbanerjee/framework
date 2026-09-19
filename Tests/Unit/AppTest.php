<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\App;
use PHPUnit\Framework\TestCase;

final class AppTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/app_test_' . uniqid();
        mkdir($this->dir);
        file_put_contents($this->dir . '/config.env', "APP_ENV=default\n");
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->dir);
    }

    public function testResolveEnvFileFallsBackToPlainConfigWhenAppEnvIsNull(): void
    {
        $this->assertSame(
            $this->dir . '/config.env',
            App::resolveEnvFile($this->dir, null)
        );
    }

    public function testResolveEnvFileFallsBackToPlainConfigWhenAppEnvIsEmpty(): void
    {
        $this->assertSame(
            $this->dir . '/config.env',
            App::resolveEnvFile($this->dir, '')
        );
    }

    public function testResolveEnvFileFallsBackWhenEnvSpecificFileMissing(): void
    {
        $this->assertSame(
            $this->dir . '/config.env',
            App::resolveEnvFile($this->dir, 'staging')
        );
    }

    public function testResolveEnvFilePrefersEnvSpecificFileWhenPresent(): void
    {
        file_put_contents($this->dir . '/config.env.staging', "APP_ENV=staging\n");

        $this->assertSame(
            $this->dir . '/config.env.staging',
            App::resolveEnvFile($this->dir, 'staging')
        );
    }

    public function testResolveEnvFileIsCaseSensitiveToMatchFilesystemNaming(): void
    {
        file_put_contents($this->dir . '/config.env.production', "APP_ENV=production\n");

        $this->assertSame(
            $this->dir . '/config.env',
            App::resolveEnvFile($this->dir, 'Production')
        );
    }
}
