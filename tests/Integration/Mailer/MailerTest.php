<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Mailer;

use Awf\Application\Configuration;
use Awf\Container\Container;
use Awf\Mailer\Mailer;
use Awf\Tests\Integration\AbstractIntegrationTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * End-to-end integration tests for the AWF Mailer wrapper.
 *
 * All tests that actually send mail are SKIPPED unless the SMTP catch-all
 * environment variables are set.  The recommended local setup is Mailpit or
 * Mailhog, both of which accept any SMTP traffic and expose an HTTP API for
 * inspecting received messages.
 *
 * Required environment variables (sending tests):
 *   AWF_TEST_SMTP_HOST      SMTP server hostname (e.g. "127.0.0.1")
 *   AWF_TEST_SMTP_PORT      SMTP server port     (default: 1025 for Mailpit/Mailhog)
 *   AWF_TEST_SMTP_FROM      Sender address       (e.g. "sender@example.com")
 *   AWF_TEST_SMTP_TO        Recipient address    (e.g. "catch-all@example.com")
 *
 * Optional environment variables:
 *   AWF_TEST_SMTP_USER      SMTP username (leave empty for unauthenticated relay)
 *   AWF_TEST_SMTP_PASS      SMTP password
 *   AWF_TEST_SMTP_SECURE    Encryption: "" | "tls" | "ssl"  (default: "")
 *   AWF_TEST_SMTP_API_URL   Base URL of the catcher's HTTP API for result
 *                           verification (e.g. "http://127.0.0.1:8025").
 *                           When present, tests will query the API to confirm
 *                           delivery.  When absent, only a successful send
 *                           return value is asserted.
 */
#[CoversClass(Mailer::class)]
#[Group('integration')]
final class MailerTest extends AbstractIntegrationTestCase
{
    // -------------------------------------------------------------------------
    // Environment variable names
    // -------------------------------------------------------------------------

    private const ENV_SMTP_HOST    = 'AWF_TEST_SMTP_HOST';
    private const ENV_SMTP_PORT    = 'AWF_TEST_SMTP_PORT';
    private const ENV_SMTP_FROM    = 'AWF_TEST_SMTP_FROM';
    private const ENV_SMTP_TO      = 'AWF_TEST_SMTP_TO';
    private const ENV_SMTP_USER    = 'AWF_TEST_SMTP_USER';
    private const ENV_SMTP_PASS    = 'AWF_TEST_SMTP_PASS';
    private const ENV_SMTP_SECURE  = 'AWF_TEST_SMTP_SECURE';
    private const ENV_SMTP_API_URL = 'AWF_TEST_SMTP_API_URL';

    // -------------------------------------------------------------------------
    // Shared state
    // -------------------------------------------------------------------------

    /** Unique run ID embedded in every message subject for tracking. */
    private string $runId;

    protected function setUp(): void
    {
        $this->runId = 'awf_mail_' . bin2hex(random_bytes(6));
    }

    // =========================================================================
    // Pure configuration / builder surface tests (no real SMTP needed)
    // =========================================================================

    /**
     * Mailer::setSubject() stores the subject on $this->Subject.
     */
    public function testSetSubjectStoresValue(): void
    {
        $mailer = $this->buildMailer();

        $result = $mailer->setSubject('Hello World');

        // Fluent interface returns the same instance
        $this->assertSame($mailer, $result, 'setSubject() must return $this for chaining.');
        $this->assertSame('Hello World', $mailer->Subject);
    }

    /**
     * Mailer::setBody() stores the body on $this->Body.
     */
    public function testSetBodyStoresValue(): void
    {
        $mailer = $this->buildMailer();

        $result = $mailer->setBody('<p>Hello</p>');

        $this->assertSame($mailer, $result, 'setBody() must return $this for chaining.');
        $this->assertSame('<p>Hello</p>', $mailer->Body);
    }

    /**
     * Mailer::setSender() accepts a string (address only).
     */
    public function testSetSenderWithString(): void
    {
        $mailer = $this->buildMailer();

        $result = $mailer->setSender('from@example.com');

        $this->assertSame($mailer, $result);
        $this->assertSame('from@example.com', $mailer->From);
    }

    /**
     * Mailer::setSender() accepts a two-element array [address, name].
     */
    public function testSetSenderWithArray(): void
    {
        $mailer = $this->buildMailer();

        $mailer->setSender(['from@example.com', 'Sender Name']);

        $this->assertSame('from@example.com', $mailer->From);
        $this->assertSame('Sender Name', $mailer->FromName);
    }

    /**
     * Mailer::setSender() accepts a three-element array [address, name, autoReplyTo].
     */
    public function testSetSenderWithThreeElementArray(): void
    {
        $mailer = $this->buildMailer();

        $mailer->setSender(['reply@example.com', 'Reply Name', false]);

        $this->assertSame('reply@example.com', $mailer->From);
        $this->assertSame('Reply Name', $mailer->FromName);
    }

    /**
     * Mailer::setSender() throws when given an invalid value.
     */
    public function testSetSenderThrowsForInvalidValue(): void
    {
        $mailer = $this->buildMailer();

        $this->expectException(\UnexpectedValueException::class);

        $mailer->setSender(42);
    }

    /**
     * Mailer::addRecipient() registers a To address.
     */
    public function testAddRecipientSingleAddress(): void
    {
        $mailer = $this->buildMailer();

        $result = $mailer->addRecipient('to@example.com', 'Recipient');

        $this->assertSame($mailer, $result);
        $this->assertNotEmpty($mailer->getToAddresses());
    }

    /**
     * Mailer::addRecipient() accepts an array of addresses.
     */
    public function testAddRecipientArrayOfAddresses(): void
    {
        $mailer = $this->buildMailer();

        $mailer->addRecipient(['a@example.com', 'b@example.com']);

        $this->assertCount(2, $mailer->getToAddresses());
    }

    /**
     * Mailer::addCC() registers a CC address.
     */
    public function testAddCCSingleAddress(): void
    {
        $mailer = $this->buildMailer();

        $result = $mailer->addCC('cc@example.com', 'CC Recipient');

        $this->assertSame($mailer, $result);
        $this->assertNotEmpty($mailer->getCcAddresses());
    }

    /**
     * Mailer::addBCC() registers a BCC address.
     */
    public function testAddBCCSingleAddress(): void
    {
        $mailer = $this->buildMailer();

        $result = $mailer->addBCC('bcc@example.com', 'BCC Recipient');

        $this->assertSame($mailer, $result);
        $this->assertNotEmpty($mailer->getBccAddresses());
    }

    /**
     * Mailer::addReplyTo() registers a Reply-To address.
     */
    public function testAddReplyToSingleAddress(): void
    {
        $mailer = $this->buildMailer();

        $result = $mailer->addReplyTo('reply@example.com', 'Reply Name');

        $this->assertSame($mailer, $result);
        $this->assertNotEmpty($mailer->getReplyToAddresses());
    }

    /**
     * Mailer::addAttachment() throws when the attachment count differs from the
     * name count.
     */
    public function testAddAttachmentThrowsOnCountMismatch(): void
    {
        $mailer = $this->buildMailer();

        $this->expectException(\InvalidArgumentException::class);

        // Two files but three names
        $mailer->addAttachment(
            ['/tmp/a.txt', '/tmp/b.txt'],
            ['Name A', 'Name B', 'Name C']
        );
    }

    /**
     * Mailer::isHtml() returns the same instance (fluent interface).
     */
    public function testIsHtmlReturnsSelf(): void
    {
        $mailer = $this->buildMailer();

        $result = $mailer->isHtml(true);

        $this->assertSame($mailer, $result);
        $this->assertTrue($mailer->ContentType === 'text/html' || $mailer->isHTML());
    }

    /**
     * Mailer::useSMTP() returns true when all SMTP parameters are set.
     */
    public function testUseSMTPReturnsTrueWhenParamsComplete(): void
    {
        $mailer = $this->buildMailer();

        $result = $mailer->useSMTP(
            true,
            'smtp.example.com',
            'user@example.com',
            'secret',
            null,
            587
        );

        $this->assertTrue($result, 'useSMTP() must return true when all params are provided.');
        $this->assertSame('smtp', $mailer->Mailer);
    }

    /**
     * Mailer::useSMTP() falls back to mail() when no host is given.
     */
    public function testUseSMTPReturnsFalseWhenHostMissing(): void
    {
        $mailer = $this->buildMailer();

        $result = $mailer->useSMTP(null, null);

        $this->assertFalse($result, 'useSMTP() must return false when host is null.');
    }

    /**
     * Mailer::useSendmail() returns true when a sendmail path is set, false otherwise.
     */
    public function testUseSendmail(): void
    {
        $mailer = $this->buildMailer();

        $this->assertFalse($mailer->useSendmail(null));
        $this->assertTrue($mailer->useSendmail('/usr/sbin/sendmail'));
    }

    /**
     * CharSet defaults to utf-8.
     */
    public function testDefaultCharSet(): void
    {
        $mailer = $this->buildMailer();

        $this->assertSame('utf-8', $mailer->CharSet);
    }

    /**
     * Mailer::add() with mismatched recipient/name arrays throws InvalidArgumentException.
     */
    public function testAddWithMismatchedArraysThrows(): void
    {
        $mailer = $this->buildMailer();

        $this->expectException(\InvalidArgumentException::class);

        // Two addresses, three names → array_combine fails
        $mailer->addRecipient(
            ['a@example.com', 'b@example.com'],
            ['Name A', 'Name B', 'Name C']
        );
    }

    // =========================================================================
    // Real SMTP sending tests (skipped when env vars absent)
    // =========================================================================

    /**
     * Send a plain-text email via the configured SMTP catch-all.
     */
    public function testSendPlainTextEmail(): void
    {
        $config = $this->requireSmtpConfig();
        $mailer = $this->buildSmtpMailer($config);

        $subject = '[AWF Integration] Plain text – ' . $this->runId;

        $mailer
            ->setSubject($subject)
            ->setBody('This is a plain-text integration test message. Run ID: ' . $this->runId)
            ->addRecipient($config['to']);

        $result = $mailer->Send();

        $this->assertTrue($result, 'Mailer::Send() must return true on success.');

        $this->assertMessageDelivered($subject, $config);
    }

    /**
     * Send an HTML email with an AltBody fallback.
     */
    public function testSendHtmlEmail(): void
    {
        $config = $this->requireSmtpConfig();
        $mailer = $this->buildSmtpMailer($config);

        $subject = '[AWF Integration] HTML – ' . $this->runId;

        $mailer
            ->setSubject($subject)
            ->isHtml(true)
            ->setBody('<h1>AWF Integration Test</h1><p>Run ID: ' . $this->runId . '</p>')
            ->addRecipient($config['to']);

        $mailer->AltBody = 'AWF Integration Test. Run ID: ' . $this->runId;

        $result = $mailer->Send();

        $this->assertTrue($result, 'Mailer::Send() must return true for HTML message.');

        $this->assertMessageDelivered($subject, $config);
    }

    /**
     * sendMail() convenience method end-to-end.
     */
    public function testSendMailConvenienceMethod(): void
    {
        $config = $this->requireSmtpConfig();
        $mailer = $this->buildSmtpMailer($config);

        $subject = '[AWF Integration] sendMail() – ' . $this->runId;

        $result = $mailer->sendMail(
            $config['from'],
            'AWF Test Sender',
            $config['to'],
            $subject,
            'sendMail() convenience wrapper integration test. Run ID: ' . $this->runId,
            false
        );

        $this->assertTrue($result, 'Mailer::sendMail() must return true on success.');

        $this->assertMessageDelivered($subject, $config);
    }

    /**
     * Send an email with CC and BCC.
     */
    public function testSendEmailWithCcAndBcc(): void
    {
        $config = $this->requireSmtpConfig();
        $mailer = $this->buildSmtpMailer($config);

        $subject = '[AWF Integration] CC+BCC – ' . $this->runId;

        $mailer
            ->setSubject($subject)
            ->setBody('CC and BCC integration test. Run ID: ' . $this->runId)
            ->addRecipient($config['to'])
            ->addCC($config['to'])
            ->addBCC($config['to']);

        $result = $mailer->Send();

        $this->assertTrue($result);
    }

    /**
     * Send an email with a Reply-To address.
     */
    public function testSendEmailWithReplyTo(): void
    {
        $config = $this->requireSmtpConfig();
        $mailer = $this->buildSmtpMailer($config);

        $subject = '[AWF Integration] Reply-To – ' . $this->runId;

        $mailer
            ->setSubject($subject)
            ->setBody('Reply-To integration test. Run ID: ' . $this->runId)
            ->addRecipient($config['to'])
            ->addReplyTo('replyto@example.com', 'Reply-To Name');

        $result = $mailer->Send();

        $this->assertTrue($result);
    }

    /**
     * Send an email with a file attachment.
     */
    public function testSendEmailWithAttachment(): void
    {
        $config = $this->requireSmtpConfig();
        $mailer = $this->buildSmtpMailer($config);

        $subject = '[AWF Integration] Attachment – ' . $this->runId;

        // Create a temporary file to attach
        $tmpFile = tempnam(sys_get_temp_dir(), 'awf_mail_attachment_');
        file_put_contents($tmpFile, 'Attachment content for run ' . $this->runId);

        try {
            $mailer
                ->setSubject($subject)
                ->setBody('Attachment integration test. Run ID: ' . $this->runId)
                ->addRecipient($config['to'])
                ->addAttachment($tmpFile, 'test-attachment.txt');

            $result = $mailer->Send();

            $this->assertTrue($result, 'Mailer::Send() with attachment must return true.');
        } finally {
            @unlink($tmpFile);
        }
    }

    /**
     * Sending when mail.online = false must return false and NOT throw.
     */
    public function testSendWhenOfflineReturnsFalse(): void
    {
        // Build a container with mail.online = false
        $container = $this->buildContainerWithConfig([
            'mail.mailer'   => 'smtp',
            'mail.smtphost' => '127.0.0.1',
            'mail.smtpport' => 25,
            'mail.mailfrom' => 'from@example.com',
            'mail.fromname' => 'AWF Test',
            'mail.online'   => false,
        ]);

        $mailer = new Mailer($container);
        $mailer
            ->setSubject('Offline test')
            ->setBody('This should not be sent.')
            ->addRecipient('to@example.com');

        // Send() returns false when offline; it must NOT throw
        $result = $mailer->Send();

        $this->assertFalse($result, 'Mailer::Send() must return false when mail.online is false.');
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Build a Mailer configured with only the bare minimum (no real SMTP) to
     * exercise builder/configuration methods without sending.
     */
    private function buildMailer(): Mailer
    {
        $container = $this->buildContainerWithConfig([
            'mail.mailer'   => 'mail',
            'mail.mailfrom' => 'noreply@example.com',
            'mail.fromname' => 'AWF Test',
            'mail.online'   => true,
        ]);

        return new Mailer($container);
    }

    /**
     * Collect SMTP config from env vars; skip the test if vars are absent.
     *
     * @return array{host: string, port: int, from: string, to: string, user: string, pass: string, secure: string, apiUrl: string}
     */
    private function requireSmtpConfig(): array
    {
        $host = (string) getenv(self::ENV_SMTP_HOST);
        $from = (string) getenv(self::ENV_SMTP_FROM);
        $to   = (string) getenv(self::ENV_SMTP_TO);

        if ($host === '' || $from === '' || $to === '') {
            $this->markTestSkipped(
                sprintf(
                    'SMTP integration tests require %s, %s, and %s to be set.',
                    self::ENV_SMTP_HOST,
                    self::ENV_SMTP_FROM,
                    self::ENV_SMTP_TO
                )
            );
        }

        return [
            'host'   => $host,
            'port'   => (int) ((string) getenv(self::ENV_SMTP_PORT) ?: '1025'),
            'from'   => $from,
            'to'     => $to,
            'user'   => (string) getenv(self::ENV_SMTP_USER),
            'pass'   => (string) getenv(self::ENV_SMTP_PASS),
            'secure' => (string) getenv(self::ENV_SMTP_SECURE),
            'apiUrl' => rtrim((string) getenv(self::ENV_SMTP_API_URL), '/'),
        ];
    }

    /**
     * Build a Mailer configured to send through the catch-all SMTP server.
     *
     * @param array $config Result of requireSmtpConfig().
     */
    private function buildSmtpMailer(array $config): Mailer
    {
        $container = $this->buildContainerWithConfig([
            'mail.mailer'    => 'smtp',
            'mail.smtpauth'  => ($config['user'] !== '') ? 1 : 0,
            'mail.smtpuser'  => $config['user'],
            'mail.smtppass'  => $config['pass'],
            'mail.smtphost'  => $config['host'],
            'mail.smtpsecure'=> $config['secure'],
            'mail.smtpport'  => $config['port'],
            'mail.mailfrom'  => $config['from'],
            'mail.fromname'  => 'AWF Integration Tester',
            'mail.online'    => true,
        ]);

        return new Mailer($container);
    }

    /**
     * Build an AWF Container pre-seeded with the given configuration data.
     *
     * The Mailer constructor reads from `$container->appConfig`, which is an
     * AppConfiguration (a Registry subclass).  We build a minimal container
     * and then load flat key/value pairs into the configuration registry.
     */
    private function buildContainerWithConfig(array $data): Container
    {
        $container = new Container(
            [
                'application_name' => 'awf_test',
                'basePath'         => sys_get_temp_dir(),
                'filesystemBase'   => sys_get_temp_dir(),
                'temporaryPath'    => sys_get_temp_dir(),
            ]
        );

        // Populate appConfig with the supplied key/value pairs.
        $config = new Configuration($container);

        foreach ($data as $key => $value) {
            $config->set($key, $value);
        }

        // Override the lazy-loaded appConfig service with our pre-seeded instance.
        $container['appConfig'] = $config;

        return $container;
    }

    /**
     * If AWF_TEST_SMTP_API_URL is set, query the Mailpit/Mailhog HTTP API to
     * confirm that a message with the given subject was received recently.
     * Without the API URL, we simply skip this assertion step.
     *
     * Mailpit v1.x  — GET /api/v1/messages returns a JSON array of messages.
     * Mailhog       — GET /api/v2/messages  returns a similar structure.
     *
     * We try both endpoints and accept either.
     */
    private function assertMessageDelivered(string $subject, array $config): void
    {
        $apiUrl = $config['apiUrl'] ?? '';

        if ($apiUrl === '' || !function_exists('curl_init')) {
            // Cannot verify — treat the successful Send() return as sufficient.
            return;
        }

        // Small pause to let the catcher process the message
        usleep(500_000); // 0.5 s

        // Try Mailpit endpoint first, then Mailhog endpoint
        foreach (['/api/v1/messages', '/api/v2/messages'] as $endpoint) {
            $url      = $apiUrl . $endpoint;
            $ch       = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            ]);

            $body   = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status !== 200 || $body === false) {
                continue;
            }

            $data = json_decode((string) $body, true);

            // Mailpit wraps messages in {"messages": [...]}
            $messages = $data['messages'] ?? $data['items'] ?? $data;

            if (!is_array($messages)) {
                continue;
            }

            foreach ($messages as $msg) {
                $msgSubject = $msg['Subject'] ?? $msg['Content']['Headers']['Subject'][0] ?? '';

                if (str_contains($msgSubject, $this->runId)) {
                    // Found it — assertion passes.
                    $this->addToAssertionCount(1);
                    return;
                }
            }

            // Found a valid API response but no matching message.
            $this->fail(
                sprintf(
                    'Message with run ID "%s" (subject: "%s") was not found in the SMTP catcher API at %s.',
                    $this->runId,
                    $subject,
                    $url
                )
            );
        }

        // Neither endpoint worked — we can only trust the Send() return value.
    }
}
