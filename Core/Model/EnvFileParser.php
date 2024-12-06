<?php
/*
 * COPYRIGHT (c) [SUMAN BANERJEE] - All Rights Reserved
 * SUMAN BANERJEE <contact@isumanbanerjee.com>
 * Project Name: FRAMEWORK
 * Created by: Suman Banerjee <contact@isumanbanerjee.com>.
 */

namespace Core\Model;

/**
 * Class EnvFileParser
 * Handles the parsing and processing of environment files.
 *
 * @package Core\Model
 */
class EnvFileParser
{
    /**
     * @var string The directory where environment files are located.
     */
    private string $envDirectory;

    /**
     * EnvFileParser constructor.
     *
     * @param string $envDirectory The directory where environment files are located.
     */
    public function __construct(string $envDirectory = __DIR__ . '/../../Configuration')
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
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $config = [];

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $config[trim($key)] = trim($value, " \t\n\r\0\x0B'");
        }

        return $config;
    }

    /**
     * Processes all environment files in the directory and creates compiled PHP files for each.
     */
    public function processEnvFiles(): void
    {
        $envFiles = glob($this->envDirectory . '/*.env');

        if (empty($envFiles)) {
            trigger_error("No environment files found in directory: " . $this->envDirectory, E_USER_WARNING);
            return;
        }

        foreach ($envFiles as $envFile) {
            error_log("Processing file: " . $envFile);

            $config = $this->parseEnvFile($envFile);

            $compiledFilePath = $this->envDirectory . '/' . pathinfo($envFile, PATHINFO_FILENAME) . '_compiled.php';

            if (!file_exists($compiledFilePath)) {
                file_put_contents($compiledFilePath, '<?php return ' . var_export($config, true) . ';');
            }
        }
    }
}