<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Lang;
use PHPUnit\Framework\TestCase;

final class LangTest extends TestCase
{
    protected function setUp(): void
    {
        Lang::setLocale('en');
    }

    public function testSetLocaleLoadsTranslations(): void
    {
        Lang::setLocale('es');
        $this->assertSame('es', Lang::getLocale());
        $this->assertSame('Bienvenido', Lang::get('welcome'));
    }

    public function testGetResolvesNestedKeys(): void
    {
        $this->assertSame('Login', Lang::get('auth.login'));
    }

    public function testGetReturnsKeyWhenMissing(): void
    {
        $this->assertSame('does.not.exist', Lang::get('does.not.exist'));
    }

    public function testGetReplacesPlaceholders(): void
    {
        $this->assertSame(
            'Too many login attempts. Please try again in 30 seconds.',
            Lang::get('auth.throttle', ['seconds' => '30'])
        );
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $this->assertTrue(Lang::has('welcome'));
        $this->assertTrue(Lang::has('auth.login'));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        $this->assertFalse(Lang::has('nonexistent.key'));
    }

    public function testHelperFunctionDelegatesToLang(): void
    {
        Lang::setLocale('fr');
        $this->assertSame('Bienvenue', __('welcome'));
        $this->assertSame('Bienvenue', trans('welcome'));
    }

    public function testWorksForEveryAvailableLocale(): void
    {
        foreach (['en', 'es', 'fr', 'de'] as $locale) {
            Lang::setLocale($locale);
            $this->assertNotSame('welcome', Lang::get('welcome'));
        }
    }
}
