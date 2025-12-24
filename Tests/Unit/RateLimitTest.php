<?php

namespace Tests\Unit;

use Core\Model\RateLimit;
use Core\Model\Lang;
use PHPUnit\Framework\TestCase;

/**
 * Rate Limit Tests
 */
class RateLimitTest extends TestCase
{
    private RateLimit $limiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->limiter = new RateLimit(5, 1); // 5 attempts per minute
    }

    public function testRateLimitCanBeCreated(): void
    {
        $this->assertInstanceOf(RateLimit::class, $this->limiter);
    }

    public function testRateLimitHit(): void
    {
        $key = 'test_key';
        
        $attempts = $this->limiter->hit($key);
        
        $this->assertEquals(1, $attempts);
    }

    public function testRateLimitAttempts(): void
    {
        $key = 'test_key';
        
        $this->limiter->hit($key);
        $this->limiter->hit($key);
        $this->limiter->hit($key);
        
        $this->assertEquals(3, $this->limiter->attempts($key));
    }

    public function testRateLimitTooManyAttempts(): void
    {
        $key = 'test_key';
        
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->hit($key);
        }
        
        $this->assertTrue($this->limiter->tooManyAttempts($key));
    }

    public function testRateLimitRetriesLeft(): void
    {
        $key = 'test_key';
        
        $this->limiter->hit($key);
        $this->limiter->hit($key);
        
        $this->assertEquals(3, $this->limiter->retriesLeft($key));
    }

    public function testRateLimitResetAttempts(): void
    {
        $key = 'test_key';
        
        $this->limiter->hit($key);
        $this->limiter->hit($key);
        
        $this->assertEquals(2, $this->limiter->attempts($key));
        
        $this->limiter->resetAttempts($key);
        
        $this->assertEquals(0, $this->limiter->attempts($key));
    }

    public function testRateLimitClear(): void
    {
        $key = 'test_key';
        
        $this->limiter->hit($key);
        $this->limiter->clear($key);
        
        $this->assertEquals(0, $this->limiter->attempts($key));
    }

    public function testRateLimitFor(): void
    {
        $apiLimit = RateLimit::for('api');
        $loginLimit = RateLimit::for('login');
        
        $this->assertInstanceOf(RateLimit::class, $apiLimit);
        $this->assertInstanceOf(RateLimit::class, $loginLimit);
    }
}

/**
 * Localization Tests
 */
class LocalizationTest extends TestCase
{
    private string $langPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create temporary language files
        $this->langPath = sys_get_temp_dir() . '/test_lang_' . uniqid();
        mkdir($this->langPath, 0755, true);
        
        // English translations
        file_put_contents($this->langPath . '/en.php', '<?php return [
            "welcome" => "Welcome",
            "auth" => [
                "failed" => "Authentication failed"
            ]
        ];');
        
        // Spanish translations
        file_put_contents($this->langPath . '/es.php', '<?php return [
            "welcome" => "Bienvenido",
            "auth" => [
                "failed" => "Autenticación fallida"
            ]
        ];');
        
        // Set path via reflection (since it's private)
        $reflection = new \ReflectionClass(Lang::class);
        $property = $reflection->getProperty('path');
        $property->setAccessible(true);
        $property->setValue(null, $this->langPath);
    }

    protected function tearDown(): void
    {
        // Clean up
        @unlink($this->langPath . '/en.php');
        @unlink($this->langPath . '/es.php');
        @rmdir($this->langPath);
        
        parent::tearDown();
    }

    public function testLangSetLocale(): void
    {
        Lang::setLocale('en');
        
        $this->assertEquals('en', Lang::getLocale());
    }

    public function testLangGetTranslation(): void
    {
        Lang::setLocale('en');
        
        $this->assertEquals('Welcome', Lang::get('welcome'));
    }

    public function testLangGetNestedTranslation(): void
    {
        Lang::setLocale('en');
        
        $this->assertEquals('Authentication failed', Lang::get('auth.failed'));
    }

    public function testLangGetWithReplacements(): void
    {
        file_put_contents($this->langPath . '/en.php', '<?php return [
            "greeting" => "Hello, :name!"
        ];');
        
        Lang::setLocale('en');
        
        $this->assertEquals('Hello, John!', Lang::get('greeting', ['name' => 'John']));
    }

    public function testLangChangeLocale(): void
    {
        Lang::setLocale('en');
        $this->assertEquals('Welcome', Lang::get('welcome'));
        
        Lang::setLocale('es');
        $this->assertEquals('Bienvenido', Lang::get('welcome'));
    }

    public function testLangHas(): void
    {
        Lang::setLocale('en');
        
        $this->assertTrue(Lang::has('welcome'));
        $this->assertFalse(Lang::has('nonexistent'));
    }

    public function testLangReturnsKeyWhenNotFound(): void
    {
        Lang::setLocale('en');
        
        $this->assertEquals('nonexistent.key', Lang::get('nonexistent.key'));
    }

    public function testTransHelperFunction(): void
    {
        Lang::setLocale('en');
        
        $this->assertEquals('Welcome', trans('welcome'));
    }

    public function testDoubleUnderscoreHelperFunction(): void
    {
        Lang::setLocale('en');
        
        $this->assertEquals('Welcome', __('welcome'));
    }
}

