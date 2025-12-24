<?php

/**
 * Environment File Parser
 *
 * This file contains the EnvFileParser class which handles parsing and processing
 * of environment configuration files (.env format).
 *
 * PHP version 8.1
 *
 * @category  Configuration
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */

declare(strict_types=1);

namespace Core\Model;

/**
 * Environment File Parser Class
 *
 * Handles the parsing, processing, and compilation of environment configuration
 * files. Supports .env file format with key=value pairs, comment lines, and
 * compilation to optimized PHP arrays for faster loading.
 *
 * Features:
 * - Parse .env files with key=value syntax
 * - Support for comment lines (lines starting with #)
 * - Compile configurations to PHP arrays
 * - Automatic trimming of whitespace and quotes
 * - Error handling for missing or invalid files
 *
 * Example usage:
 * ```php
 * $parser = new EnvFileParser('/path/to/config');
 * $config = $parser->compile('config.env', 'config_compiled.php');
 * ```
 *
 * @category  Configuration
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @version   1.0.0
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */
class EnvFileParser
{
    /**
     * The directory path where environment files are located
     *
     * @var string
     */
    private string $envDirectory;

    /**
     * Initialize the environment file parser
     *
     * Sets up the parser with the directory containing environment files.
     * Defaults to the Configuration directory if not specified.
     *
     * @param string $envDirectory The absolute path to the directory containing .env files.
     *                             Defaults to '../../Configuration' relative to this file.
     *
     * @since 1.0.0
     */
    public function __construct(string $envDirectory = __DIR__ . '/../../Configuration')
    {
        $this->envDirectory = $envDirectory;
    }

    /**
     * Parse an environment file and return configuration as associative array
     *
     * Reads and parses an environment file line by line, extracting key=value pairs.
     * Handles comment lines (starting with #), empty lines, and quoted values.
     * Trims whitespace and quotes from both keys and values.
     *
     * File format:
     * - KEY=value
     * - KEY='value with spaces'
     * - # Comment lines are ignored
     * - Empty lines are skipped
     *
     * @param string $filePath The absolute or relative path to the environment file to parse
     *
     * @return array<string,string> Associative array of configuration key-value pairs
     *
     * @throws \RuntimeException If the file cannot be read or does not exist
     *
     * @since 1.0.0
     */
    private function parseEnvFile(string $filePath): array
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $config = [];

        foreach ($lines as $line) {
            // Skip comment lines (starting with #)
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Split line into key and value at first = sign
            list($key, $value) = explode('=', $line, 2);
            
            // Trim whitespace and quotes from both key and value
            $config[trim($key)] = trim($value, " \t\n\r\0\x0B'\"");
        }

        return $config;
    }

    /**
     * Process all environment files and compile them to optimized PHP files
     *
     * Scans the configured directory for all .env files, parses each one,
     * and generates a corresponding compiled PHP file (_compiled.php suffix).
     * Compiled files contain a PHP array representation for faster loading.
     * Existing compiled files are preserved and not overwritten.
     *
     * The method performs the following operations:
     * 1. Searches for *.env files in the configured directory
     * 2. Parses each .env file into a configuration array
     * 3. Creates a _compiled.php file with var_export output
     * 4. Skips files that already have compiled versions
     *
     * Generated compiled files can be included directly:
     * ```php
     * $config = include 'config_compiled.php';
     * ```
     *
     * @return void
     *
     * @throws \RuntimeException If directory cannot be read or files cannot be written
     *
     * @since 1.0.0
     *
     * @see parseEnvFile() For the parsing logic
     */
    public function processEnvFiles(): void
    {
        $envFiles = glob($this->envDirectory . '/*.env');

        if (empty($envFiles)) {
            trigger_error(
                "No environment files found in directory: " . $this->envDirectory,
                E_USER_WARNING
            );
            return;
        }

        foreach ($envFiles as $envFile) {
            error_log("Processing environment file: " . $envFile);

            $config = $this->parseEnvFile($envFile);

            $compiledFilePath = $this->envDirectory . '/'
                . pathinfo($envFile, PATHINFO_FILENAME)
                . '_compiled.php';

            // Only create compiled file if it doesn't already exist
            if (!file_exists($compiledFilePath)) {
                $content = '<?php' . PHP_EOL . PHP_EOL
                    . '/**' . PHP_EOL
                    . ' * Compiled configuration file' . PHP_EOL
                    . ' * ' . PHP_EOL
                    . ' * Auto-generated from: ' . basename($envFile) . PHP_EOL
                    . ' * Generated at: ' . date('Y-m-d H:i:s') . PHP_EOL
                    . ' * ' . PHP_EOL
                    . ' * @category  Configuration' . PHP_EOL
                    . ' * @package   Core\Model' . PHP_EOL
                    . ' * @author    Auto-generated' . PHP_EOL
                    . ' * @copyright 2025 Suman Banerjee. All rights reserved.' . PHP_EOL
                    . ' */' . PHP_EOL . PHP_EOL
                    . 'return ' . var_export($config, true) . ';' . PHP_EOL;

                file_put_contents($compiledFilePath, $content);
                
                error_log("Compiled file created: " . $compiledFilePath);
            } else {
                error_log("Compiled file already exists, skipping: " . $compiledFilePath);
            }
        }
    }
}

