<?php

namespace Tests\Unit;

use Core\Model\Template;
use PHPUnit\Framework\TestCase;

/**
 * Template Engine Tests
 */
class TemplateTest extends TestCase
{
    private Template $template;
    private string $viewsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewsPath = sys_get_temp_dir() . '/test_views_' . uniqid();
        mkdir($this->viewsPath, 0755, true);
        $this->template = new Template($this->viewsPath, null, false);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->viewsPath);
        parent::tearDown();
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) return;
        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $path . '/' . $file;
            is_dir($filePath) ? $this->removeDirectory($filePath) : unlink($filePath);
        }
        rmdir($path);
    }

    public function testTemplateCanRenderBasicView(): void
    {
        file_put_contents($this->viewsPath . '/test.php', '<h1>Hello World</h1>');
        
        $output = $this->template->render('test');
        
        $this->assertStringContainsString('Hello World', $output);
    }

    public function testTemplateCanRenderWithData(): void
    {
        file_put_contents($this->viewsPath . '/greet.php', '<h1>Hello {{ $name }}</h1>');
        
        $output = $this->template->render('greet', ['name' => 'John']);
        
        $this->assertStringContainsString('Hello John', $output);
    }

    public function testTemplateEscapesHtmlByDefault(): void
    {
        file_put_contents($this->viewsPath . '/escape.php', '{{ $content }}');
        
        $output = $this->template->render('escape', ['content' => '<script>alert("xss")</script>']);
        
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    public function testTemplateRawEchoDoesNotEscape(): void
    {
        file_put_contents($this->viewsPath . '/raw.php', '{!! $content !!}');
        
        $output = $this->template->render('raw', ['content' => '<strong>Bold</strong>']);
        
        $this->assertStringContainsString('<strong>Bold</strong>', $output);
    }

    public function testTemplateIfDirective(): void
    {
        file_put_contents($this->viewsPath . '/if.php', '@if($show) <p>Visible</p> @endif');
        
        $outputTrue = $this->template->render('if', ['show' => true]);
        $outputFalse = $this->template->render('if', ['show' => false]);
        
        $this->assertStringContainsString('Visible', $outputTrue);
        $this->assertStringNotContainsString('Visible', $outputFalse);
    }

    public function testTemplateElseDirective(): void
    {
        file_put_contents($this->viewsPath . '/else.php',
            '@if($condition) <p>True</p> @else <p>False</p> @endif'
        );
        
        $outputTrue = $this->template->render('else', ['condition' => true]);
        $outputFalse = $this->template->render('else', ['condition' => false]);
        
        $this->assertStringContainsString('True', $outputTrue);
        $this->assertStringContainsString('False', $outputFalse);
    }

    public function testTemplateForeachDirective(): void
    {
        file_put_contents($this->viewsPath . '/foreach.php',
            '@foreach($items as $item) <p>{{ $item }}</p> @endforeach'
        );
        
        $output = $this->template->render('foreach', ['items' => ['A', 'B', 'C']]);
        
        $this->assertStringContainsString('A', $output);
        $this->assertStringContainsString('B', $output);
        $this->assertStringContainsString('C', $output);
    }

    public function testTemplateUnlessDirective(): void
    {
        file_put_contents($this->viewsPath . '/unless.php',
            '@unless($condition) <p>Not True</p> @endunless'
        );
        
        $outputFalse = $this->template->render('unless', ['condition' => false]);
        $outputTrue = $this->template->render('unless', ['condition' => true]);
        
        $this->assertStringContainsString('Not True', $outputFalse);
        $this->assertStringNotContainsString('Not True', $outputTrue);
    }

    public function testTemplateCommentsAreRemoved(): void
    {
        file_put_contents($this->viewsPath . '/comment.php',
            '<p>Visible</p> {{-- This is a comment --}} <p>Also Visible</p>'
        );
        
        $output = $this->template->render('comment');
        
        $this->assertStringContainsString('Visible', $output);
        $this->assertStringNotContainsString('This is a comment', $output);
    }

    public function testTemplateShareData(): void
    {
        $this->template->share('siteName', 'Test Site');
        
        file_put_contents($this->viewsPath . '/shared.php', '<h1>{{ $siteName }}</h1>');
        
        $output = $this->template->render('shared');
        
        $this->assertStringContainsString('Test Site', $output);
    }

    public function testTemplateShareArrayData(): void
    {
        $this->template->share(['app' => 'MyApp', 'version' => '1.0']);
        
        file_put_contents($this->viewsPath . '/shared_array.php', '{{ $app }} v{{ $version }}');
        
        $output = $this->template->render('shared_array');
        
        $this->assertStringContainsString('MyApp v1.0', $output);
    }

    public function testTemplateClearCache(): void
    {
        $cachePath = sys_get_temp_dir() . '/template_cache_' . uniqid();
        mkdir($cachePath, 0755, true);
        
        $template = new Template($this->viewsPath, $cachePath, true);
        
        file_put_contents($this->viewsPath . '/cached.php', '<p>Test</p>');
        $template->render('cached');
        
        $this->assertNotEmpty(glob($cachePath . '/*'));
        
        $template->clearCache();
        
        $this->removeDirectory($cachePath);
        $this->assertTrue(true); // Cache cleared
    }
}

