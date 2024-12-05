<?php

namespace Configuration;

use Action\EnvFileParser;
use InvalidArgumentException;

/**
 * Class App
 *
 * This class provides methods to add, update, or remove configuration data to/from the .env files
 * in the Configuration directory. It maintains an array of the .env files and provides
 * methods to add, update, or remove configurations based on the file type.
 *
 * @package Configuration
 * @version 1.0.0
 * @since 1.0.0
 * @author Suman Banerjee
 * @license GPL-3.0-or-later
 * @link https://github.com/isumanbanerjee/framework
 * @see EnvFileParser
 * @tutorial https://example.com/tutorial
 * @copyright 2023
 */
class App
{
    /**
     * @var array An associative array mapping file types to their respective .env file paths.
     * @access private
     */
    private array $envFiles = [
        'error'  => __DIR__ . '/error.env',
        'config' => __DIR__ . '/config.env'
    ];

    /**
     * Adds a configuration to the specified .env file if it does not already exist.
     *
     * @param string $fileType The type of the .env file (e.g., 'error', 'config').
     * @param string $key The configuration key.
     * @param string $value The configuration value.
     * @return bool True if the configuration was added, false otherwise.
     * @since 1.0.0
     * @example $app->addConfiguration('config', 'key', 'value');
     * @link https://github.com/isumanbanerjee/framework#addConfiguration
     */
    public function addConfiguration(string $fileType, string $key, string $value): bool
    {
        $filePath = $this->getFilePath($fileType);
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, "$key=") === 0) {
                return false; // Key already exists, do not add again
            }
        }
        file_put_contents($filePath, file_get_contents($filePath) . "\n$key='$value'");
        $this->processEnvFiles();
        return true;
    }

    /**
     * Updates a configuration in the specified .env file.
     *
     * @param string $fileType The type of the .env file (e.g., 'error', 'config').
     * @param string $key The configuration key.
     * @param string $value The new configuration value.
     * @return bool True if the configuration was updated, false otherwise.
     * @since 1.0.0
     * @example $app->updateConfiguration('config', 'key', 'new_value');
     * @link https://github.com/isumanbanerjee/framework#updateConfiguration
     */
    public function updateConfiguration(string $fileType, string $key, string $value): bool
    {
        $filePath = $this->getFilePath($fileType);
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $updated = false;
        foreach ($lines as &$line) {
            if (strpos($line, "$key=") === 0) {
                $line = "$key='$value'";
                $updated = true;
            }
        }
        if ($updated) {
            file_put_contents($filePath, implode("\n", $lines));
            $this->processEnvFiles();
            return true;
        }
        return false;
    }

    /**
     * Removes a configuration from the specified .env file.
     *
     * @param string $fileType The type of the .env file (e.g., 'error', 'config').
     * @param string $key The configuration key to remove.
     * @return bool True if the configuration was removed, false otherwise.
     * @since 1.0.0
     * @example $app->removeConfiguration('config', 'key');
     * @link https://github.com/isumanbanerjee/framework#removeConfiguration
     */
    public function removeConfiguration(string $fileType, string $key): bool
    {
        $filePath = $this->getFilePath($fileType);
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $newLines = array_filter($lines, fn($line) => strpos($line, "$key=") !== 0);
        if (count($newLines) !== count($lines)) {
            file_put_contents($filePath, implode("\n", $newLines));
            $this->processEnvFiles();
            return true;
        }
        return false;
    }

    /**
     * Retrieves the file path for the specified file type.
     *
     * @param string $fileType The type of the .env file (e.g., 'error', 'config').
     * @return string The file path of the specified .env file.
     * @throws InvalidArgumentException if the file type is invalid.
     * @since 1.0.0
     * @internal This method is for internal use only.
     */
    private function getFilePath(string $fileType): string
    {
        if (!isset($this->envFiles[$fileType])) {
            throw new InvalidArgumentException("Invalid file type: $fileType");
        }
        return $this->envFiles[$fileType];
    }

    /**
     * Processes the .env files using the EnvFileParser.
     *
     * @return void
     * @since 1.0.0
     * @ignore
     */
    private function processEnvFiles(): void
    {
        $parser = new EnvFileParser();
        $parser->processEnvFiles();
    }
}