<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Mail;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpConnection;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpConnectionFactory;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpMessage;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpSendFailurePolicy;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpTransportException;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpWithdrawMailer;

final class SmtpWithdrawMailerTest extends TestCase
{
    public function testSendsMessageThroughExpectedSmtpConversation(): void
    {
        $connection = new ScriptedSmtpConnection([
            '220 mailhog ready',
            '250-mailhog',
            '250 PIPELINING',
            '250 sender ok',
            '250 recipient ok',
            '354 end data with <CR><LF>.<CR><LF>',
            '250 queued',
            '221 bye',
        ]);
        $logger = new SpyStructuredLogger();
        $mailer = new SmtpWithdrawMailer(
            $this->mailConfig(),
            new ScriptedSmtpConnectionFactory($connection),
            $logger,
            new SmtpSendFailurePolicy($logger),
        );

        $mailer->send(new SmtpMessage(
            recipient: 'customer@example.test',
            subject: 'Withdraw processed',
            body: "Line 1\n.Line 2",
        ));

        self::assertSame([
            "EHLO pix-withdrawal.local\r\n",
            "MAIL FROM:<no-reply@example.test>\r\n",
            "RCPT TO:<customer@example.test>\r\n",
            "DATA\r\n",
            "From: PIX Withdrawal <no-reply@example.test>\r\nTo: <customer@example.test>\r\nSubject: Withdraw processed\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\nLine 1\r\n..Line 2\r\n.\r\n",
            "QUIT\r\n",
        ], $connection->writes());
        self::assertTrue($connection->closed());
        self::assertSame(['mail.send.attempt', 'mail.send.succeeded'], $logger->infoMessages());
    }

    public function testNegotiatesTlsAndAuthenticatesWhenConfigured(): void
    {
        $connection = new ScriptedSmtpConnection([
            '220 smtp.example.test ready',
            '250-smtp.example.test',
            '250 STARTTLS',
            '220 begin tls negotiation',
            '250-smtp.example.test',
            '250 AUTH LOGIN',
            '334 VXNlcm5hbWU6',
            '334 UGFzc3dvcmQ6',
            '235 authenticated',
            '250 sender ok',
            '250 recipient ok',
            '354 end data',
            '250 queued',
            '221 bye',
        ]);
        $logger = new SpyStructuredLogger();
        $mailer = new SmtpWithdrawMailer(
            $this->mailConfig([
                'username' => 'mailer',
                'password' => 'secret',
                'encryption' => 'tls',
            ]),
            new ScriptedSmtpConnectionFactory($connection),
            $logger,
            new SmtpSendFailurePolicy($logger),
        );

        $mailer->send(new SmtpMessage(
            recipient: 'customer@example.test',
            subject: 'Withdraw processed',
            body: 'Your withdraw was processed.',
        ));

        self::assertTrue($connection->tlsEnabled());
        self::assertContains("STARTTLS\r\n", $connection->writes());
        self::assertContains("AUTH LOGIN\r\n", $connection->writes());
        self::assertContains(base64_encode('mailer') . "\r\n", $connection->writes());
        self::assertContains(base64_encode('secret') . "\r\n", $connection->writes());
    }

    public function testWrapsRetryableTransportFailureAsTransientInfrastructureException(): void
    {
        $connection = new ScriptedSmtpConnection([
            '220 mailhog ready',
            '250 mailhog',
            '250 sender ok',
            '450 mailbox temporarily unavailable',
        ]);
        $logger = new SpyStructuredLogger();
        $mailer = new SmtpWithdrawMailer(
            $this->mailConfig(),
            new ScriptedSmtpConnectionFactory($connection),
            $logger,
            new SmtpSendFailurePolicy($logger),
        );

        $this->expectException(TransientInfrastructureException::class);
        $this->expectExceptionMessage('SMTP message delivery failed due to a transient transport error.');

        try {
            $mailer->send(new SmtpMessage(
                recipient: 'customer@example.test',
                subject: 'Withdraw processed',
                body: 'Your withdraw was processed.',
            ));
        } finally {
            self::assertSame(['mail.send.attempt'], $logger->infoMessages());
            self::assertSame(['mail.send.failed.retryable'], $logger->warningMessages());
            self::assertTrue($connection->closed());
        }
    }

    public function testPropagatesPermanentRecipientRejectionAfterLoggingFailure(): void
    {
        $connection = new ScriptedSmtpConnection([
            '220 mailhog ready',
            '250 mailhog',
            '250 sender ok',
            '550 mailbox unavailable',
        ]);
        $logger = new SpyStructuredLogger();
        $mailer = new SmtpWithdrawMailer(
            $this->mailConfig(),
            new ScriptedSmtpConnectionFactory($connection),
            $logger,
            new SmtpSendFailurePolicy($logger),
        );

        $this->expectException(SmtpTransportException::class);
        $this->expectExceptionMessage('SMTP command "RCPT TO" expected response code [250, 251], got: 550 mailbox unavailable');

        try {
            $mailer->send(new SmtpMessage(
                recipient: 'customer@example.test',
                subject: 'Withdraw processed',
                body: 'Your withdraw was processed.',
            ));
        } finally {
            self::assertSame(['mail.send.failed'], $logger->errorMessages());
            self::assertTrue($connection->closed());
        }
    }

    public function testFailsFastWhenMailerIsDisabled(): void
    {
        $logger = new SpyStructuredLogger();
        $mailer = new SmtpWithdrawMailer(
            MailConfig::fromArray([
                'default' => null,
                'mailers' => [],
            ]),
            new ScriptedSmtpConnectionFactory(new ScriptedSmtpConnection([])),
            $logger,
            new SmtpSendFailurePolicy($logger),
        );

        $this->expectException(SmtpTransportException::class);
        $this->expectExceptionMessage('SMTP mailer is disabled for the current runtime.');

        try {
            $mailer->send(new SmtpMessage(
                recipient: 'customer@example.test',
                subject: 'Withdraw processed',
                body: 'Your withdraw was processed.',
            ));
        } finally {
            self::assertSame(['mail.send.failed'], $logger->errorMessages());
        }
    }

    /**
     * @param array<string, mixed> $smtpOverrides
     */
    private function mailConfig(array $smtpOverrides = []): MailConfig
    {
        return MailConfig::fromArray([
            'default' => 'smtp',
            'mailers' => [
                'smtp' => array_replace_recursive([
                    'transport' => 'smtp',
                    'host' => 'mailhog',
                    'port' => 1025,
                    'username' => null,
                    'password' => null,
                    'encryption' => null,
                    'from' => [
                        'address' => 'no-reply@example.test',
                        'name' => 'PIX Withdrawal',
                    ],
                    'timeouts' => [
                        'connect_seconds' => 3,
                        'read_seconds' => 7,
                    ],
                ], $smtpOverrides),
            ],
        ]);
    }
}

final class SpyStructuredLogger implements StructuredLogger
{
    /** @var list<string> */
    private array $infoMessages = [];

    /** @var list<string> */
    private array $warningMessages = [];

    /** @var list<string> */
    private array $errorMessages = [];

    public function info(string $message, LogContext $context): void
    {
        $this->infoMessages[] = $message;
    }

    public function warning(string $message, LogContext $context): void
    {
        $this->warningMessages[] = $message;
    }

    public function error(string $message, LogContext $context): void
    {
        $this->errorMessages[] = $message;
    }

    /**
     * @return list<string>
     */
    public function infoMessages(): array
    {
        return $this->infoMessages;
    }

    /**
     * @return list<string>
     */
    public function warningMessages(): array
    {
        return $this->warningMessages;
    }

    /**
     * @return list<string>
     */
    public function errorMessages(): array
    {
        return $this->errorMessages;
    }
}

final class ScriptedSmtpConnectionFactory implements SmtpConnectionFactory
{
    public function __construct(private readonly ScriptedSmtpConnection $connection)
    {
    }

    public function connect(MailConfig $config): SmtpConnection
    {
        return $this->connection;
    }
}

final class ScriptedSmtpConnection implements SmtpConnection
{
    /**
     * @var list<string>
     */
    private array $writes = [];

    private bool $closed = false;

    private bool $tlsEnabled = false;

    /**
     * @param list<string|null> $responses
     */
    public function __construct(private array $responses)
    {
    }

    public function readLine(): ?string
    {
        return array_shift($this->responses);
    }

    public function write(string $payload): void
    {
        $this->writes[] = $payload;
    }

    public function enableTls(): void
    {
        $this->tlsEnabled = true;
    }

    public function didTimeOut(): bool
    {
        return false;
    }

    public function close(): void
    {
        $this->closed = true;
    }

    /**
     * @return list<string>
     */
    public function writes(): array
    {
        return $this->writes;
    }

    public function closed(): bool
    {
        return $this->closed;
    }

    public function tlsEnabled(): bool
    {
        return $this->tlsEnabled;
    }
}
