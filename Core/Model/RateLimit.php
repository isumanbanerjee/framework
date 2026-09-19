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
