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
    public function controller($args): void
    {
        $name = $args[0] ?? null;
        if (!$name) {
            (new Console())->error("Controller name required");
            return;
        }
        $content = "<?php\n\nnamespace System\Controller;\n\nclass {$name}\n{\n    public function index()\n    {\n        // Your code here\n    }\n}\n";
        file_put_contents("System/Controller/{$name}.php", $content);
        (new Console())->info("Controller created: {$name}");
    }

    public function model($args): void
    {
        $name = $args[0] ?? null;
        if (!$name) {
            (new Console())->error("Model name required");
            return;
        }
        $content = "<?php\n\nnamespace System\Model;\n\nclass {$name}\n{\n    // Your model code here\n}\n";
        file_put_contents("System/Model/{$name}.php", $content);
        (new Console())->info("Model created: {$name}");
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

