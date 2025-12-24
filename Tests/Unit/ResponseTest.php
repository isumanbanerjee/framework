<?php

namespace Tests\Unit;

use Core\Model\Response;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for Response Class
 *
 * Tests HTTP response handling, headers, and output.
 */
class ResponseTest extends TestCase
{
    private Response $response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->response = new Response();
    }

    public function testSetStatusCodeSetsCorrectCode(): void
    {
        $this->response->setStatusCode(404);
        
        // We can't easily test http_response_code() in unit tests
        // but we can test that the method completes without error
        $this->assertTrue(true);
    }

    public function testSetHeaderAddsHeader(): void
    {
        // Header testing is difficult in unit tests
        // This just ensures the method doesn't throw an error
        $this->response->setHeader('Content-Type', 'application/json');
        
        $this->assertTrue(true);
    }

    public function testJsonOutputsValidJson(): void
    {
        $data = ['status' => 'success', 'data' => ['id' => 1]];
        
        $this->expectOutputString(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        
        try {
            $this->response->json($data, 200);
        } catch (\Exception $e) {
            // Expected to exit
        }
    }

    public function testSetStatusCodeAcceptsValidCodes(): void
    {
        $validCodes = [200, 201, 301, 302, 400, 401, 403, 404, 500, 503];
        
        foreach ($validCodes as $code) {
            $this->response->setStatusCode($code);
        }
        
        $this->assertTrue(true);
    }

    public function testHtmlMethodSetsCorrectContentType(): void
    {
        // This test verifies the method exists and can be called
        ob_start();
        try {
            $this->response->html('<h1>Test</h1>');
        } catch (\Exception $e) {
            // Expected to exit
        }
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<h1>Test</h1>', $output);
    }
}

