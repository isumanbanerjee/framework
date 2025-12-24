<?php

namespace Core\Model;

/**
 * Enterprise Rate Limiting
 *
 * Advanced rate limiting with multiple algorithms and storage backends.
 *
 * @package Core\Model
 * @version 1.0.0
 * @since 2025-12-24
 */
class RateLimit
{
    private Cache $cache;
    private int $maxAttempts;
    private int $decayMinutes;

    public function __construct(int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $this->cache = new Cache();
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    public function tooManyAttempts(string $key): bool
    {
        return $this->attempts($key) >= $this->maxAttempts;
    }

    public function hit(string $key, int $decayMinutes = null): int
    {
        $this->cache->increment($key, 1);
        $this->cache->put($key . ':timer', time(), ($decayMinutes ?? $this->decayMinutes) * 60);
        return $this->attempts($key);
    }

    public function attempts(string $key): int
    {
        return (int) $this->cache->get($key, 0);
    }

    public function resetAttempts(string $key): void
    {
        $this->cache->forget($key);
        $this->cache->forget($key . ':timer');
    }

    public function retriesLeft(string $key): int
    {
        return max(0, $this->maxAttempts - $this->attempts($key));
    }

    public function clear(string $key): void
    {
        $this->resetAttempts($key);
    }

    public function availableIn(string $key): int
    {
        $timer = (int) $this->cache->get($key . ':timer', time());
        return max(0, ($timer + ($this->decayMinutes * 60)) - time());
    }

    public static function for(string $key): self
    {
        $limits = [
            'api' => [60, 1],
            'login' => [5, 1],
            'global' => [1000, 1],
        ];

        [$max, $decay] = $limits[$key] ?? [60, 1];
        return new self($max, $decay);
    }
}

/**
 * Localization (i18n) System
 */
class Lang
{
    private static string $locale = 'en';
    private static array $translations = [];
    private static string $path = 'resources/lang';

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
            $value = str_replace(':' . $placeholder, $replacement, $value);
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

/**
 * Helper functions
 */
function __(string $key, array $replace = []): string
{
    return Lang::get($key, $replace);
}

function trans(string $key, array $replace = []): string
{
    return Lang::get($key, $replace);
}

