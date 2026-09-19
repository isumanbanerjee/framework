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

    public function testComputeEtagIsAQuotedMd5Hash(): void
    {
        $etag = (new Response())->computeEtag('hello world');

        $this->assertSame('"' . md5('hello world') . '"', $etag);
    }

    public function testComputeEtagIsStableForTheSameBody(): void
    {
        $response = new Response();
        $this->assertSame($response->computeEtag('same'), $response->computeEtag('same'));
    }

    public function testComputeEtagDiffersForDifferentBodies(): void
    {
        $response = new Response();
        $this->assertNotSame($response->computeEtag('a'), $response->computeEtag('b'));
    }

    public function testIfNoneMatchSatisfiedByReturnsFalseWhenHeaderAbsent(): void
    {
        unset($_SERVER['HTTP_IF_NONE_MATCH']);

        $this->assertFalse((new Response())->ifNoneMatchSatisfiedBy('"abc"'));
    }

    public function testIfNoneMatchSatisfiedByReturnsTrueOnExactMatch(): void
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = '"abc"';

        $this->assertTrue((new Response())->ifNoneMatchSatisfiedBy('"abc"'));

        unset($_SERVER['HTTP_IF_NONE_MATCH']);
    }

    public function testIfNoneMatchSatisfiedByReturnsFalseOnMismatch(): void
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = '"xyz"';

        $this->assertFalse((new Response())->ifNoneMatchSatisfiedBy('"abc"'));

        unset($_SERVER['HTTP_IF_NONE_MATCH']);
    }

    public function testIfNoneMatchSatisfiedByHandlesWildcard(): void
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = '*';

        $this->assertTrue((new Response())->ifNoneMatchSatisfiedBy('"anything"'));

        unset($_SERVER['HTTP_IF_NONE_MATCH']);
    }

    public function testIfNoneMatchSatisfiedByHandlesCommaSeparatedList(): void
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = '"one", "two", "three"';

        $this->assertTrue((new Response())->ifNoneMatchSatisfiedBy('"two"'));

        unset($_SERVER['HTTP_IF_NONE_MATCH']);
    }

    public function testIfNoneMatchSatisfiedByStripsWeakValidatorPrefix(): void
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"abc"';

        $this->assertTrue((new Response())->ifNoneMatchSatisfiedBy('"abc"'));

        unset($_SERVER['HTTP_IF_NONE_MATCH']);
    }

    public function testWithEtagReturnsSelfForChaining(): void
    {
        $response = new Response();

        $this->assertSame($response, $response->withEtag());
        $this->assertSame($response, $response->withEtag(false));
    }
}
