<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Mail;
use Core\Model\MailChannel;
use Core\Model\Notification;
use PHPUnit\Framework\TestCase;

final class RecordingMail extends Mail
{
    /** @var array<int,array{to:mixed,subject:string,body:string,options:array}> */
    public array $calls = [];

    public function __construct()
    {
    }

    public function send($to, string $subject, string $body, array $options = []): bool
    {
        $this->calls[] = ['to' => $to, 'subject' => $subject, 'body' => $body, 'options' => $options];

        return true;
    }
}

final class WelcomeMailNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): array
    {
        return [
            'subject' => 'Welcome!',
            'body' => 'Welcome aboard.',
            'options' => ['html' => false],
        ];
    }
}

final class ExplicitRecipientMailNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): array
    {
        return [
            'to' => 'explicit@example.com',
            'subject' => 'Hi',
            'body' => 'Body',
        ];
    }
}

final class EmptyMailNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }
}

final class MailChannelTest extends TestCase
{
    public function testSendResolvesRecipientEmailFromArrayNotifiable(): void
    {
        $mailer = new RecordingMail();
        $channel = new MailChannel($mailer);

        $channel->send(['id' => 1, 'email' => 'user@example.com'], new WelcomeMailNotification());

        $this->assertCount(1, $mailer->calls);
        $this->assertSame('user@example.com', $mailer->calls[0]['to']);
        $this->assertSame('Welcome!', $mailer->calls[0]['subject']);
        $this->assertSame('Welcome aboard.', $mailer->calls[0]['body']);
        $this->assertSame(['html' => false], $mailer->calls[0]['options']);
    }

    public function testSendResolvesRecipientEmailFromObjectNotifiable(): void
    {
        $mailer = new RecordingMail();
        $channel = new MailChannel($mailer);

        $notifiable = new class () {
            public string $email = 'object@example.com';
        };

        $channel->send($notifiable, new WelcomeMailNotification());

        $this->assertSame('object@example.com', $mailer->calls[0]['to']);
    }

    public function testSendUsesExplicitToOverNotifiableEmail(): void
    {
        $mailer = new RecordingMail();
        $channel = new MailChannel($mailer);

        $channel->send(['email' => 'ignored@example.com'], new ExplicitRecipientMailNotification());

        $this->assertSame('explicit@example.com', $mailer->calls[0]['to']);
    }

    public function testSendSkipsWhenNoRecipientCanBeResolved(): void
    {
        $mailer = new RecordingMail();
        $channel = new MailChannel($mailer);

        $channel->send(['id' => 1], new WelcomeMailNotification());

        $this->assertCount(0, $mailer->calls);
    }

    public function testSendSkipsWhenToMailPayloadIsEmpty(): void
    {
        $mailer = new RecordingMail();
        $channel = new MailChannel($mailer);

        $channel->send('user@example.com', new EmptyMailNotification());

        $this->assertCount(0, $mailer->calls);
    }

    public function testSendAcceptsStringNotifiableAsRecipient(): void
    {
        $mailer = new RecordingMail();
        $channel = new MailChannel($mailer);

        $channel->send('user@example.com', new WelcomeMailNotification());

        $this->assertSame('user@example.com', $mailer->calls[0]['to']);
    }
}
