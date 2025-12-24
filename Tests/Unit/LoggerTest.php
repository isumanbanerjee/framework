<?php

namespace Tests\Unit;

use Core\Model\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for Logger Class
 *
 * Tests logging functionality and log rotation.
 */
class LoggerTest extends TestCase
{
    private string $testLogFile;
    private Logger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a temporary log file for testing
        $this->testLogFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
        $this->logger = new Logger($this->testLogFile);
    }

    protected function tearDown(): void
    {
        // Clean up test log file
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }
        
        // Clean up rotated log files
        $pattern = $this->testLogFile . '_*';
        foreach (glob($pattern) as $file) {
            unlink($file);
        }
        
        parent::tearDown();
    }

    public function testLogInfoWritesToFile(): void
    {
        $this->logger->logInfo('Test info message');
        
        $this->assertFileExists($this->testLogFile);
        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Test info message', $content);
        $this->assertStringContainsString('[INFO]', $content);
    }

    public function testLogErrorWritesToFile(): void
    {
        $this->logger->logError('Test error message');
        
        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Test error message', $content);
        $this->assertStringContainsString('[ERROR]', $content);
    }


    public function testLogIncludesTimestamp(): void
    {
        $this->logger->logInfo('Test message');
        
        $content = file_get_contents($this->testLogFile);
        // Check for date format (YYYY-MM-DD)
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $content);
    }

    public function testMultipleLogsAreAppended(): void
    {
        $this->logger->logInfo('First message');
        $this->logger->logInfo('Second message');
        $this->logger->logInfo('Third message');
        
        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('First message', $content);
        $this->assertStringContainsString('Second message', $content);
        $this->assertStringContainsString('Third message', $content);
    }

    public function testLogFileCreatesDirectoryIfNotExists(): void
    {
        $nestedPath = sys_get_temp_dir() . '/test_logs_' . uniqid() . '/nested/test.log';
        $logger = new Logger($nestedPath);
        
        $logger->logInfo('Test');
        
        $this->assertFileExists($nestedPath);
        
        // Cleanup
        unlink($nestedPath);
        rmdir(dirname($nestedPath));
        rmdir(dirname(dirname($nestedPath)));
    }

    public function testLogRotationOccursWhenFileSizeExceeded(): void
    {
        // Create a logger with very small max file size (int, not string)
        $logger = new Logger($this->testLogFile, 100);
        
        // Write enough data to exceed the limit
        for ($i = 0; $i < 10; $i++) {
            $logger->logInfo('This is a test message that will fill up the log file ' . $i);
        }
        
        // Check if rotation occurred (backup file should exist)
        $pattern = $this->testLogFile . '_*';
        $rotatedFiles = glob($pattern);
        
        $this->assertNotEmpty($rotatedFiles);
    }
}

