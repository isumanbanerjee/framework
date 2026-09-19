<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Console;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ConsoleTest extends TestCase
{
    /**
     * @param array<int,string> $argv
     *
     * @return array<string,mixed>
     */
    private function parsedOptions(array $argv): array
    {
        $console = new Console();
        $reflection = new ReflectionClass($console);

        $parse = $reflection->getMethod('parseArguments');
        $parse->setAccessible(true);
        $parse->invoke($console, $argv);

        $options = $reflection->getProperty('options');
        $options->setAccessible(true);

        return $options->getValue($console);
    }

    public function testParsesKeyValueOption(): void
    {
        $this->assertSame(['routes' => 'routes/api.php'], $this->parsedOptions(['--routes=routes/api.php']));
    }

    public function testParsesValueContainingAnEqualsSignWithoutTruncation(): void
    {
        // Regression test: appending "=true" before splitting used to corrupt
        // any value that already contained "=", turning "name=John" into
        // "name=John=true".
        $this->assertSame(['filter' => 'name=John'], $this->parsedOptions(['--filter=name=John']));
    }

    public function testParsesFlagOptionAsBooleanTrue(): void
    {
        $this->assertSame(['verbose' => true], $this->parsedOptions(['--verbose']));
    }

    public function testParsesShortFlagAsBooleanTrue(): void
    {
        $this->assertSame(['v' => true], $this->parsedOptions(['-v']));
    }

    public function testParsesPositionalArguments(): void
    {
        $console = new Console();
        $reflection = new ReflectionClass($console);

        $parse = $reflection->getMethod('parseArguments');
        $parse->setAccessible(true);
        $parse->invoke($console, ['users', '--format=json']);

        $arguments = $reflection->getProperty('arguments');
        $arguments->setAccessible(true);

        $this->assertSame(['users'], $arguments->getValue($console));
    }

    public function testDocsGenerateCommandWritesOpenApiSpecFromRoutesFile(): void
    {
        $root = dirname(__DIR__, 2);
        $routesFile = 'tmp_console_test_routes_' . uniqid() . '.php';
        $outputFile = 'tmp_console_test_output_' . uniqid() . '.json';

        file_put_contents($root . '/' . $routesFile, <<<'PHP'
            <?php

            use Core\Model\Request;
            use Core\Model\Response;
            use Core\Model\Router;

            $router = new Router(new Request(), new Response());
            $router->get('/ping', fn () => 'pong')->name('ping');

            return $router;
            PHP);

        try {
            $exitCode = (new Console())->run([
                'console',
                'docs:generate',
                "--routes={$routesFile}",
                "--output={$outputFile}",
                '--title=Integration Test API',
            ]);

            $this->assertSame(0, $exitCode);
            $this->assertFileExists($root . '/' . $outputFile);

            $spec = json_decode((string) file_get_contents($root . '/' . $outputFile), true);

            $this->assertSame('Integration Test API', $spec['info']['title']);
            $this->assertArrayHasKey('/ping', $spec['paths']);
            $this->assertSame('ping', $spec['paths']['/ping']['get']['operationId']);
        } finally {
            @unlink($root . '/' . $routesFile);
            @unlink($root . '/' . $outputFile);
        }
    }

    public function testDocsGenerateCommandReportsErrorWithoutRoutesOption(): void
    {
        ob_start();
        (new Console())->run(['console', 'docs:generate']);
        $output = ob_get_clean();

        $this->assertStringContainsString('Routes file required', $output);
    }

    public function testRouteCacheCommandWritesCompiledRoutesAndReportsSkips(): void
    {
        $root = dirname(__DIR__, 2);
        $routesFile = 'tmp_console_test_routes_' . uniqid() . '.php';
        $cacheFile = $root . '/Configuration/routes_compiled.php';

        file_put_contents($root . '/' . $routesFile, <<<'PHP'
            <?php

            use Core\Model\Request;
            use Core\Model\Response;
            use Core\Model\Router;

            $router = new Router(new Request(), new Response());
            $router->get('/ping', 'System\Controller\UserController@index')->name('ping');
            $router->get('/closure', fn () => 'nope');

            return $router;
            PHP);

        try {
            ob_start();
            $exitCode = (new Console())->run([
                'console',
                'route:cache',
                "--routes={$routesFile}",
            ]);
            $output = ob_get_clean();

            $this->assertSame(0, $exitCode);
            $this->assertStringContainsString('1 routes cached', $output);
            $this->assertStringContainsString('1 route(s) skipped', $output);
            $this->assertFileExists($cacheFile);

            $cached = require $cacheFile;
            $this->assertCount(1, $cached);
            $this->assertSame('/ping', $cached[0]['path']);
            $this->assertSame('ping', $cached[0]['name']);
        } finally {
            @unlink($root . '/' . $routesFile);
            @unlink($cacheFile);
        }
    }

    public function testRouteClearCommandRemovesCompiledRoutesFile(): void
    {
        $root = dirname(__DIR__, 2);
        $cacheFile = $root . '/Configuration/routes_compiled.php';
        file_put_contents($cacheFile, '<?php return [];');

        ob_start();
        $exitCode = (new Console())->run(['console', 'route:clear']);
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Route cache cleared', $output);
        $this->assertFileDoesNotExist($cacheFile);
    }

    public function testRouteCacheCommandReportsErrorWithoutRoutesOption(): void
    {
        ob_start();
        (new Console())->run(['console', 'route:cache']);
        $output = ob_get_clean();

        $this->assertStringContainsString('Routes file required', $output);
    }
}
