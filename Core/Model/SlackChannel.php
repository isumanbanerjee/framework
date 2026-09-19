<?php

/**
 * Slack Notification Channel
 *
 * Delivers a notification to a Slack incoming webhook using the payload
 * returned by the notification's toSlack() method.
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

/**
 * SlackChannel Class
 *
 * Expects toSlack() to return an array shaped like:
 * ```php
 * [
 *     'webhook_url' => 'https://hooks.slack.com/services/...',
 *     'text'        => 'Something happened!',
 *     // any other keys (blocks, channel, username, icon_emoji, ...) are
 *     // forwarded verbatim as part of the Slack message JSON body.
 * ]
 * ```
 *
 * An empty toSlack() payload, or one missing `webhook_url`, sends nothing.
 * The HTTP POST is delegated to an injectable poster callable so tests can
 * avoid making real network requests; it defaults to Http::post().
 *
 * @category  Notifications
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
class SlackChannel implements NotificationChannel
{
    /**
     * @var callable(string,array<string,mixed>,array<string,string>):HttpResponse
     */
    private $poster;

    /**
     * @param callable|null $poster Callable(url, jsonBody, headers): HttpResponse.
     *                              Defaults to [Http::class, 'post'].
     */
    public function __construct(?callable $poster = null)
    {
        $this->poster = $poster ?? [Http::class, 'post'];
    }

    /**
     * Deliver the notification's Slack representation.
     *
     * @param mixed        $notifiable   The entity being notified.
     * @param Notification $notification The notification to deliver.
     *
     * @return void
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        $payload = $notification->toSlack($notifiable);

        if (!isset($payload['webhook_url']) || !is_string($payload['webhook_url']) || $payload['webhook_url'] === '') {
            return;
        }

        $webhookUrl = $payload['webhook_url'];
        unset($payload['webhook_url']);

        ($this->poster)($webhookUrl, $payload, []);
    }
}
