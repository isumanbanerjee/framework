<?php
/*
 * COPYRIGHT (c) [SUMAN BANERJEE] - All Rights Reserved
 * SUMAN BANERJEE <contact@isumanbanerjee.com>
 * Project Name: FRAMEWORK
 * Created by: Suman Banerjee <contact@isumanbanerjee.com>.
 */

namespace Configuration;

use Core\Model\EnvFileParser;
use InvalidArgumentException;

/**
 * Class App
 * Handles the configuration management for the application.
 *
 * @package Configuration
 */
class App
{
    /**
     * @var array The array of environment files.
     */
    private array $envFiles = [
        'error'  => __DIR__ . '/error.env',
        'config' => __DIR__ . '/config.env'
    ];

    /**
     * Adds a new configuration to the specified environment file.
     *
     * @param string $fileType The type of the environment file.
     * @param string $key The configuration key.
     * @param string $value The configuration value.
     * @return bool True if the configuration was added, false if the key already exists.
     * @throws InvalidArgumentException If the file type is invalid.
     */
    public function addConfiguration(string $fileType, string $key, string $value): bool
    {
        $filePath = $this->getFilePath($fileType);
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, "$key=") === 0) {
                return false;
            }
        }
        file_put_contents($filePath, file_get_contents($filePath) . "\n$key='$value'");
        $this->processEnvFiles();
        return true;
    }

    /**
     * Updates an existing configuration in the specified environment file.
     *
     * @param string $fileType The type of the environment file.
     * @param string $key The configuration key.
     * @param string $value The new configuration value.
     * @return bool True if the configuration was updated, false if the key does not exist.
     * @throws InvalidArgumentException If the file type is invalid.
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
     * Removes a configuration from the specified environment file.
     *
     * @param string $fileType The type of the environment file.
     * @param string $key The configuration key.
     * @return bool True if the configuration was removed, false if the key does not exist.
     * @throws InvalidArgumentException If the file type is invalid.
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
     * Retrieves the file path for the specified environment file type.
     *
     * @param string $fileType The type of the environment file.
     * @return string The file path.
     * @throws InvalidArgumentException If the file type is invalid.
     */
    private function getFilePath(string $fileType): string
    {
        if (!isset($this->envFiles[$fileType])) {
            throw new InvalidArgumentException("Invalid file type: $fileType");
        }
        return $this->envFiles[$fileType];
    }

    /**
     * Processes the environment files to update their compiled versions.
     */
    private function processEnvFiles(): void
    {
        $parser = new EnvFileParser();
        $parser->processEnvFiles();
    }
}