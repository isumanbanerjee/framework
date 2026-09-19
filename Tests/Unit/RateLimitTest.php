<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\RateLimit;
use PHPUnit\Framework\TestCase;

final class RateLimitTest extends TestCase
{
    private string $key;

    protected function setUp(): void
    {
        $this->key = 'rate-limit-test:' . uniqid();
    }

    protected function tearDown(): void
    {
        (new RateLimit())->resetAttempts($this->key);
    }

    public function testAttemptsStartAtZero(): void
    {
        $limiter = new RateLimit(3, 1);
        $this->assertSame(0, $limiter->attempts($this->key));
        $this->assertFalse($limiter->tooManyAttempts($this->key));
    }

    public function testHitIncrementsAttempts(): void
    {
        $limiter = new RateLimit(3, 1);

        $this->assertSame(1, $limiter->hit($this->key));
        $this->assertSame(2, $limiter->hit($this->key));
        $this->assertSame(2, $limiter->attempts($this->key));
    }

    public function testTooManyAttemptsOnceLimitReached(): void
    {
        $limiter = new RateLimit(2, 1);

        $limiter->hit($this->key);
        $this->assertFalse($limiter->tooManyAttempts($this->key));

        $limiter->hit($this->key);
        $this->assertTrue($limiter->tooManyAttempts($this->key));
    }

    public function testRetriesLeftDecreasesWithEachHit(): void
    {
        $limiter = new RateLimit(3, 1);

        $this->assertSame(3, $limiter->retriesLeft($this->key));
        $limiter->hit($this->key);
        $this->assertSame(2, $limiter->retriesLeft($this->key));
    }

    public function testResetAttemptsClearsCounter(): void
    {
        $limiter = new RateLimit(3, 1);

        $limiter->hit($this->key);
        $limiter->hit($this->key);
        $limiter->resetAttempts($this->key);

        $this->assertSame(0, $limiter->attempts($this->key));
        $this->assertSame(3, $limiter->retriesLeft($this->key));
    }

    public function testForLoginUsesFiveAttemptPreset(): void
    {
        $limiter = RateLimit::for('login');

        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($limiter->tooManyAttempts($this->key));
            $limiter->hit($this->key);
        }

        $this->assertTrue($limiter->tooManyAttempts($this->key));
    }

    public function testForUnknownKeyFallsBackToDefaultPreset(): void
    {
        $limiter = RateLimit::for('unknown-preset');

        for ($i = 0; $i < 60; $i++) {
            $this->assertFalse($limiter->tooManyAttempts($this->key));
            $limiter->hit($this->key);
        }

        $this->assertTrue($limiter->tooManyAttempts($this->key));
    }
}
