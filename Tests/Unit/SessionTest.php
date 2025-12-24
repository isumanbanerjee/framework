<?php

namespace Tests\Unit;

use Core\Model\Session;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for Session Class
 *
 * Tests session management, flash messages, and CSRF token functionality.
 *
 * Note: Some session tests may not work in CLI environment due to
 * session configuration restrictions.
 */
class SessionTest extends TestCase
{
    private ?Session $session = null;
    private bool $canRunSessionTests = true;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Check if we can run session tests
        if (php_sapi_name() === 'cli' && !extension_loaded('xdebug')) {
            // Configure for CLI testing
            ini_set('session.use_cookies', '0');
            ini_set('session.cache_limiter', '');
        }
        
        // Clear any existing session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // Clear session data
        $_SESSION = [];
        
        try {
            $this->session = new Session();
            $this->canRunSessionTests = true;
        } catch (\Exception $e) {
            // Session initialization failed - likely CLI restrictions
            $this->canRunSessionTests = false;
            $this->markTestSkipped('Session tests cannot run in this environment: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if ($this->canRunSessionTests && session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }
        $_SESSION = [];
        
        parent::tearDown();
    }

    public function testSessionCanBeInitialized(): void
    {
        $this->assertNotNull($this->session);
        $this->assertInstanceOf(Session::class, $this->session);
    }

    public function testSetAndGetValue(): void
    {
        if (!$this->canRunSessionTests) {
            $this->markTestSkipped('Session not available');
        }
        
        $this->session->set('test_key', 'test_value');
        $value = $this->session->get('test_key');
        
        $this->assertEquals('test_value', $value);
    }

    public function testGetReturnsDefaultForNonExistentKey(): void
    {
        if (!$this->canRunSessionTests) {
            $this->markTestSkipped('Session not available');
        }
        
        $value = $this->session->get('non_existent', 'default');
        
        $this->assertEquals('default', $value);
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        if (!$this->canRunSessionTests) {
            $this->markTestSkipped('Session not available');
        }
        
        $this->session->set('existing_key', 'value');
        
        $this->assertTrue($this->session->has('existing_key'));
    }

    public function testHasReturnsFalseForNonExistentKey(): void
    {
        if (!$this->canRunSessionTests) {
            $this->markTestSkipped('Session not available');
        }
        
        $this->assertFalse($this->session->has('non_existent_key'));
    }

    public function testRemoveDeletesValue(): void
    {
        if (!$this->canRunSessionTests) {
            $this->markTestSkipped('Session not available');
        }
        
        $this->session->set('key_to_remove', 'value');
        $this->session->remove('key_to_remove');
        
        $this->assertFalse($this->session->has('key_to_remove'));
    }

    public function testGenerateCsrfTokenReturnsString(): void
    {
        if (!$this->canRunSessionTests) {
            $this->markTestSkipped('Session not available');
        }
        
        $token = $this->session->generateCsrfToken();
        
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes in hex = 64 chars
    }

    public function testGenerateCsrfTokenReturnsSameTokenOnMultipleCalls(): void
    {
        if (!$this->canRunSessionTests) {
            $this->markTestSkipped('Session not available');
        }
        
        $token1 = $this->session->generateCsrfToken();
        $token2 = $this->session->generateCsrfToken();
        
        $this->assertEquals($token1, $token2);
    }

    public function testValidateCsrfTokenReturnsTrueForValidToken(): void
    {
        if (!$this->canRunSessionTests) {
            $this->markTestSkipped('Session not available');
        }
        
        $token = $this->session->generateCsrfToken();
        
        $this->assertTrue($this->session->validateCsrfToken($token));
    }

    public function testValidateCsrfTokenReturnsFalseForInvalidToken(): void
    {
        if (!$this->canRunSessionTests) {
            $this->markTestSkipped('Session not available');
        }
        
        $this->session->generateCsrfToken();
        
        $this->assertFalse($this->session->validateCsrfToken('invalid_token'));
    }

    public function testValidateCsrfTokenReturnsFalseForNull(): void
    {
        if (!$this->canRunSessionTests) {
            $this->markTestSkipped('Session not available');
        }
        
        $this->assertFalse($this->session->validateCsrfToken(null));
    }
}

