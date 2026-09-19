<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Cache;
use PHPUnit\Framework\TestCase;

final class CacheTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/omniophp-cache-test-' . uniqid();
        Cache::resetGlobalStats();
    }

    protected function tearDown(): void
    {
        Cache::resetGlobalStats();

        if (is_dir($this->cacheDir)) {
            $this->removeDirectory($this->cacheDir);
        }
    }

    public function testGlobalStatsAggregateHitsMissesWritesAcrossInstances(): void
    {
        $cacheA = new Cache('file', ['cache_dir' => $this->cacheDir]);
        $cacheB = new Cache('file', ['cache_dir' => $this->cacheDir]);

        $cacheA->put('greeting', 'hello');
        $cacheB->get('greeting');
        $cacheA->get('missing-key');

        $stats = Cache::getGlobalStats();

        $this->assertSame(1, $stats['writes']);
        $this->assertSame(1, $stats['hits']);
        $this->assertSame(1, $stats['misses']);
    }

    public function testGlobalStatsCountDeletes(): void
    {
        $cache = new Cache('file', ['cache_dir' => $this->cacheDir]);
        $cache->put('key', 'value');
        $cache->forget('key');

        $this->assertSame(1, Cache::getGlobalStats()['deletes']);
    }

    public function testResetGlobalStatsClearsCounters(): void
    {
        $cache = new Cache('file', ['cache_dir' => $this->cacheDir]);
        $cache->put('key', 'value');
        $cache->get('key');

        Cache::resetGlobalStats();

        $this->assertSame(
            ['hits' => 0, 'misses' => 0, 'writes' => 0, 'deletes' => 0],
            Cache::getGlobalStats()
        );
    }

    public function testInstanceStatsAreIndependentOfGlobalStats(): void
    {
        $cacheA = new Cache('file', ['cache_dir' => $this->cacheDir]);
        $cacheB = new Cache('file', ['cache_dir' => $this->cacheDir]);

        $cacheA->put('a', 1);
        $cacheB->put('b', 1);
        $cacheB->put('c', 1);

        $this->assertSame(1, $cacheA->getStats()['writes']);
        $this->assertSame(2, $cacheB->getStats()['writes']);
        $this->assertSame(3, Cache::getGlobalStats()['writes']);
    }

    private function removeDirectory(string $dir): void
    {
        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
