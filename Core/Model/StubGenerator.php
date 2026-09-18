<?php

/**
 * Stub Generator
 *
 * Renders a stub template by substituting {{placeholder}} tokens, and provides
 * naming helpers used by the console generator commands.
 *
 * PHP version 8.1
 *
 * @category  Support
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */

declare(strict_types=1);

namespace Core\Model;

use RuntimeException;

/**
 * StubGenerator Class
 *
 * Example:
 * ```php
 * $generator = new StubGenerator(__DIR__ . '/../../resources/stubs');
 * $code = $generator->render('controller', ['name' => 'UserController']);
 * ```
 *
 * @category  Support
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
class StubGenerator
{
    /**
     * Directory containing *.stub templates.
     *
     * @var string
     */
    private string $stubPath;

    /**
     * @param string $stubPath Directory containing stub templates.
     */
    public function __construct(string $stubPath)
    {
        $this->stubPath = rtrim($stubPath, '/\\');
    }

    /**
     * Render a stub with the given replacements.
     *
     * @param string                $stub         Stub name (without .stub).
     * @param array<string,string>  $replacements Placeholder => value map.
     *
     * @return string
     *
     * @throws RuntimeException When the stub file does not exist.
     */
    public function render(string $stub, array $replacements): string
    {
        $file = $this->stubPath . '/' . $stub . '.stub';

        if (!is_file($file)) {
            throw new RuntimeException("Stub not found: {$file}");
        }

        $content = (string) file_get_contents($file);

        foreach ($replacements as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }

    /**
     * Convert a snake_case or kebab-case name to StudlyCase.
     *
     * @param string $value Input name.
     *
     * @return string
     */
    public static function studly(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', $value);

        return str_replace(' ', '', ucwords($value));
    }

    /**
     * Build a timestamped migration file name from a snake_case description.
     *
     * @param string $name      Migration description, e.g. "create_users_table".
     * @param string $timestamp Timestamp prefix (e.g. "2026_01_28_000001").
     *
     * @return string File name without extension.
     */
    public static function migrationFileName(string $name, string $timestamp): string
    {
        return $timestamp . '_' . trim($name, '_');
    }
}
