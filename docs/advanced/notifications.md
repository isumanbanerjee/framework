# Notifications

Notifications are small classes describing *what* happened and which channels should deliver it; `Notifier` routes each notification to the channels it declares.

## Defining a notification

```php
use Core\Model\Notification;

class WelcomeNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return ['mail', 'slack', 'database'];
    }

    public function toArray(mixed $notifiable): array
    {
        return ['message' => 'Welcome aboard!'];
    }

    public function toMail(mixed $notifiable): array
    {
        return [
            'subject' => 'Welcome!',
            'body' => '<p>Welcome aboard.</p>',
            'options' => ['html' => true],
        ];
    }

    public function toSlack(mixed $notifiable): array
    {
        return [
            'webhook_url' => 'https://hooks.slack.com/services/...',
            'text' => 'A new user just signed up!',
        ];
    }
}
```

`via()` is the only required method. `toArray()`, `toMail()`, and `toSlack()` all default to returning `[]`, so a notification only needs to implement the representations its declared channels actually use.

## Registering channels and sending

```php
use Core\Model\Notifier;
use Core\Model\MailChannel;
use Core\Model\SlackChannel;
use Core\Model\DatabaseChannel;
use Core\Model\Mail;

$notifier = new Notifier();
$notifier->extend('mail', new MailChannel(new Mail()));
$notifier->extend('slack', new SlackChannel());
$notifier->extend('database', new DatabaseChannel($pdo));

$notifier->send($user, new WelcomeNotification());
```

`Notifier::send()` throws `InvalidArgumentException` if a notification's `via()` list names a channel that hasn't been registered.

## Built-in channels

| Channel | Class | Reads | Notes |
|---|---|---|---|
| Array | `ArrayChannel` | `toArray()` | In-memory collector, useful for tests and request-scoped aggregation |
| Database | `DatabaseChannel` | `toArray()` | Persists to a `notifications` table via PDO |
| Mail | `MailChannel` | `toMail()` | Wraps a `Core\Model\Mail` instance |
| Slack | `SlackChannel` | `toSlack()` | POSTs to a Slack incoming webhook |

### MailChannel

`MailChannel` is constructed with a `Mail` instance and forwards the `toMail()` payload to `Mail::send()`:

```php
[
    'to'      => 'user@example.com', // optional — falls back to $notifiable['email'] / $notifiable->email / a string $notifiable
    'subject' => 'Welcome!',
    'body'    => '<p>Welcome aboard.</p>',
    'options' => ['html' => true],   // optional, forwarded as Mail::send()'s $options
]
```

An empty `toMail()` payload, or a notifiable with no resolvable recipient address, sends nothing — silently, since not every notification needs every channel.

### SlackChannel

`SlackChannel` reads `toSlack()` and expects a `webhook_url` key; every other key is forwarded verbatim as the Slack message's JSON body (`text`, `blocks`, `channel`, `username`, `icon_emoji`, ...):

```php
[
    'webhook_url' => 'https://hooks.slack.com/services/...',
    'text' => 'Deploy finished',
    'channel' => '#deploys',
]
```

An empty `toSlack()` payload, or one missing `webhook_url`, sends nothing. The HTTP POST is delegated to an injectable `callable(string $url, array $body, array $headers): HttpResponse`, defaulting to `Core\Model\Http::post(...)`; pass your own callable to `new SlackChannel($poster)` in tests to avoid real network calls.

## Testing notifications

`ArrayChannel` is the easiest way to assert a notification was sent without hitting mail/Slack/the database:

```php
$channel = new ArrayChannel();
$notifier = new Notifier();
$notifier->extend('array', $channel);

$notifier->send($user, new WelcomeNotification());

$this->assertSame(1, $channel->count());
```

For `MailChannel`/`SlackChannel` specifically, inject a fake `Mail` subclass (override `send()`, skip `parent::__construct()`) or a fake poster callable — see `Tests/Unit/MailChannelTest.php` and `Tests/Unit/SlackChannelTest.php` for the pattern.
