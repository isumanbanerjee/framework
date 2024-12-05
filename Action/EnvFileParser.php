<?php

namespace Action;

/**
 * Class EnvFileParser
 *
 * This class is responsible for parsing environment files and creating compiled PHP files from them.
 * It reads the environment files, processes each line to extract key-value pairs, and generates a PHP file
 * with the parsed configuration.
 *
 * Usage:
 * $parser = new EnvFileParser();
 * $parser->processEnvFiles();
 *
 * @package Action
 * @version 1.0.0
 * @since 2024-02-04
 * @license MIT
 */
class EnvFileParser
{
    /**
     * @var string Directory to scan for environment files.
     */
    private string $envDirectory;

    /**
     * Constructor to initialize the directory to scan for environment files.
     *
     * @param string $envDirectory Directory to scan for environment files.
     */
    public function __construct(string $envDirectory = __DIR__ . '/../Configuration')
    {
        $this->envDirectory = $envDirectory;
    }

    /**
     * Parses an environment file and returns its configuration as an associative array.
     *
     * @param string $filePath The path to the environment file.
     * @return array The parsed configuration.
     */
    private function parseEnvFile(string $filePath): array
    {
        // Read the file into an array of lines, ignoring empty lines
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $config = [];

        // Process each line in the file
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Split the line into key and value
            list($key, $value) = explode('=', $line, 2);
            $config[trim($key)] = trim($value, " \t\n\r\0\x0B'");
        }

        return $config;
    }

    /**
     * Processes each environment file and creates a compiled PHP file if it does not exist.
     *
     * This method scans the directory for .env files, parses each file to extract the configuration,
     * and generates a compiled PHP file with the parsed configuration. If the compiled file already exists,
     * it will not be overwritten.
     */
    public function processEnvFiles()
    {
        // Scan the directory for .env files
        $envFiles = glob($this->envDirectory . '/*.env');

        // Check if there are environment files to process
        if (empty($envFiles)) {
            trigger_error("No environment files found in directory: " . $this->envDirectory, E_USER_WARNING);
            return;
        }

        // Iterate over each environment file
        foreach ($envFiles as $envFile) {
            // Log the constructed file path
            error_log("Processing file: " . $envFile);

            // Parse the environment file
            $config = $this->parseEnvFile($envFile);

            // Construct the path for the compiled PHP file
            $compiledFilePath = $this->envDirectory . '/' . pathinfo($envFile, PATHINFO_FILENAME) . '_compiled.php';

            // If the compiled file does not exist, create it
            if (!file_exists($compiledFilePath)) {
                file_put_contents($compiledFilePath, '<?php return ' . var_export($config, true) . ';');
            }
        }
    }
}