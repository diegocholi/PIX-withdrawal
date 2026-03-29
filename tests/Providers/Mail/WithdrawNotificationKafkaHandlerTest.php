<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Mail;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerMessage;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpConnection;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpConnectionFactory;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpSendFailurePolicy;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpWithdrawMailer;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationDispatcher;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationKafkaHandler;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationKafkaMessageMapper;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationRenderer;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationSubjectTemplate;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationTemplate;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderPayloadNormalizer;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderSensitiveDataMasker;
use Tecnofit\PixWithdrawal\Providers\Serialization\SafeEventPayloadSerializer;

final class WithdrawNotificationKafkaHandlerTest extends TestCase
{
    public function testHandlesNotificationKafkaMessageAndDispatchesEmail(): void
    {
        $connection = new KafkaNotificationSmtpConnection([
            '220 mailhog ready',
            '250 mailhog',
            '250 sender ok',
            '250 recipient ok',
            '354 end data',
            '250 queued',
            '221 bye',
        ]);
        $logger = new KafkaNotificationSpyStructuredLogger();
        $handler = new WithdrawNotificationKafkaHandler(
            new WithdrawNotificationKafkaMessageMapper(),
            $this->dispatcher($connection, $logger),
        );

        $handler->handle(new KafkaConsumerMessage(
            topic: 'notification.email.withdraw',
            payload: '{"event_name":"notification.email.withdraw","correlation_id":"corr-notify","occurred_at":"2026-03-28T10:04:00-03:00","payload":{"recipient":"customer@example.test","event":{"event_name":"withdraw.processed","aggregate_id":"wd-1","occurred_at":"2026-03-28T10:03:00-03:00","correlation_id":"corr-1","payload":{"amount":"25.00","pix_key_type":"EMAIL","pix_key_masked":"u***@example.com"}}}}',
            key: 'wd-1',
            headers: [],
            partition: 0,
            offset: 1,
        ));

        self::assertStringContainsString("RCPT TO:<customer@example.test>\r\n", implode('', $connection->writes()));
        self::assertSame([
            'notification.dispatch.started',
            'mail.send.attempt',
            'mail.send.succeeded',
            'notification.dispatch.sent',
        ], $logger->infoMessages());
    }

    private function dispatcher(
        KafkaNotificationSmtpConnection $connection,
        KafkaNotificationSpyStructuredLogger $logger,
    ): WithdrawNotificationDispatcher {
        return new WithdrawNotificationDispatcher(
            new SmtpWithdrawMailer(
                MailConfig::fromArray([
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
                ]),
                new KafkaNotificationSmtpConnectionFactory($connection),
                $logger,
                new SmtpSendFailurePolicy($logger),
            ),
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
}

final class KafkaNotificationSmtpConnectionFactory implements SmtpConnectionFactory
{
    public function __construct(private readonly KafkaNotificationSmtpConnection $connection)
    {
    }

    public function connect(MailConfig $config): SmtpConnection
    {
        return $this->connection;
    }
}

final class KafkaNotificationSmtpConnection implements SmtpConnection
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

final class KafkaNotificationSpyStructuredLogger implements StructuredLogger
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
