<?php

/**
 * Localization (i18n) System
 *
 * PHP version 8.1
 *
 * @category  I18n
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
 * Lang Class
 *
 * Loads dot-notation translation strings from `resources/lang/{locale}.php`
 * and resolves `:placeholder` replacements.
 *
 * @category  I18n
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
class Lang
{
    private static string $locale = 'en';

    private static array $translations = [];

    private static string $path = __DIR__ . '/../../resources/lang';

    public static function setLocale(string $locale): void
    {
        self::$locale = $locale;
        self::load($locale);
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }

    private static function load(string $locale): void
    {
        $file = self::$path . '/' . $locale . '.php';

        if (file_exists($file)) {
            self::$translations = include $file;
        }
    }

    public static function get(string $key, array $replace = []): string
    {
        $keys = explode('.', $key);
        $value = self::$translations;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $key;
            }
            $value = $value[$k];
        }

        foreach ($replace as $placeholder => $replacement) {
            $value = str_replace(':' . $placeholder, (string) $replacement, $value);
        }

        return $value;
    }

    public static function has(string $key): bool
    {
        $keys = explode('.', $key);
        $value = self::$translations;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return false;
            }
            $value = $value[$k];
        }

        return true;
    }
}
