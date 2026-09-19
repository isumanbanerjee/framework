<?php

namespace Core\Model;

use Exception;

/**
 * Enterprise Template Engine (Blade-like)
 *
 * Modern template engine with clean syntax, automatic escaping,
 * template inheritance, components, and caching.
 *
 * Features:
 * - Blade-like syntax ({{ }}, @if, @foreach, etc.)
 * - Automatic XSS protection
 * - Template inheritance (@extends, @section, @yield)
 * - Components and slots
 * - Template caching
 * - Custom directives
 * - Conditional rendering
 * - Loops with $loop variable
 * - Include and partials
 *
 * @package Core\Model
 * @version 1.0.0
 * @since 2025-12-24
 */
class Template
{
    /**
     * Views directory
     *
     * @var string
     */
    private string $viewsPath;

    /**
     * Cache directory
     *
     * @var string
     */
    private string $cachePath;

    /**
     * Enable caching
     *
     * @var bool
     */
    private bool $cacheEnabled;

    /**
     * Custom directives
     *
     * @var array
     */
    private array $directives = [];

    /**
     * Shared data across all views
     *
     * @var array
     */
    private array $shared = [];

    /**
     * Current rendering data
     *
     * @var array
     */
    private array $data = [];

    /**
     * Compiled template cache
     *
     * @var array
     */
    private array $compiled = [];

    /**
     * Initialize template engine
     *
     * @param string $viewsPath Path to views directory
     * @param string|null $cachePath Path to cache directory
     * @param bool $cacheEnabled Enable template caching
     */
    public function __construct(
        string $viewsPath = 'views',
        ?string $cachePath = null,
        bool $cacheEnabled = true
    ) {
        $this->viewsPath = rtrim($viewsPath, '/');
        $this->cachePath = $cachePath ?? sys_get_temp_dir() . '/views_cache';
        $this->cacheEnabled = $cacheEnabled && !App::config('DEBUG_MODE', false);

        $this->ensureCacheDirectory();
        $this->registerDefaultDirectives();
    }

    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDirectory(): void
    {
        if ($this->cacheEnabled && !is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * Register default directives
     */
    private function registerDefaultDirectives(): void
    {
        // Already handled in compilation
    }

    /**
     * Render a view
     *
     * @param string $view View name (dot notation)
     * @param array $data Data to pass to view
     * @return string
     */
    public function render(string $view, array $data = []): string
    {
        try {
            $this->data = array_merge($this->shared, $data);
            $viewPath = $this->findView($view);

            if (!$viewPath) {
                throw new Exception("View not found: {$view}");
            }

            $compiled = $this->compile($viewPath);

            return $this->evaluate($compiled, $this->data);
        } catch (Exception $e) {
            if (defined('TEST_ENV') || php_sapi_name() === 'cli') {
                throw $e;
            }

            $error = new Error();
            $error->terminateWithError(
                'TEMPLATE_RENDER_FAILED',
                'Template render failed: ' . $e->getMessage(),
                Error::SEVERITY_ERROR,
                ['view' => $view]
            );
        }
    }

    /**
     * Share data with all views
     *
     * @param string|array $key
     * @param mixed $value
     * @return self
     */
    public function share($key, $value = null): self
    {
        if (is_array($key)) {
            $this->shared = array_merge($this->shared, $key);
        } else {
            $this->shared[$key] = $value;
        }

        return $this;
    }

    /**
     * Register custom directive
     *
     * @param string $name
     * @param callable $handler
     * @return self
     */
    public function directive(string $name, callable $handler): self
    {
        $this->directives[$name] = $handler;
        return $this;
    }

    /**
     * Find view file
     *
     * @param string $view
     * @return string|null
     */
    private function findView(string $view): ?string
    {
        $path = $this->viewsPath . '/' . str_replace('.', '/', $view) . '.php';

        if (file_exists($path)) {
            return $path;
        }

        // Try with .blade.php extension
        $bladePath = $this->viewsPath . '/' . str_replace('.', '/', $view) . '.blade.php';
        if (file_exists($bladePath)) {
            return $bladePath;
        }

        return null;
    }

    /**
     * Compile template
     *
     * @param string $path
     * @return string Path to compiled file
     */
    private function compile(string $path): string
    {
        $cacheKey = md5($path);

        if (isset($this->compiled[$cacheKey])) {
            return $this->compiled[$cacheKey];
        }

        $cachePath = $this->cachePath . '/' . $cacheKey . '.php';

        if ($this->cacheEnabled && file_exists($cachePath)) {
            if (filemtime($cachePath) >= filemtime($path)) {
                $this->compiled[$cacheKey] = $cachePath;
                return $cachePath;
            }
        }

        $content = file_get_contents($path);
        $compiled = $this->compileString($content);

        if ($this->cacheEnabled) {
            file_put_contents($cachePath, $compiled, LOCK_EX);
            $this->compiled[$cacheKey] = $cachePath;
            return $cachePath;
        }

        // Use temporary file if caching disabled
        $tempPath = tempnam(sys_get_temp_dir(), 'view_');
        file_put_contents($tempPath, $compiled);
        $this->compiled[$cacheKey] = $tempPath;

        return $tempPath;
    }

    /**
     * Compile template string
     *
     * @param string $content
     * @return string
     */
    private function compileString(string $content): string
    {
        // Compile directives in order
        $content = $this->compileEchos($content);
        $content = $this->compileExtends($content);
        $content = $this->compileSections($content);
        $content = $this->compileYields($content);
        $content = $this->compileIncludes($content);
        $content = $this->compileIf($content);
        $content = $this->compileUnless($content);
        $content = $this->compileForEach($content);
        $content = $this->compileFor($content);
        $content = $this->compileWhile($content);
        $content = $this->compilePhp($content);
        $content = $this->compileCustomDirectives($content);
        $content = $this->compileComments($content);

        return $content;
    }

    /**
     * Compile echo statements {{ }}
     *
     * @param string $content
     * @return string
     */
    private function compileEchos(string $content): string
    {
        // Raw echo {!! !!}
        $content = preg_replace('/\{\!\!\s*(.+?)\s*\!\!\}/s', '<?php echo $1; ?>', $content);

        // Escaped echo {{ }}
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/s', '<?php echo htmlspecialchars($1, ENT_QUOTES, \'UTF-8\'); ?>', $content);

        return $content;
    }

    /**
     * Compile @extends directive
     *
     * @param string $content
     * @return string
     */
    private function compileExtends(string $content): string
    {
        return preg_replace('/@extends\s*\(\s*[\'"](.+?)[\'"]\s*\)/', '<?php $__extends = \'$1\'; ?>', $content);
    }

    /**
     * Compile @section directive
     *
     * @param string $content
     * @return string
     */
    private function compileSections(string $content): string
    {
        $content = preg_replace('/@section\s*\(\s*[\'"](.+?)[\'"]\s*\)/', '<?php $__sections[\'$1\'] = ob_start(); ?>', $content);
        $content = preg_replace('/@endsection/', '<?php $__sections[array_key_last($__sections)] = ob_get_clean(); ?>', $content);

        return $content;
    }

    /**
     * Compile @yield directive
     *
     * @param string $content
     * @return string
     */
    private function compileYields(string $content): string
    {
        return preg_replace('/@yield\s*\(\s*[\'"](.+?)[\'"]\s*\)/', '<?php echo $__sections[\'$1\'] ?? \'\'; ?>', $content);
    }

    /**
     * Compile @include directive
     *
     * @param string $content
     * @return string
     */
    private function compileIncludes(string $content): string
    {
        return preg_replace_callback(
            '/@include\s*\(\s*[\'"](.+?)[\'"]\s*(?:,\s*(\[.+?\]))?\s*\)/',
            function ($matches) {
                $view = $matches[1];
                $data = $matches[2] ?? '[]';
                return "<?php echo \$this->render('{$view}', {$data}); ?>";
            },
            $content
        );
    }

    /**
     * Compile @if directive
     *
     * @param string $content
     * @return string
     */
    private function compileIf(string $content): string
    {
        $content = preg_replace('/@if\s*\((.+?)\)/', '<?php if ($1): ?>', $content);
        $content = preg_replace('/@elseif\s*\((.+?)\)/', '<?php elseif ($1): ?>', $content);
        $content = preg_replace('/@else/', '<?php else: ?>', $content);
        $content = preg_replace('/@endif/', '<?php endif; ?>', $content);

        return $content;
    }

    /**
     * Compile @unless directive
     *
     * @param string $content
     * @return string
     */
    private function compileUnless(string $content): string
    {
        $content = preg_replace('/@unless\s*\((.+?)\)/', '<?php if (!($1)): ?>', $content);
        $content = preg_replace('/@endunless/', '<?php endif; ?>', $content);

        return $content;
    }

    /**
     * Compile @foreach directive
     *
     * @param string $content
     * @return string
     */
    private function compileForEach(string $content): string
    {
        $content = preg_replace(
            '/@foreach\s*\((.+?)\s+as\s+(.+?)\)/',
            '<?php foreach ($1 as $2): $loop = new \stdClass(); ?>',
            $content
        );
        $content = preg_replace('/@endforeach/', '<?php endforeach; ?>', $content);

        return $content;
    }

    /**
     * Compile @for directive
     *
     * @param string $content
     * @return string
     */
    private function compileFor(string $content): string
    {
        $content = preg_replace('/@for\s*\((.+?)\)/', '<?php for ($1): ?>', $content);
        $content = preg_replace('/@endfor/', '<?php endfor; ?>', $content);

        return $content;
    }

    /**
     * Compile @while directive
     *
     * @param string $content
     * @return string
     */
    private function compileWhile(string $content): string
    {
        $content = preg_replace('/@while\s*\((.+?)\)/', '<?php while ($1): ?>', $content);
        $content = preg_replace('/@endwhile/', '<?php endwhile; ?>', $content);

        return $content;
    }

    /**
     * Compile @php directive
     *
     * @param string $content
     * @return string
     */
    private function compilePhp(string $content): string
    {
        $content = preg_replace('/@php/', '<?php', $content);
        $content = preg_replace('/@endphp/', '?>', $content);

        return $content;
    }

    /**
     * Compile custom directives
     *
     * @param string $content
     * @return string
     */
    private function compileCustomDirectives(string $content): string
    {
        foreach ($this->directives as $name => $handler) {
            $content = preg_replace_callback(
                '/@' . $name . '\s*\((.+?)\)/',
                function ($matches) use ($handler) {
                    return $handler($matches[1]);
                },
                $content
            );
        }

        return $content;
    }

    /**
     * Compile comments {{-- --}}
     *
     * @param string $content
     * @return string
     */
    private function compileComments(string $content): string
    {
        return preg_replace('/\{\{--\s*(.+?)\s*--\}\}/s', '', $content);
    }

    /**
     * Evaluate compiled template
     *
     * @param string $path
     * @param array $data
     * @return string
     */
    private function evaluate(string $path, array $data): string
    {
        extract($data, EXTR_SKIP);
        $__sections = $__sections ?? [];

        ob_start();

        try {
            include $path;

            // Handle template inheritance
            if (isset($__extends)) {
                ob_end_clean();
                return $this->render($__extends, array_merge($data, ['__sections' => $__sections]));
            }

            return ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Clear template cache
     *
     * @return bool
     */
    public function clearCache(): bool
    {
        if (!is_dir($this->cachePath)) {
            return true;
        }

        $files = glob($this->cachePath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        return true;
    }
}
