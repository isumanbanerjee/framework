<?php

/**
 * Database Notification Channel
 *
 * Persists notifications to a "notifications" table.
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

use PDO;

/**
 * DatabaseChannel Class
 *
 * Stores the notification's toArray() payload as JSON alongside the notifiable
 * identifier and notification type.
 *
 * @category  Notifications
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
class DatabaseChannel implements NotificationChannel
{
    private PDO $pdo;
    private string $table;

    /**
     * @param PDO    $pdo   Database connection.
     * @param string $table Table to persist notifications into.
     */
    public function __construct(PDO $pdo, string $table = 'notifications')
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * Persist the notification payload.
     *
     * @param mixed        $notifiable   The entity being notified.
     * @param Notification $notification The notification to deliver.
     *
     * @return void
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO {$this->table} (notifiable, type, data) VALUES (?, ?, ?)"
        );

        $statement->execute([
            $this->notifiableId($notifiable),
            get_class($notification),
            json_encode($notification->toArray($notifiable)),
        ]);
    }

    /**
     * Derive a scalar identifier for the notifiable.
     *
     * @param mixed $notifiable The entity being notified.
     *
     * @return string
     */
    private function notifiableId(mixed $notifiable): string
    {
        if (is_array($notifiable) && isset($notifiable['id'])) {
            return (string) $notifiable['id'];
        }

        if (is_object($notifiable) && isset($notifiable->id)) {
            return (string) $notifiable->id;
        }

        if (is_scalar($notifiable)) {
            return (string) $notifiable;
        }

        return '';
    }
}
