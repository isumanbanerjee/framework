<?php

/**
 * Notification Base Class
 *
 * Extend to define a notification and the channels it should be delivered on.
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
 * Notification Class
 *
 * Example:
 * ```php
 * class WelcomeNotification extends Notification
 * {
 *     public function via($notifiable): array
 *     {
 *         return ['mail', 'database'];
 *     }
 *
 *     public function toArray($notifiable): array
 *     {
 *         return ['message' => 'Welcome aboard!'];
 *     }
 * }
 * ```
 *
 * @category  Notifications
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
abstract class Notification
{
    /**
     * Channels this notification should be delivered on.
     *
     * @param mixed $notifiable The entity being notified.
     *
     * @return array<int,string>
     */
    abstract public function via(mixed $notifiable): array;

    /**
     * Array representation (used by the database/array channels).
     *
     * @param mixed $notifiable The entity being notified.
     *
     * @return array<string,mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [];
    }

    /**
     * Mail representation (subject/body used by the mail channel).
     *
     * @param mixed $notifiable The entity being notified.
     *
     * @return array<string,mixed>
     */
    public function toMail(mixed $notifiable): array
    {
        return [];
    }
}
