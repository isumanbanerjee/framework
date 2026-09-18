<?php

/**
 * Notification Dispatcher
 *
 * Routes a notification to each of its declared channels.
 *
 * PHP version 8.1
 *
 * @category  Notifications
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */

declare(strict_types=1);

namespace Core\Model;

use InvalidArgumentException;

/**
 * Notifier Class
 *
 * Channels are registered by name and resolved from a notification's via()
 * list at send time.
 *
 * Example:
 * ```php
 * $notifier = new Notifier();
 * $notifier->extend('database', new DatabaseChannel($pdo));
 * $notifier->send($user, new WelcomeNotification());
 * ```
 *
 * @category  Notifications
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
class Notifier
{
    /**
     * Registered channels keyed by name.
     *
     * @var array<string,NotificationChannel>
     */
    private array $channels = [];

    /**
     * Register a channel implementation.
     *
     * @param string              $name    Channel name (matches via() entries).
     * @param NotificationChannel $channel Channel implementation.
     *
     * @return self
     */
    public function extend(string $name, NotificationChannel $channel): self
    {
        $this->channels[$name] = $channel;

        return $this;
    }

    /**
     * Whether a channel is registered.
     *
     * @param string $name Channel name.
     *
     * @return bool
     */
    public function hasChannel(string $name): bool
    {
        return isset($this->channels[$name]);
    }

    /**
     * Send a notification across all of its declared channels.
     *
     * @param mixed        $notifiable   The entity being notified.
     * @param Notification $notification The notification to deliver.
     *
     * @return void
     *
     * @throws InvalidArgumentException When a declared channel is not registered.
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        foreach ($notification->via($notifiable) as $channelName) {
            if (!isset($this->channels[$channelName])) {
                throw new InvalidArgumentException("Notification channel not registered: {$channelName}");
            }

            $this->channels[$channelName]->send($notifiable, $notification);
        }
    }
}
