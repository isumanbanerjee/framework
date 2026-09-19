<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Template;
use PHPUnit\Framework\TestCase;

final class ViewScaffoldingTest extends TestCase
{
    private string $cacheDir;
    private Template $template;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/view_scaffolding_test_' . uniqid();
        $this->template = new Template(
            dirname(__DIR__, 2) . '/resources/views',
            $this->cacheDir,
            false
        );
    }

    protected function tearDown(): void
    {
        foreach (glob($this->cacheDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->cacheDir);
    }

    public function testLoginViewRendersWithinGuestLayout(): void
    {
        $html = $this->template->render('auth.login');

        $this->assertStringContainsString('Sign in', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('auth-card', $html);
    }

    public function testLoginViewRendersErrorWhenProvided(): void
    {
        $html = $this->template->render('auth.login', ['error' => 'Invalid credentials']);

        $this->assertStringContainsString('Invalid credentials', $html);
    }

    public function testRegisterViewRendersWithinGuestLayout(): void
    {
        $html = $this->template->render('auth.register');

        $this->assertStringContainsString('Create an account', $html);
        $this->assertStringContainsString('name="password_confirmation"', $html);
    }

    public function testAppLayoutIncludesHeaderAndFooterComponents(): void
    {
        // @include() renders components as independent views, so data must be
        // shared rather than passed through render()'s $data argument.
        $this->template->share('appName', 'TestApp');

        $html = $this->template->render('layouts.app');

        $this->assertStringContainsString('TestApp', $html);
        $this->assertStringContainsString('<header', $html);
        $this->assertStringContainsString('<footer', $html);
    }
}
