<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\EnvFileParser;
use PHPUnit\Framework\TestCase;

final class EnvFileParserTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/envparser_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDir . '/*') as $file) {
            unlink($file);
        }
        rmdir($this->tempDir);
    }

    public function testProcessEnvFilesCompilesKeyValuePairs(): void
    {
        file_put_contents(
            $this->tempDir . '/config.env',
            "# a comment\nAPP_NAME=MyApp\nAPP_DEBUG='true'\n\nDB_HOST=\"localhost\"\n"
        );

        $parser = new EnvFileParser($this->tempDir);
        $parser->processEnvFiles();

        $compiledPath = $this->tempDir . '/config_compiled.php';
        $this->assertFileExists($compiledPath);

        $config = include $compiledPath;
        $this->assertSame([
            'APP_NAME' => 'MyApp',
            'APP_DEBUG' => 'true',
            'DB_HOST' => 'localhost',
        ], $config);
    }

    public function testDoesNotOverwriteExistingCompiledFile(): void
    {
        file_put_contents($this->tempDir . '/config.env', "KEY=new_value\n");
        file_put_contents(
            $this->tempDir . '/config_compiled.php',
            "<?php\nreturn ['KEY' => 'old_value'];\n"
        );

        $parser = new EnvFileParser($this->tempDir);
        $parser->processEnvFiles();

        $config = include $this->tempDir . '/config_compiled.php';
        $this->assertSame(['KEY' => 'old_value'], $config);
    }

    public function testWarnsWhenNoEnvFilesFound(): void
    {
        $parser = new EnvFileParser($this->tempDir);

        $triggered = false;
        set_error_handler(function () use (&$triggered) {
            $triggered = true;
            return true;
        }, E_USER_WARNING);

        $parser->processEnvFiles();

        restore_error_handler();
        $this->assertTrue($triggered);
    }
}
