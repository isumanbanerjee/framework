<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Logger;
use PHPUnit\Framework\TestCase;

final class LoggerTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = sys_get_temp_dir() . '/logger_test_' . uniqid() . '.log';
    }

    protected function tearDown(): void
    {
        @unlink($this->logFile);
        foreach (glob($this->logFile . '.*') ?: [] as $rotated) {
            @unlink($rotated);
        }
    }

    public function testLogInfoWritesEntry(): void
    {
        (new Logger($this->logFile))->logInfo('hello', ['a' => 1]);

        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('[INFO] hello', $contents);
        $this->assertStringContainsString('{"a":1}', $contents);
    }

    public function testLogErrorWritesEntry(): void
    {
        (new Logger($this->logFile))->logError('boom');

        $this->assertStringContainsString('[ERROR] boom', file_get_contents($this->logFile));
    }

    public function testDefaultMinLevelIsDebugAndLogsEverything(): void
    {
        $logger = new Logger($this->logFile);
        $logger->logDebug('d');
        $logger->logInfo('i');
        $logger->logWarning('w');
        $logger->logError('e');
        $logger->logCritical('c');

        $contents = file_get_contents($this->logFile);
        foreach (['[DEBUG] d', '[INFO] i', '[WARNING] w', '[ERROR] e', '[CRITICAL] c'] as $needle) {
            $this->assertStringContainsString($needle, $contents);
        }
    }

    public function testSetMinLevelFiltersLowerSeverityEntries(): void
    {
        $logger = (new Logger($this->logFile))->setMinLevel('WARNING');

        $logger->logDebug('should be dropped');
        $logger->logInfo('should also be dropped');
        $logger->logWarning('kept');
        $logger->logError('kept too');

        $contents = file_exists($this->logFile) ? file_get_contents($this->logFile) : '';

        $this->assertStringNotContainsString('should be dropped', $contents);
        $this->assertStringNotContainsString('should also be dropped', $contents);
        $this->assertStringContainsString('[WARNING] kept', $contents);
        $this->assertStringContainsString('[ERROR] kept too', $contents);
    }

    public function testSetMinLevelIsCaseInsensitive(): void
    {
        $logger = (new Logger($this->logFile))->setMinLevel('error');

        $logger->logWarning('dropped');
        $logger->logError('kept');

        $contents = file_get_contents($this->logFile);
        $this->assertStringNotContainsString('dropped', $contents);
        $this->assertStringContainsString('[ERROR] kept', $contents);
    }

    public function testSetMinLevelIgnoresUnrecognizedLevel(): void
    {
        $logger = (new Logger($this->logFile))->setMinLevel('NOT_A_LEVEL');

        $logger->logDebug('still logged');

        $this->assertStringContainsString('[DEBUG] still logged', file_get_contents($this->logFile));
    }

    public function testFilteredEntryDoesNotCreateLogFile(): void
    {
        $logger = (new Logger($this->logFile))->setMinLevel('ERROR');

        $logger->logInfo('nope');

        $this->assertFalse(file_exists($this->logFile));
    }
}
