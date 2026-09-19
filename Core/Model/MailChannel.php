<?php

/**
 * Mail Notification Channel
 *
 * Delivers a notification via Core\Model\Mail using the payload returned by
 * the notification's toMail() method.
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
 * MailChannel Class
 *
 * Expects toMail() to return an array shaped like:
 * ```php
 * [
 *     'to'      => 'user@example.com', // optional, falls back to the notifiable's email
 *     'subject' => 'Welcome!',
 *     'body'    => '<p>Welcome aboard.</p>',
 *     'options' => ['html' => true], // optional, forwarded to Mail::send()
 * ]
 * ```
 *
 * An empty toMail() payload (the base Notification default) sends nothing.
 *
 * @category  Notifications
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
class MailChannel implements NotificationChannel
{
    private Mail $mailer;

    /**
     * @param Mail $mailer Mail sender used to deliver the message.
     */
    public function __construct(Mail $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Deliver the notification's mail representation.
     *
     * @param mixed        $notifiable   The entity being notified.
     * @param Notification $notification The notification to deliver.
     *
     * @return void
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        $payload = $notification->toMail($notifiable);

        if ($payload === []) {
            return;
        }

        $to = $payload['to'] ?? $this->resolveRecipient($notifiable);

        if ($to === null) {
            return;
        }

        $this->mailer->send(
            $to,
            (string) ($payload['subject'] ?? ''),
            (string) ($payload['body'] ?? ''),
            (array) ($payload['options'] ?? [])
        );
    }

    /**
     * Derive an email address for the notifiable when toMail() doesn't specify one.
     *
     * @param mixed $notifiable The entity being notified.
     *
     * @return string|null
     */
    private function resolveRecipient(mixed $notifiable): ?string
    {
        if (is_array($notifiable) && isset($notifiable['email'])) {
            return (string) $notifiable['email'];
        }

        if (is_object($notifiable) && isset($notifiable->email)) {
            return (string) $notifiable->email;
        }

        if (is_string($notifiable)) {
            return $notifiable;
        }

        return null;
    }
}
