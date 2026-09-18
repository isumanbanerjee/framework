<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\ArrayChannel;
use Core\Model\DatabaseChannel;
use Core\Model\Notification;
use Core\Model\Notifier;
use PDO;
use PHPUnit\Framework\TestCase;

class WelcomeNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return ['array', 'database'];
    }

    public function toArray(mixed $notifiable): array
    {
        return ['message' => 'Welcome aboard!'];
    }
}

class ArrayOnlyNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return ['array'];
    }

    public function toArray(mixed $notifiable): array
    {
        return ['ping' => true];
    }
}

final class NotificationTest extends TestCase
{
    public function testSendDeliversToArrayChannel(): void
    {
        $channel = new ArrayChannel();
        $notifier = (new Notifier())->extend('array', $channel);

        $notifier->send(['id' => 7], new ArrayOnlyNotification());

        $this->assertSame(1, $channel->count());
        $this->assertSame(['ping' => true], $channel->sent()[0]['data']);
        $this->assertSame(['id' => 7], $channel->sent()[0]['notifiable']);
    }

    public function testSendAcrossMultipleChannels(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE notifications (id INTEGER PRIMARY KEY AUTOINCREMENT, notifiable TEXT, type TEXT, data TEXT)');

        $arrayChannel = new ArrayChannel();
        $notifier = (new Notifier())
            ->extend('array', $arrayChannel)
            ->extend('database', new DatabaseChannel($pdo));

        $notifier->send((object) ['id' => 42], new WelcomeNotification());

        // array channel
        $this->assertSame(1, $arrayChannel->count());

        // database channel
        $row = $pdo->query('SELECT notifiable, type, data FROM notifications')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('42', $row['notifiable']);
        $this->assertStringContainsString('WelcomeNotification', $row['type']);
        $this->assertSame(['message' => 'Welcome aboard!'], json_decode($row['data'], true));
    }

    public function testUnregisteredChannelThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Notifier())->send(['id' => 1], new ArrayOnlyNotification());
    }

    public function testHasChannel(): void
    {
        $notifier = (new Notifier())->extend('array', new ArrayChannel());

        $this->assertTrue($notifier->hasChannel('array'));
        $this->assertFalse($notifier->hasChannel('sms'));
    }

    public function testDatabaseChannelUsesArrayNotifiableId(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE notifications (id INTEGER PRIMARY KEY AUTOINCREMENT, notifiable TEXT, type TEXT, data TEXT)');

        (new DatabaseChannel($pdo))->send(['id' => 'user-9'], new ArrayOnlyNotification());

        $this->assertSame('user-9', $pdo->query('SELECT notifiable FROM notifications')->fetchColumn());
    }
}
