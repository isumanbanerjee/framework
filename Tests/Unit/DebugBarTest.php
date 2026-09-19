<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\App;
use Core\Model\Cache;
use Core\Model\DebugBar;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DebugBarTest extends TestCase
{
    private mixed $originalDebugValue;

    protected function setUp(): void
    {
        $this->originalDebugValue = $this->getConfigValue('APP_DEBUG');
        Cache::resetGlobalStats();
    }

    protected function tearDown(): void
    {
        $this->setConfigValue('APP_DEBUG', $this->originalDebugValue);
    }

    public function testIsEnabledReflectsAppDebugConfig(): void
    {
        $this->setConfigValue('APP_DEBUG', 'false');
        $this->assertFalse(DebugBar::isEnabled());

        $this->setConfigValue('APP_DEBUG', 'true');
        $this->assertTrue(DebugBar::isEnabled());
    }

    public function testInjectReturnsHtmlUnchangedWhenDisabled(): void
    {
        $this->setConfigValue('APP_DEBUG', 'false');

        $html = '<html><body>Hello</body></html>';
        $this->assertSame($html, DebugBar::inject($html));
    }

    public function testInjectInsertsBeforeClosingBodyTag(): void
    {
        $this->setConfigValue('APP_DEBUG', 'true');

        $html = '<html><body>Hello</body></html>';
        $result = DebugBar::inject($html, 200);

        $this->assertStringContainsString('omniophp-debugbar', $result);
        $this->assertLessThan(
            strpos($result, '</body>'),
            strpos($result, 'omniophp-debugbar')
        );
    }

    public function testInjectAppendsToolbarWhenNoBodyTagPresent(): void
    {
        $this->setConfigValue('APP_DEBUG', 'true');

        $html = 'plain text response';
        $result = DebugBar::inject($html, 404);

        $this->assertStringStartsWith('plain text response', $result);
        $this->assertStringContainsString('omniophp-debugbar', $result);
    }

    public function testRenderIncludesStatusCodeMemoryAndCacheStats(): void
    {
        Cache::resetGlobalStats();

        $bar = DebugBar::render(418);

        $this->assertStringContainsString('418', $bar);
        $this->assertStringContainsString('cache: 0 hit / 0 miss / 0 write', $bar);
        $this->assertStringContainsString('session: inactive', $bar);
    }

    private function getConfigValue(string $key): mixed
    {
        [$instance, $property] = $this->accessAppConfig();

        return $property->getValue($instance)[$key] ?? null;
    }

    private function setConfigValue(string $key, mixed $value): void
    {
        [$instance, $property] = $this->accessAppConfig();

        $config = $property->getValue($instance);
        $config[$key] = $value;
        $property->setValue($instance, $config);
    }

    /**
     * @return array{0:App,1:\ReflectionProperty}
     */
    private function accessAppConfig(): array
    {
        $instance = App::getInstance();
        $reflection = new ReflectionClass($instance);
        $property = $reflection->getProperty('config');
        $property->setAccessible(true);

        return [$instance, $property];
    }
}
