<?php

/**
 * Array Notification Channel
 *
 * Collects notifications in memory. Useful for testing and for aggregating
 * notifications within a single request.
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
 * ArrayChannel Class
 *
 * @category  Notifications
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
class ArrayChannel implements NotificationChannel
{
    /**
     * Collected notifications.
     *
     * @var array<int,array{notifiable:mixed,notification:Notification,data:array<string,mixed>}>
     */
    private array $sent = [];

    /**
     * Record the notification.
     *
     * @param mixed        $notifiable   The entity being notified.
     * @param Notification $notification The notification to deliver.
     *
     * @return void
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        $this->sent[] = [
            'notifiable' => $notifiable,
            'notification' => $notification,
            'data' => $notification->toArray($notifiable),
        ];
    }

    /**
     * All notifications collected so far.
     *
     * @return array<int,array{notifiable:mixed,notification:Notification,data:array<string,mixed>}>
     */
    public function sent(): array
    {
        return $this->sent;
    }

    /**
     * Number of notifications collected.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->sent);
    }
}
