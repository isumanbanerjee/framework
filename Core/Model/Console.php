<?php

namespace Core\Model;

/**
 * Enterprise CLI Console
 *
 * Command-line interface for running commands, migrations, and tasks.
 *
 * @package Core\Model
 * @version 1.0.0
 * @since 2025-12-24
 */
class Console
{
    private array $commands = [];
    private array $arguments = [];
    private array $options = [];

    public function __construct()
    {
        $this->registerDefaultCommands();
    }

    private function registerDefaultCommands(): void
    {
        $this->commands = [
            'help' => [$this, 'helpCommand'],
            'cache:clear' => [CacheCommand::class, 'clear'],
            'queue:work' => [QueueCommand::class, 'work'],
            'make:controller' => [MakeCommand::class, 'controller'],
            'make:model' => [MakeCommand::class, 'model'],
            'make:middleware' => [MakeCommand::class, 'middleware'],
            'make:migration' => [MakeCommand::class, 'migration'],
            'migrate' => [MigrateCommand::class, 'run'],
            'migrate:rollback' => [MigrateCommand::class, 'rollback'],
            'db:seed' => [SeedCommand::class, 'seed'],
            'config:cache' => [ConfigCacheCommand::class, 'cache'],
            'serve' => [ServerCommand::class, 'serve'],
        ];
    }

    public function register(string $name, callable $handler): void
    {
        $this->commands[$name] = $handler;
    }

    public function run(array $argv): int
    {
        array_shift($argv); // Remove script name
        $command = array_shift($argv) ?? 'help';
        $this->parseArguments($argv);

        if (!isset($this->commands[$command])) {
            $this->error("Command not found: $command");
            return 1;
        }

        try {
            $handler = $this->commands[$command];
            if (is_array($handler) && is_string($handler[0])) {
                $instance = new $handler[0]();
                $handler = [$instance, $handler[1]];
            }
            call_user_func($handler, $this->arguments, $this->options);
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    private function parseArguments(array $argv): void
    {
        foreach ($argv as $arg) {
            if (strpos($arg, '--') === 0) {
                [$key, $value] = explode('=', substr($arg, 2) . '=true', 2);
                $this->options[$key] = $value;
            } elseif (strpos($arg, '-') === 0) {
                $this->options[substr($arg, 1)] = true;
            } else {
                $this->arguments[] = $arg;
            }
        }
    }

    public function info(string $message): void
    {
        echo "\033[32m✓\033[0m $message\n";
    }

    public function error(string $message): void
    {
        echo "\033[31m✗\033[0m $message\n";
    }

    public function line(string $message): void
    {
        echo "$message\n";
    }

    public function ask(string $question): string
    {
        echo "$question: ";
        return trim(fgets(STDIN));
    }

    public function confirm(string $question): bool
    {
        $answer = $this->ask("$question (yes/no)");
        return in_array(strtolower($answer), ['yes', 'y']);
    }

    private function helpCommand(): void
    {
        $this->line("Available commands:");
        foreach (array_keys($this->commands) as $command) {
            $this->line("  php console $command");
        }
    }
}

class CacheCommand
{
    public function clear(): void
    {
        $cache = new Cache();
        $cache->flush();
        (new Console())->info("Cache cleared successfully!");
    }
}

class QueueCommand
{
    public function work($args, $options): void
    {
        $queue = new Queue();
        $queueName = $args[0] ?? null;
        (new Console())->info("Processing queue: " . ($queueName ?? 'default'));
        $queue->work($queueName, 3, 0);
    }
}

class MakeCommand
{
    private const BASE = __DIR__ . '/../..';
    private const STUBS = self::BASE . '/resources/stubs';

    private function generator(): StubGenerator
    {
        return new StubGenerator(self::STUBS);
    }

    private function write(string $relativePath, string $content): void
    {
        $path = self::BASE . '/' . ltrim($relativePath, '/');
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
        (new Console())->info("Created: {$relativePath}");
    }

    public function controller($args): void
    {
        $name = $args[0] ?? null;
        if (!$name) {
            (new Console())->error("Controller name required");
            return;
        }

        $content = $this->generator()->render('controller', ['name' => $name]);
        $this->write("System/Controller/{$name}.php", $content);
    }

    public function model($args): void
    {
        $name = $args[0] ?? null;
        if (!$name) {
            (new Console())->error("Model name required");
            return;
        }

        $table = strtolower($name) . 's';
        $content = $this->generator()->render('model', ['name' => $name, 'table' => $table]);
        $this->write("System/Model/{$name}.php", $content);
    }

    public function middleware($args): void
    {
        $name = $args[0] ?? null;
        if (!$name) {
            (new Console())->error("Middleware name required");
            return;
        }

        $content = $this->generator()->render('middleware', ['name' => $name]);
        $this->write("System/Middleware/{$name}.php", $content);
    }

    public function migration($args): void
    {
        $name = $args[0] ?? null;
        if (!$name) {
            (new Console())->error("Migration name required (e.g. create_users_table)");
            return;
        }

        $timestamp = date('Y_m_d_His');
        $fileName = StubGenerator::migrationFileName($name, $timestamp);
        $className = StubGenerator::studly($name);

        // Best-effort table name inference: create_users_table -> users
        $table = preg_replace('/^create_|_table$/', '', $name) ?: 'table_name';

        $content = $this->generator()->render('migration', [
            'name' => $className,
            'table' => $table,
        ]);

        $this->write("database/migrations/{$fileName}.php", $content);
    }
}

class MigrateCommand
{
    private function migrator(): \Core\Model\Database\Migrator
    {
        $pdo = \Core\Model\Database\Connection::make(App::all());
        return new \Core\Model\Database\Migrator($pdo, __DIR__ . '/../../database/migrations');
    }

    public function run(): void
    {
        $console = new Console();
        $ran = $this->migrator()->run();

        if (empty($ran)) {
            $console->line('Nothing to migrate.');
            return;
        }

        foreach ($ran as $migration) {
            $console->info("Migrated: {$migration}");
        }
    }

    public function rollback(): void
    {
        $console = new Console();
        $rolledBack = $this->migrator()->rollback();

        if (empty($rolledBack)) {
            $console->line('Nothing to roll back.');
            return;
        }

        foreach ($rolledBack as $migration) {
            $console->info("Rolled back: {$migration}");
        }
    }
}

class SeedCommand
{
    public function seed($args): void
    {
        $console = new Console();
        $class = $args[0] ?? null;

        if (!$class) {
            $console->error('Seeder class required (e.g. System\\Model\\UserSeeder)');
            return;
        }

        if (!class_exists($class)) {
            $console->error("Seeder class not found: {$class}");
            return;
        }

        $pdo = \Core\Model\Database\Connection::make(App::all());
        $seeder = new $class($pdo);
        $seeder->run();

        $console->info("Seeded: {$class}");
    }
}

class ConfigCacheCommand
{
    public function cache(): void
    {
        $console = new Console();
        $config = App::all();

        $path = __DIR__ . '/../../Configuration/config_compiled.php';
        $content = '<?php' . PHP_EOL . PHP_EOL
            . '// Auto-generated config cache. Do not edit.' . PHP_EOL
            . 'return ' . var_export($config, true) . ';' . PHP_EOL;

        file_put_contents($path, $content);
        $console->info('Configuration cached.');
    }
}

class ServerCommand
{
    public function serve($args, $options): void
    {
        $port = $options['port'] ?? 8000;
        (new Console())->info("Server started on http://localhost:$port");
        (new Console())->line("Press Ctrl+C to stop");
        exec("php -S localhost:$port");
    }
}

