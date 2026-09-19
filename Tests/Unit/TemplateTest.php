<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Template;
use PHPUnit\Framework\TestCase;

final class TemplateTest extends TestCase
{
    private string $viewsDir;
    private string $cacheDir;
    private Template $template;

    protected function setUp(): void
    {
        $this->viewsDir = sys_get_temp_dir() . '/template_test_views_' . uniqid();
        $this->cacheDir = sys_get_temp_dir() . '/template_test_cache_' . uniqid();
        mkdir($this->viewsDir);
        mkdir($this->viewsDir . '/layouts');

        file_put_contents(
            $this->viewsDir . '/layouts/base.php',
            "<title>@yield('title')</title><body>@yield('content')</body>"
        );

        file_put_contents(
            $this->viewsDir . '/child.php',
            "@extends('layouts.base')\n@section('title')\nHello\n@endsection\n@section('content')\n<p>{{ \$name }}</p>\n@endsection"
        );

        $this->template = new Template($this->viewsDir, $this->cacheDir, false);
    }

    protected function tearDown(): void
    {
        @unlink($this->viewsDir . '/layouts/base.php');
        @unlink($this->viewsDir . '/child.php');
        @rmdir($this->viewsDir . '/layouts');
        @rmdir($this->viewsDir);
        foreach (glob($this->cacheDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->cacheDir);
    }

    public function testExtendsPropagatesSectionsIntoTheParentLayout(): void
    {
        $html = $this->template->render('child', ['name' => 'World']);

        $this->assertMatchesRegularExpression('/<title>\s*Hello\s*<\/title>/', $html);
        $this->assertStringContainsString('<p>World</p>', $html);
    }

    public function testExtendsDoesNotLeakTheChildTemplatesOwnOutputBuffer(): void
    {
        $obLevelBefore = ob_get_level();

        $this->template->render('child', ['name' => 'World']);

        $this->assertSame($obLevelBefore, ob_get_level());
    }
}
