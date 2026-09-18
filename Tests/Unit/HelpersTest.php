<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function testEnvReadsFromEnvSuperglobal(): void
    {
        $_ENV['HELPERS_TEST_KEY'] = 'from_env';
        $this->assertSame('from_env', env('HELPERS_TEST_KEY'));
        unset($_ENV['HELPERS_TEST_KEY']);
    }

    public function testEnvReadsFromServerSuperglobal(): void
    {
        $_SERVER['HELPERS_TEST_SERVER'] = 'from_server';
        $this->assertSame('from_server', env('HELPERS_TEST_SERVER'));
        unset($_SERVER['HELPERS_TEST_SERVER']);
    }

    public function testEnvReturnsDefaultWhenMissing(): void
    {
        $this->assertSame('fallback', env('DEFINITELY_MISSING_KEY_XYZ', 'fallback'));
        $this->assertNull(env('DEFINITELY_MISSING_KEY_XYZ'));
    }

    public function testEnvPrefersEnvOverServer(): void
    {
        $_ENV['HELPERS_PRIORITY'] = 'env_wins';
        $_SERVER['HELPERS_PRIORITY'] = 'server_loses';
        $this->assertSame('env_wins', env('HELPERS_PRIORITY'));
        unset($_ENV['HELPERS_PRIORITY'], $_SERVER['HELPERS_PRIORITY']);
    }

    public function testEscapesHtmlSpecialCharacters(): void
    {
        $this->assertSame(
            '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;',
            e('<script>alert("xss")</script>')
        );
    }

    public function testEscapesSingleQuotes(): void
    {
        $this->assertSame('O&#039;Brien', e("O'Brien"));
    }

    public function testEscapeHandlesNull(): void
    {
        $this->assertSame('', e(null));
    }
}
