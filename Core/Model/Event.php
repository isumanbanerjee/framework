<?php

namespace Core\Model;

use Exception;
use Closure;

/**
 * Enterprise Event System
 *
 * Event-driven architecture with observers, listeners, subscribers,
 * event broadcasting, and queue integration.
 *
 * Features:
 * - Event firing and listening
 * - Multiple listeners per event
 * - Event subscribers
 * - Wildcard listeners
 * - Event broadcasting
 * - Queue integration
 * - Event stopping
 * - Priority-based listeners
 * - Event object support
 *
 * @package Core\Model
 * @version 1.0.0
 * @since 2025-12-24
 */
class Event
{
    /**
     * Registered event listeners
     *
     * @var array
     */
    private static array $listeners = [];

    /**
     * Registered wildcard listeners
     *
     * @var array
     */
    private static array $wildcards = [];

    /**
     * Queue instance for queued events
     *
     * @var Queue|null
     */
    private static ?Queue $queue = null;

    /**
     * Register event listener
     *
     * @param string|array $events Event name(s)
     * @param callable|string $listener Listener callback or class
     * @param int $priority Priority (lower = earlier)
     * @return void
     */
    public static function listen($events, $listener, int $priority = 0): void
    {
        foreach ((array) $events as $event) {
            if (strpos($event, '*') !== false) {
                self::$wildcards[$event][] = [
                    'listener' => $listener,
                    'priority' => $priority
                ];
            } else {
                self::$listeners[$event][] = [
                    'listener' => $listener,
                    'priority' => $priority
                ];
            }
        }
    }

    /**
     * Register event subscriber
     *
     * @param string|object $subscriber Subscriber class or instance
     * @return void
     */
    public static function subscribe($subscriber): void
    {
        if (is_string($subscriber)) {
            $subscriber = new $subscriber();
        }

        $events = $subscriber->subscribe();

        foreach ($events as $event => $listeners) {
            foreach ((array) $listeners as $listener) {
                self::listen($event, [$subscriber, $listener]);
            }
        }
    }

    /**
     * Fire an event
     *
     * @param string|object $event Event name or object
     * @param mixed $payload Event payload
     * @param bool $halt Stop after first non-null response
     * @return array|mixed
     */
    public static function fire($event, $payload = [], bool $halt = false)
    {
        [$event, $payload] = self::parseEventAndPayload($event, $payload);

        $responses = [];
        $listeners = self::getListeners($event);

        foreach ($listeners as $listener) {
            $response = self::callListener($listener, $event, $payload);

            if ($halt && $response !== null) {
                return $response;
            }

            if ($response === false) {
                break;
            }

            $responses[] = $response;
        }

        return $halt ? null : $responses;
    }

    /**
     * Fire event until first non-null response
     *
     * @param string|object $event
     * @param mixed $payload
     * @return mixed
     */
    public static function until($event, $payload = [])
    {
        return self::fire($event, $payload, true);
    }

    /**
     * Queue an event for async processing
     *
     * @param string|object $event
     * @param mixed $payload
     * @param string|null $queue
     * @return void
     */
    public static function queue($event, $payload = [], ?string $queue = null): void
    {
        if (self::$queue === null) {
            self::$queue = new Queue();
        }

        [$event, $payload] = self::parseEventAndPayload($event, $payload);

        self::$queue->push(function() use ($event, $payload) {
            self::fire($event, $payload);
        }, [], $queue);
    }

    /**
     * Dispatch event (alias for fire)
     *
     * @param string|object $event
     * @param mixed $payload
     * @param bool $halt
     * @return array|mixed
     */
    public static function dispatch($event, $payload = [], bool $halt = false)
    {
        return self::fire($event, $payload, $halt);
    }

    /**
     * Remove event listeners
     *
     * @param string $event
     * @return void
     */
    public static function forget(string $event): void
    {
        unset(self::$listeners[$event]);
    }

    /**
     * Remove all event listeners
     *
     * @return void
     */
    public static function flush(): void
    {
        self::$listeners = [];
        self::$wildcards = [];
    }

    /**
     * Check if event has listeners
     *
     * @param string $event
     * @return bool
     */
    public static function hasListeners(string $event): bool
    {
        return !empty(self::getListeners($event));
    }

    /**
     * Get listeners for event
     *
     * @param string $event
     * @return array
     */
    private static function getListeners(string $event): array
    {
        $listeners = self::$listeners[$event] ?? [];

        // Add wildcard listeners
        foreach (self::$wildcards as $pattern => $wildcardListeners) {
            if (self::eventMatches($pattern, $event)) {
                $listeners = array_merge($listeners, $wildcardListeners);
            }
        }

        // Sort by priority
        usort($listeners, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        return array_column($listeners, 'listener');
    }

    /**
     * Check if event matches pattern
     *
     * @param string $pattern
     * @param string $event
     * @return bool
     */
    private static function eventMatches(string $pattern, string $event): bool
    {
        $pattern = preg_quote($pattern, '/');
        $pattern = str_replace('\*', '.*', $pattern);
        return (bool) preg_match('/^' . $pattern . '$/', $event);
    }

    /**
     * Parse event and payload
     *
     * @param string|object $event
     * @param mixed $payload
     * @return array
     */
    private static function parseEventAndPayload($event, $payload): array
    {
        if (is_object($event)) {
            return [get_class($event), $event];
        }

        return [$event, $payload];
    }

    /**
     * Call event listener
     *
     * @param mixed $listener
     * @param string $event
     * @param mixed $payload
     * @return mixed
     */
    private static function callListener($listener, string $event, $payload)
    {
        try {
            if ($listener instanceof Closure) {
                return $listener($payload, $event);
            }

            if (is_string($listener) && class_exists($listener)) {
                $listener = new $listener();
            }

            if (is_array($listener) && count($listener) === 2) {
                return call_user_func($listener, $payload, $event);
            }

            if (is_object($listener) && method_exists($listener, 'handle')) {
                return $listener->handle($payload, $event);
            }

            return null;
        } catch (Exception $e) {
            $logger = new Logger('logs/events.log');
            $logger->logError('Event listener failed', [
                'event' => $event,
                'listener' => is_object($listener) ? get_class($listener) : $listener,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Create fake event (for testing)
     *
     * @return FakeEvent
     */
    public static function fake(): FakeEvent
    {
        return new FakeEvent();
    }
}

/**
 * Fake Event for Testing
 */
class FakeEvent
{
    private array $dispatched = [];

    public function listen($event, $listener): void
    {
        // Do nothing
    }

    public function fire($event, $payload = []): void
    {
        $this->dispatched[] = ['event' => $event, 'payload' => $payload];
    }

    public function dispatch($event, $payload = []): void
    {
        $this->fire($event, $payload);
    }

    public function assertDispatched(string $event): bool
    {
        foreach ($this->dispatched as $item) {
            if ($item['event'] === $event) {
                return true;
            }
        }
        return false;
    }

    public function assertNotDispatched(string $event): bool
    {
        return !$this->assertDispatched($event);
    }
}

/**
 * Base Event Class
 */
abstract class BaseEvent
{
    /**
     * Fire this event
     *
     * @return void
     */
    public function fire(): void
    {
        Event::fire($this);
    }

    /**
     * Queue this event
     *
     * @param string|null $queue
     * @return void
     */
    public function queue(?string $queue = null): void
    {
        Event::queue($this, [], $queue);
    }
}

/**
 * Event Subscriber Interface
 */
interface EventSubscriber
{
    /**
     * Get subscribed events
     *
     * @return array
     */
    public function subscribe(): array;
}

