<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testSecurityHeadersContainsHardenedDefaults(): void
    {
        $headers = (new Response())->securityHeaders();

        $this->assertSame('SAMEORIGIN', $headers['X-Frame-Options']);
        $this->assertSame('nosniff', $headers['X-Content-Type-Options']);
        $this->assertSame('strict-origin-when-cross-origin', $headers['Referrer-Policy']);
        $this->assertArrayHasKey('Content-Security-Policy', $headers);
        $this->assertArrayHasKey('Permissions-Policy', $headers);
    }

    public function testSecurityHeadersAreAllStrings(): void
    {
        foreach ((new Response())->securityHeaders() as $name => $value) {
            $this->assertIsString($name);
            $this->assertIsString($value);
            $this->assertNotSame('', $value);
        }
    }

    public function testSecurityHeadersIsPureAndRepeatable(): void
    {
        $response = new Response();
        $this->assertSame($response->securityHeaders(), $response->securityHeaders());
    }
}
