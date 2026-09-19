<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class LangFilesTest extends TestCase
{
    private const LANG_DIR = __DIR__ . '/../../resources/lang';

    private function locales(): array
    {
        $files = glob(self::LANG_DIR . '/*.php');
        $locales = [];

        foreach ($files as $file) {
            $locales[basename($file, '.php')] = require $file;
        }

        return $locales;
    }

    private function keySkeleton(array $array): array
    {
        $skeleton = [];

        foreach ($array as $key => $value) {
            $skeleton[$key] = is_array($value) ? $this->keySkeleton($value) : true;
        }

        ksort($skeleton);

        return $skeleton;
    }

    public function testAtLeastFourLocalesArePresent(): void
    {
        $locales = $this->locales();

        $this->assertContains('en', array_keys($locales));
        $this->assertGreaterThanOrEqual(4, count($locales));
    }

    public function testEveryLocaleFileReturnsAnArray(): void
    {
        foreach ($this->locales() as $locale => $translations) {
            $this->assertIsArray($translations, "Locale [{$locale}] must return an array.");
            $this->assertNotEmpty($translations, "Locale [{$locale}] must not be empty.");
        }
    }

    public function testEveryLocaleHasIdenticalKeyStructureToEnglish(): void
    {
        $locales = $this->locales();
        $englishSkeleton = $this->keySkeleton($locales['en']);

        foreach ($locales as $locale => $translations) {
            $this->assertSame(
                $englishSkeleton,
                $this->keySkeleton($translations),
                "Locale [{$locale}] key structure does not match [en]."
            );
        }
    }

    public function testNoTranslationValueIsEmpty(): void
    {
        foreach ($this->locales() as $locale => $translations) {
            $this->assertNoEmptyLeaf($translations, $locale);
        }
    }

    private function assertNoEmptyLeaf(array $array, string $locale, string $path = ''): void
    {
        foreach ($array as $key => $value) {
            $currentPath = $path === '' ? (string) $key : "{$path}.{$key}";

            if (is_array($value)) {
                $this->assertNoEmptyLeaf($value, $locale, $currentPath);
                continue;
            }

            $this->assertNotSame('', trim((string) $value), "Locale [{$locale}] has an empty value at [{$currentPath}].");
        }
    }
}
