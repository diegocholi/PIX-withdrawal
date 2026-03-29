<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Mail;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Event\GenericDomainEvent;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpConnection;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpConnectionFactory;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpSendFailurePolicy;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpWithdrawMailer;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationDispatch;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationDispatcher;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationRenderer;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationSubjectTemplate;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationTemplate;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderPayloadNormalizer;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderSensitiveDataMasker;
use Tecnofit\PixWithdrawal\Providers\Serialization\SafeEventPayloadSerializer;

final class WithdrawNotificationDispatcherTest extends TestCase
{
    public function testDispatchCommandBuildsSmtpMessageFromNotificationArtifacts(): void
    {
        $connection = new RecordingNotificationSmtpConnection([
            '220 mailhog ready',
            '250 mailhog',
            '250 sender ok',
            '250 recipient ok',
            '354 end data',
            '250 queued',
            '221 bye',
        ]);
        $logger = new NotificationSpyStructuredLogger();
        $dispatcher = $this->dispatcher($connection, $logger);

        $dispatcher->dispatchCommand(new WithdrawNotificationDispatch(
            event: $this->processedEvent(),
            recipient: 'customer@example.test',
        ));

        self::assertSame([
            'notification.dispatch.started',
            'mail.send.attempt',
            'mail.send.succeeded',
            'notification.dispatch.sent',
        ], $logger->infoMessages());
        self::assertStringContainsString("RCPT TO:<customer@example.test>\r\n", implode('', $connection->writes()));
        self::assertStringContainsString("Subject: Saque PIX concluido\r\n", implode('', $connection->writes()));
        self::assertStringContainsString("Data e hora do saque: 28/03/2026 10:03:00\r\n", implode('', $connection->writes()));
        self::assertStringContainsString("Valor sacado: R$ 25,00\r\n", implode('', $connection->writes()));
    }

    public function testDispatchConvenienceMethodDelegatesToDispatcherFlow(): void
    {
        $connection = new RecordingNotificationSmtpConnection([
            '220 mailhog ready',
            '250 mailhog',
            '250 sender ok',
            '250 recipient ok',
            '354 end data',
            '250 queued',
            '221 bye',
        ]);
        $logger = new NotificationSpyStructuredLogger();
        $dispatcher = $this->dispatcher($connection, $logger);

        $dispatcher->dispatch($this->processedEvent(), 'direct@example.test');

        self::assertStringContainsString("RCPT TO:<direct@example.test>\r\n", implode('', $connection->writes()));
        self::assertSame('notification.dispatch.sent', $logger->infoMessages()[3]);
    }

    private function dispatcher(
        RecordingNotificationSmtpConnection $connection,
        NotificationSpyStructuredLogger $logger,
    ): WithdrawNotificationDispatcher {
        $mailer = new SmtpWithdrawMailer(
            $this->mailConfig(),
            new RecordingNotificationSmtpConnectionFactory($connection),
            $logger,
            new SmtpSendFailurePolicy($logger),
        );

        return new WithdrawNotificationDispatcher(
            $mailer,
            new WithdrawNotificationRenderer(
                new WithdrawNotificationTemplate(),
                new SafeEventPayloadSerializer(
                    new ProviderSensitiveDataMasker(),
                    new ProviderPayloadNormalizer(),
                ),
            ),
            new WithdrawNotificationSubjectTemplate(),
            $logger,
        );
    }

    private function processedEvent(): GenericDomainEvent
    {
        return new GenericDomainEvent(
            eventName: 'withdraw.processed',
            aggregateId: 'wd-1',
            occurredAt: '2026-03-28T10:03:00-03:00',
            correlationId: 'corr-1',
            payload: [
                'withdraw_id' => 'wd-1',
                'account_id' => 'acc-1',
                'amount' => '25.00',
                'method' => 'PIX',
                'status' => 'DONE',
                'pix_key_type' => 'EMAIL',
                'pix_key_masked' => 'u***@example.com',
            ],
        );
    }

    private function mailConfig(): MailConfig
    {
        return MailConfig::fromArray([
            'default' => 'smtp',
            'mailers' => [
                'smtp' => [
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
                ],
            ],
        ]);
    }
}

final class RecordingNotificationSmtpConnectionFactory implements SmtpConnectionFactory
{
    public function __construct(private readonly RecordingNotificationSmtpConnection $connection)
    {
    }

    public function connect(MailConfig $config): SmtpConnection
    {
        return $this->connection;
    }
}

final class RecordingNotificationSmtpConnection implements SmtpConnection
{
    /**
     * @var list<string>
     */
    private array $writes = [];

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
    }

    public function didTimeOut(): bool
    {
        return false;
    }

    public function close(): void
    {
    }

    /**
     * @return list<string>
     */
    public function writes(): array
    {
        return $this->writes;
    }
}

final class NotificationSpyStructuredLogger implements StructuredLogger
{
    /** @var list<string> */
    private array $infoMessages = [];

    public function info(string $message, LogContext $context): void
    {
        $this->infoMessages[] = $message;
    }

    public function warning(string $message, LogContext $context): void
    {
    }

    public function error(string $message, LogContext $context): void
    {
    }

    /**
     * @return list<string>
     */
    public function infoMessages(): array
    {
        return $this->infoMessages;
    }
}
