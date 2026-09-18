<?php

/**
 * Notification Channel Interface
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
 * NotificationChannel Interface
 *
 * A delivery channel (mail, database, slack, ...) knows how to send a given
 * notification to a notifiable.
 *
 * @category  Notifications
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
interface NotificationChannel
{
    /**
     * Deliver the notification.
     *
     * @param mixed        $notifiable   The entity being notified.
     * @param Notification $notification The notification to deliver.
     *
     * @return void
     */
    public function send(mixed $notifiable, Notification $notification): void;
}
