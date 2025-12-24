<?php

namespace Tests\Unit;

use Core\Model\Cache;
use PHPUnit\Framework\TestCase;

/**
 * Cache System Tests
 *
 * Tests for multi-driver cache system
 */
class CacheTest extends TestCase
{
    private Cache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new Cache('file', [
            'cache_dir' => sys_get_temp_dir() . '/test_cache_' . uniqid()
        ]);
    }

    protected function tearDown(): void
    {
        $this->cache->flush();
        parent::tearDown();
    }

    public function testCacheCanStoreAndRetrieveData(): void
    {
        $this->cache->put('test_key', 'test_value', 60);
        $this->assertEquals('test_value', $this->cache->get('test_key'));
    }

    public function testCacheReturnsDefaultWhenKeyNotFound(): void
    {
        $this->assertEquals('default', $this->cache->get('nonexistent', 'default'));
    }

    public function testCacheCanStoreArrays(): void
    {
        $data = ['name' => 'John', 'age' => 30];
        $this->cache->put('user', $data, 60);
        $this->assertEquals($data, $this->cache->get('user'));
    }

    public function testCacheCanStoreObjects(): void
    {
        $object = new \stdClass();
        $object->name = 'Test';
        $this->cache->put('object', $object, 60);
        
        $retrieved = $this->cache->get('object');
        $this->assertEquals('Test', $retrieved->name);
    }

    public function testCacheHasMethod(): void
    {
        $this->cache->put('exists', 'value', 60);
        $this->assertTrue($this->cache->has('exists'));
        $this->assertFalse($this->cache->has('notexists'));
    }

    public function testCacheForgetMethod(): void
    {
        $this->cache->put('key', 'value', 60);
        $this->assertTrue($this->cache->has('key'));
        
        $this->cache->forget('key');
        $this->assertFalse($this->cache->has('key'));
    }

    public function testCacheRememberMethod(): void
    {
        $callCount = 0;
        
        $value = $this->cache->remember('test', 60, function() use (&$callCount) {
            $callCount++;
            return 'computed_value';
        });
        
        $this->assertEquals('computed_value', $value);
        $this->assertEquals(1, $callCount);
        
        // Second call should use cache
        $value2 = $this->cache->remember('test', 60, function() use (&$callCount) {
            $callCount++;
            return 'computed_value';
        });
        
        $this->assertEquals('computed_value', $value2);
        $this->assertEquals(1, $callCount); // Callback not called again
    }

    public function testCacheForeverMethod(): void
    {
        $this->cache->forever('permanent', 'value');
        $this->assertEquals('value', $this->cache->get('permanent'));
    }

    public function testCacheIncrementMethod(): void
    {
        $this->cache->put('counter', 5, 60);
        $this->cache->increment('counter');
        $this->assertEquals(6, $this->cache->get('counter'));
        
        $this->cache->increment('counter', 5);
        $this->assertEquals(11, $this->cache->get('counter'));
    }

    public function testCacheDecrementMethod(): void
    {
        $this->cache->put('counter', 10, 60);
        $this->cache->decrement('counter');
        $this->assertEquals(9, $this->cache->get('counter'));
        
        $this->cache->decrement('counter', 5);
        $this->assertEquals(4, $this->cache->get('counter'));
    }

    public function testCachePutManyMethod(): void
    {
        $this->cache->putMany([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3'
        ], 60);
        
        $this->assertEquals('value1', $this->cache->get('key1'));
        $this->assertEquals('value2', $this->cache->get('key2'));
        $this->assertEquals('value3', $this->cache->get('key3'));
    }

    public function testCacheManyMethod(): void
    {
        $this->cache->put('a', 1, 60);
        $this->cache->put('b', 2, 60);
        $this->cache->put('c', 3, 60);
        
        $values = $this->cache->many(['a', 'b', 'c']);
        
        $this->assertEquals(1, $values['a']);
        $this->assertEquals(2, $values['b']);
        $this->assertEquals(3, $values['c']);
    }

    public function testCacheFlushMethod(): void
    {
        $this->cache->put('key1', 'value1', 60);
        $this->cache->put('key2', 'value2', 60);
        
        $this->assertTrue($this->cache->has('key1'));
        $this->assertTrue($this->cache->has('key2'));
        
        $this->cache->flush();
        
        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
    }

    public function testCacheStatistics(): void
    {
        $this->cache->get('nonexistent'); // Miss
        $this->cache->put('key', 'value', 60); // Write
        $this->cache->get('key'); // Hit
        
        $stats = $this->cache->getStats();
        
        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('writes', $stats);
        $this->assertEquals(1, $stats['hits']);
        $this->assertEquals(1, $stats['misses']);
        $this->assertEquals(1, $stats['writes']);
    }
}

