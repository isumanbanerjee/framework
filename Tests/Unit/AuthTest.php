<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Auth;
use Core\Model\Database\Database;
use Core\Model\RateLimit;
use Core\Model\Session;
use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    private string $rateLimitKey;

    protected function setUp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.42';
        $this->rateLimitKey = uniqid('auth-test-', true);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REMOTE_ADDR']);
    }

    private function makeAuth(Database $db, Session $session): Auth
    {
        $auth = new Auth($db, $session);
        $auth->setRateLimiter(new RateLimit(3, 1));

        return $auth;
    }

    public function testFailedLoginWithUnknownIdentityDecrementsAttemptsRemaining(): void
    {
        $db = $this->createMock(Database::class);
        $db->method('fetchOneNamed')->willReturn([]);
        $session = $this->createMock(Session::class);

        $auth = $this->makeAuth($db, $session);
        $identity = $this->rateLimitKey . '@example.com';

        $this->assertSame(3, $auth->loginAttemptsRemaining($identity));

        $this->assertFalse($auth->login($identity, 'wrong-password'));
        $this->assertSame(2, $auth->loginAttemptsRemaining($identity));

        $this->assertFalse($auth->login($identity, 'wrong-password'));
        $this->assertSame(1, $auth->loginAttemptsRemaining($identity));
    }

    public function testFailedLoginWithWrongPasswordDecrementsAttemptsRemaining(): void
    {
        $hash = password_hash('correct-password', PASSWORD_DEFAULT);
        $db = $this->createMock(Database::class);
        $db->method('fetchOneNamed')->willReturn([
            'id' => 1,
            'email' => 'user@example.com',
            'password' => $hash,
        ]);
        $session = $this->createMock(Session::class);

        $auth = $this->makeAuth($db, $session);
        $identity = $this->rateLimitKey . '-wrongpass@example.com';

        $this->assertFalse($auth->login($identity, 'incorrect-password'));
        $this->assertSame(2, $auth->loginAttemptsRemaining($identity));
    }

    public function testSuccessfulLoginResetsAttemptsRemaining(): void
    {
        $hash = password_hash('correct-password', PASSWORD_DEFAULT);
        $db = $this->createMock(Database::class);
        $db->method('fetchOneNamed')->willReturn([
            'id' => 42,
            'email' => 'user@example.com',
            'password' => $hash,
        ]);
        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('regenerate');
        $session->expects($this->exactly(3))->method('set');

        $auth = $this->makeAuth($db, $session);
        $identity = $this->rateLimitKey . '-success@example.com';

        $auth->login($identity, 'incorrect-password');
        $this->assertSame(2, $auth->loginAttemptsRemaining($identity));

        $this->assertTrue($auth->login($identity, 'correct-password'));
        $this->assertSame(3, $auth->loginAttemptsRemaining($identity));
    }

    public function testThrottleKeyIsScopedPerIdentitySoUnrelatedIdentitiesAreUnaffected(): void
    {
        $db = $this->createMock(Database::class);
        $db->method('fetchOneNamed')->willReturn([]);
        $session = $this->createMock(Session::class);

        $auth = $this->makeAuth($db, $session);
        $identityA = $this->rateLimitKey . '-a@example.com';
        $identityB = $this->rateLimitKey . '-b@example.com';

        $auth->login($identityA, 'wrong-password');
        $auth->login($identityA, 'wrong-password');

        $this->assertSame(1, $auth->loginAttemptsRemaining($identityA));
        $this->assertSame(3, $auth->loginAttemptsRemaining($identityB));
    }
}
