<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Vite;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ViteTest extends TestCase
{
    private string $buildPath;

    protected function setUp(): void
    {
        $this->buildPath = sys_get_temp_dir() . '/omniophp-vite-test-' . uniqid();
        mkdir($this->buildPath, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->buildPath . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->buildPath);
    }

    public function testTagsUsesDevServerWhenHotFileExists(): void
    {
        file_put_contents($this->buildPath . '/hot', 'http://localhost:5173');

        $vite = new Vite($this->buildPath);
        $tags = $vite->tags('resources/assets/js/app.js');

        $this->assertStringContainsString('http://localhost:5173/@vite/client', $tags);
        $this->assertStringContainsString('http://localhost:5173/resources/assets/js/app.js', $tags);
    }

    public function testIsRunningHotReflectsHotFilePresence(): void
    {
        $vite = new Vite($this->buildPath);
        $this->assertFalse($vite->isRunningHot());

        file_put_contents($this->buildPath . '/hot', 'http://localhost:5173');
        $this->assertTrue($vite->isRunningHot());
    }

    public function testTagsResolvesFromManifestWhenNotHot(): void
    {
        file_put_contents($this->buildPath . '/manifest.json', json_encode([
            'resources/assets/js/app.js' => [
                'file' => 'assets/app-abc123.js',
                'css' => ['assets/app-def456.css'],
            ],
        ]));

        $vite = new Vite($this->buildPath, '/assets/build');
        $tags = $vite->tags('resources/assets/js/app.js');

        $this->assertStringContainsString('<link rel="stylesheet" href="/assets/build/assets/app-def456.css">', $tags);
        $this->assertStringContainsString('<script type="module" src="/assets/build/assets/app-abc123.js"></script>', $tags);
    }

    public function testTagsThrowsWhenManifestMissing(): void
    {
        $vite = new Vite($this->buildPath);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Vite manifest not found');

        $vite->tags('resources/assets/js/app.js');
    }

    public function testTagsThrowsWhenEntryMissingFromManifest(): void
    {
        file_put_contents($this->buildPath . '/manifest.json', json_encode([
            'resources/assets/js/app.js' => ['file' => 'assets/app-abc123.js'],
        ]));

        $vite = new Vite($this->buildPath);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not found in Vite manifest');

        $vite->tags('resources/assets/js/missing.js');
    }
}
