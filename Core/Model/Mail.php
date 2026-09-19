<?php

namespace Core\Model;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Enterprise Mail System
 *
 * Email sending with SMTP, templating, attachments, and queue integration.
 *
 * Features:
 * - SMTP/Sendmail support
 * - HTML and plain text emails
 * - Attachments and inline images
 * - CC, BCC, Reply-To
 * - Template integration
 * - Queue integration for async sending
 * - Email tracking
 * - Bulk sending
 *
 * @package Core\Model
 * @version 1.0.0
 * @since 2025-12-24
 */
class Mail
{
    /**
     * PHPMailer instance
     *
     * @var PHPMailer
     */
    private PHPMailer $mailer;

    /**
     * Template engine
     *
     * @var Template|null
     */
    private ?Template $template = null;

    /**
     * Queue instance
     *
     * @var Queue|null
     */
    private ?Queue $queue = null;

    /**
     * From address
     *
     * @var array
     */
    private array $from = [];

    /**
     * Initialize mail system
     *
     * @param array $config SMTP configuration
     */
    public function __construct(array $config = [])
    {
        try {
            $this->mailer = new PHPMailer(true);
            $this->configureSMTP($config);
            $this->template = new Template('views/emails');
        } catch (Exception $e) {
            if (defined('TEST_ENV') || php_sapi_name() === 'cli') {
                throw $e;
            }

            $error = new Error();
            $error->terminateWithError(
                'MAIL_INITIALIZATION_FAILED',
                'Mail initialization failed: ' . $e->getMessage(),
                Error::SEVERITY_ERROR
            );
        }
    }

    /**
     * Configure SMTP
     *
     * @param array $config
     */
    private function configureSMTP(array $config): void
    {
        $driver = $config['driver'] ?? App::config('MAIL_DRIVER', 'smtp');

        if ($driver === 'smtp') {
            $this->mailer->isSMTP();
            $this->mailer->Host = $config['host'] ?? App::config('MAIL_HOST', 'localhost');
            $this->mailer->Port = $config['port'] ?? App::config('MAIL_PORT', 587);
            $this->mailer->SMTPAuth = $config['auth'] ?? App::config('MAIL_AUTH', true);
            $this->mailer->Username = $config['username'] ?? App::config('MAIL_USERNAME', '');
            $this->mailer->Password = $config['password'] ?? App::config('MAIL_PASSWORD', '');
            $this->mailer->SMTPSecure = $config['encryption'] ?? App::config('MAIL_ENCRYPTION', PHPMailer::ENCRYPTION_STARTTLS);
        }

        $this->from = [
            'address' => $config['from_address'] ?? App::config('MAIL_FROM_ADDRESS', 'noreply@example.com'),
            'name' => $config['from_name'] ?? App::config('MAIL_FROM_NAME', 'Application'),
        ];

        $this->mailer->setFrom($this->from['address'], $this->from['name']);
    }

    /**
     * Send email
     *
     * @param string|array $to Recipient email(s)
     * @param string $subject Email subject
     * @param string $body Email body (HTML or plain text)
     * @param array $options Additional options
     * @return bool
     */
    public function send($to, string $subject, string $body, array $options = []): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearCCs();
            $this->mailer->clearBCCs();

            // Add recipients
            if (is_array($to)) {
                foreach ($to as $email => $name) {
                    if (is_numeric($email)) {
                        $this->mailer->addAddress($name);
                    } else {
                        $this->mailer->addAddress($email, $name);
                    }
                }
            } else {
                $this->mailer->addAddress($to);
            }

            // Set subject and body
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML($options['html'] ?? true);
            $this->mailer->Body = $body;

            // Plain text alternative
            if (isset($options['text'])) {
                $this->mailer->AltBody = $options['text'];
            }

            // CC
            if (isset($options['cc'])) {
                foreach ((array) $options['cc'] as $email) {
                    $this->mailer->addCC($email);
                }
            }

            // BCC
            if (isset($options['bcc'])) {
                foreach ((array) $options['bcc'] as $email) {
                    $this->mailer->addBCC($email);
                }
            }

            // Reply-To
            if (isset($options['reply_to'])) {
                $this->mailer->addReplyTo($options['reply_to']);
            }

            // Attachments
            if (isset($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    $this->mailer->addAttachment($attachment);
                }
            }

            return $this->mailer->send();
        } catch (Exception $e) {
            $logger = new Logger('logs/mail.log');
            $logger->logError('Email send failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send email using template
     *
     * @param string|array $to
     * @param string $subject
     * @param string $template Template name
     * @param array $data Template data
     * @param array $options
     * @return bool
     */
    public function sendTemplate($to, string $subject, string $template, array $data = [], array $options = []): bool
    {
        $body = $this->template->render($template, $data);
        return $this->send($to, $subject, $body, $options);
    }

    /**
     * Queue email for async sending
     *
     * @param string|array $to
     * @param string $subject
     * @param string $body
     * @param array $options
     * @param string|null $queue
     * @return void
     */
    public function queue($to, string $subject, string $body, array $options = [], ?string $queue = null): void
    {
        if ($this->queue === null) {
            $this->queue = new Queue();
        }

        $this->queue->push(function () use ($to, $subject, $body, $options) {
            $this->send($to, $subject, $body, $options);
        }, [], $queue);
    }

    /**
     * Queue template email
     *
     * @param string|array $to
     * @param string $subject
     * @param string $template
     * @param array $data
     * @param array $options
     * @param string|null $queue
     * @return void
     */
    public function queueTemplate($to, string $subject, string $template, array $data = [], array $options = [], ?string $queue = null): void
    {
        if ($this->queue === null) {
            $this->queue = new Queue();
        }

        $this->queue->push(function () use ($to, $subject, $template, $data, $options) {
            $this->sendTemplate($to, $subject, $template, $data, $options);
        }, [], $queue);
    }

    /**
     * Send bulk emails
     *
     * @param array $recipients Array of [email, subject, body, options]
     * @return array Results
     */
    public function sendBulk(array $recipients): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            $to = $recipient['to'] ?? $recipient[0];
            $subject = $recipient['subject'] ?? $recipient[1];
            $body = $recipient['body'] ?? $recipient[2];
            $options = $recipient['options'] ?? $recipient[3] ?? [];

            $results[$to] = $this->send($to, $subject, $body, $options);
        }

        return $results;
    }

    /**
     * Verify email address format
     *
     * @param string $email
     * @return bool
     */
    public static function isValid(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Test SMTP connection
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            $this->mailer->smtpConnect();
            $this->mailer->smtpClose();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
