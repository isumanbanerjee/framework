<?php

namespace Core\Model;

use ArrayAccess;
use Countable;
use Iterator;

/**
 * Enterprise Collection Class
 *
 * Fluent array manipulation with chainable methods inspired by Laravel Collections.
 *
 * @package Core\Model
 * @version 1.0.0
 * @since 2025-12-24
 */
class Collection implements ArrayAccess, Countable, Iterator
{
    protected array $items = [];
    private int $position = 0;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public static function make(array $items = []): self
    {
        return new self($items);
    }

    public function all(): array { return $this->items; }
    public function count(): int { return count($this->items); }
    public function isEmpty(): bool { return empty($this->items); }
    public function isNotEmpty(): bool { return !$this->isEmpty(); }
    public function first() { return $this->items[0] ?? null; }
    public function last() { return end($this->items) ?: null; }

    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->items));
    }

    public function filter(callable $callback = null): self
    {
        return new self($callback ? array_filter($this->items, $callback) : array_filter($this->items));
    }

    public function where(string $key, $value): self
    {
        return $this->filter(fn($item) => is_array($item) ? ($item[$key] ?? null) === $value : ($item->$key ?? null) === $value);
    }

    public function pluck(string $key): self
    {
        return $this->map(fn($item) => is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null));
    }

    public function unique(): self
    {
        return new self(array_unique($this->items));
    }

    public function sort(callable $callback = null): self
    {
        $items = $this->items;
        $callback ? usort($items, $callback) : sort($items);
        return new self($items);
    }

    public function reverse(): self
    {
        return new self(array_reverse($this->items));
    }

    public function chunk(int $size): self
    {
        return new self(array_chunk($this->items, $size));
    }

    public function take(int $limit): self
    {
        return new self(array_slice($this->items, 0, $limit));
    }

    public function skip(int $count): self
    {
        return new self(array_slice($this->items, $count));
    }

    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }
        return $this;
    }

    public function sum(string $key = null)
    {
        return $key ? array_sum($this->pluck($key)->all()) : array_sum($this->items);
    }

    public function avg(string $key = null)
    {
        $count = $this->count();
        return $count > 0 ? $this->sum($key) / $count : 0;
    }

    public function min(string $key = null)
    {
        return $key ? min($this->pluck($key)->all()) : min($this->items);
    }

    public function max(string $key = null)
    {
        return $key ? max($this->pluck($key)->all()) : max($this->items);
    }

    public function groupBy(string $key): self
    {
        $groups = [];
        foreach ($this->items as $item) {
            $value = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);
            $groups[$value][] = $item;
        }
        return new self($groups);
    }

    public function toJson(): string
    {
        return json_encode($this->items);
    }

    // ArrayAccess
    public function offsetExists($offset): bool { return isset($this->items[$offset]); }
    public function offsetGet($offset): mixed { return $this->items[$offset] ?? null; }
    public function offsetSet($offset, $value): void { $this->items[$offset] = $value; }
    public function offsetUnset($offset): void { unset($this->items[$offset]); }

    // Iterator
    public function rewind(): void { $this->position = 0; }
    public function current(): mixed { return array_values($this->items)[$this->position] ?? null; }
    public function key(): mixed { return array_keys($this->items)[$this->position] ?? null; }
    public function next(): void { ++$this->position; }
    public function valid(): bool { return isset(array_values($this->items)[$this->position]); }
}

/**
 * Helper function
 */
function collect(array $items = []): Collection
{
    return new Collection($items);
}

