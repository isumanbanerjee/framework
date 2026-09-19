<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Notification;
use Core\Model\SlackChannel;
use PHPUnit\Framework\TestCase;

final class DeploySlackNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return ['slack'];
    }

    public function toSlack(mixed $notifiable): array
    {
        return [
            'webhook_url' => 'https://hooks.slack.com/services/T000/B000/XXX',
            'text' => 'Deploy finished',
            'channel' => '#deploys',
        ];
    }
}

final class EmptySlackNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return ['slack'];
    }
}

final class NoWebhookSlackNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return ['slack'];
    }

    public function toSlack(mixed $notifiable): array
    {
        return ['text' => 'no webhook here'];
    }
}

final class SlackChannelTest extends TestCase
{
    public function testSendPostsMessageBodyToWebhookUrl(): void
    {
        $calls = [];
        $channel = new SlackChannel(function (string $url, array $body, array $headers) use (&$calls) {
            $calls[] = ['url' => $url, 'body' => $body, 'headers' => $headers];

            return null;
        });

        $channel->send(['id' => 1], new DeploySlackNotification());

        $this->assertCount(1, $calls);
        $this->assertSame('https://hooks.slack.com/services/T000/B000/XXX', $calls[0]['url']);
        $this->assertSame(['text' => 'Deploy finished', 'channel' => '#deploys'], $calls[0]['body']);
    }

    public function testSendSkipsWhenToSlackPayloadIsEmpty(): void
    {
        $calls = [];
        $channel = new SlackChannel(function (string $url, array $body, array $headers) use (&$calls) {
            $calls[] = $url;

            return null;
        });

        $channel->send('someone', new EmptySlackNotification());

        $this->assertCount(0, $calls);
    }

    public function testSendSkipsWhenWebhookUrlIsMissing(): void
    {
        $calls = [];
        $channel = new SlackChannel(function (string $url, array $body, array $headers) use (&$calls) {
            $calls[] = $url;

            return null;
        });

        $channel->send('someone', new NoWebhookSlackNotification());

        $this->assertCount(0, $calls);
    }

    public function testDefaultPosterIsHttpPost(): void
    {
        $channel = new SlackChannel();

        $reflection = new \ReflectionProperty($channel, 'poster');
        $reflection->setAccessible(true);
        $poster = $reflection->getValue($channel);

        $this->assertIsCallable($poster);
        $this->assertSame(['Core\\Model\\Http', 'post'], $poster);
    }
}
