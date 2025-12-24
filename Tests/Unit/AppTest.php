<?php

namespace Tests\Unit;

use Core\Model\App;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for App Configuration Class
 *
 * Tests the singleton pattern, configuration loading, and access methods.
 */
class AppTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset singleton instance between tests
        $reflection = new \ReflectionClass(App::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = App::getInstance();
        $instance2 = App::getInstance();
        
        $this->assertSame($instance1, $instance2);
    }

    public function testConfigReturnsValue(): void
    {
        $value = App::config('DB_TYPE', 'mysql');
        
        $this->assertIsString($value);
    }

    public function testConfigReturnsDefaultWhenKeyNotFound(): void
    {
        $value = App::config('NON_EXISTENT_KEY', 'default_value');
        
        $this->assertEquals('default_value', $value);
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        // Assuming DB_TYPE exists in config
        $result = App::has('DB_TYPE');
        
        $this->assertTrue($result);
    }

    public function testHasReturnsFalseForNonExistentKey(): void
    {
        $result = App::has('NON_EXISTENT_KEY_12345');
        
        $this->assertFalse($result);
    }

    public function testAllReturnsArray(): void
    {
        $config = App::all();
        
        $this->assertIsArray($config);
        $this->assertNotEmpty($config);
    }

    public function testCannotCloneSingleton(): void
    {
        $instance = App::getInstance();
        
        $reflection = new \ReflectionClass(App::class);
        $cloneMethod = $reflection->getMethod('__clone');
        
        $this->assertTrue($cloneMethod->isPrivate());
    }

    public function testConfigurationKeysExist(): void
    {
        $config = App::all();
        
        // Check for session configuration
        $this->assertArrayHasKey('SESSION_FLASH_KEY', $config);
        $this->assertArrayHasKey('SESSION_CSRF_TOKEN_KEY', $config);
        
        // Check for auth configuration
        $this->assertArrayHasKey('AUTH_TABLE', $config);
        $this->assertArrayHasKey('AUTH_PRIMARY_KEY', $config);
    }
}

