<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Smoke;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use Tecnofit\PixWithdrawal\Core\Domain\Event\GenericDomainEvent;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpWithdrawMailer;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationDispatcher;

final class ProviderSmokeTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->clearEnvironmentOverrides();

        parent::tearDown();
    }

    public function testSmokeBootstrapResolvesMainProvidersWithLocalConfiguration(): void
    {
        $this->applyLocalProviderEnvironment();

        $container = (new HyperfContainerFactory())->create();

        self::assertSame('local', $container->get(ConfigInterface::class)->get('providers.runtime.environment'));
        self::assertInstanceOf(KafkaConfig::class, $container->get(KafkaConfig::class));
        self::assertInstanceOf(MailConfig::class, $container->get(MailConfig::class));
        self::assertInstanceOf(DomainEventDispatcher::class, $container->get(DomainEventDispatcher::class));
        self::assertInstanceOf(SmtpWithdrawMailer::class, $container->get(SmtpWithdrawMailer::class));
        self::assertInstanceOf(WithdrawNotificationDispatcher::class, $container->get(WithdrawNotificationDispatcher::class));
    }

    public function testSmokeKafkaDispatcherPublishesRealMessageToBroker(): void
    {
        $this->applyLocalProviderEnvironment();

        $container = (new HyperfContainerFactory())->create();
        $kafkaConfig = $container->get(KafkaConfig::class);
        $correlationId = sprintf('smoke-kafka-%s', bin2hex(random_bytes(8)));

        $container->get(DomainEventDispatcher::class)->dispatch(
            new GenericDomainEvent(
                eventName: 'withdraw.queued',
                aggregateId: 'wd-smoke-1',
                occurredAt: '2026-03-28T21:00:00-03:00',
                correlationId: $correlationId,
                payload: [
                    'withdraw_id' => 'wd-smoke-1',
                    'account_id' => 'acc-smoke-1',
                    'method' => 'PIX',
                    'status' => 'QUEUED',
                    'amount' => '22.90',
                    'pix_key_type' => 'EMAIL',
                    'queued_at' => '2026-03-28T21:00:00-03:00',
                    'scheduled_at' => null,
                ],
            )
        );

        $consumer = new KafkaConsumer($this->earliestConsumerConf(
            sprintf('providers-smoke-%s', bin2hex(random_bytes(8)))
        ));
        $consumer->subscribe([$kafkaConfig->withdrawProcessTopic()]);
        $message = $this->waitForKafkaMessage($consumer, $correlationId);

        self::assertNotNull($message, 'Smoke suite did not receive the Kafka publication in time.');
        self::assertSame($kafkaConfig->withdrawProcessTopic(), $message->topic_name);
        self::assertSame($correlationId, ($message->headers ?? [])['correlation_id'] ?? null);
        self::assertStringContainsString('"aggregate_id":"wd-smoke-1"', (string) $message->payload);
    }

    public function testSmokeNotificationDispatcherSendsMessageToMailhog(): void
    {
        $this->applyLocalProviderEnvironment();

        $container = (new HyperfContainerFactory())->create();
        $recipient = sprintf('providers-smoke-%s@example.test', bin2hex(random_bytes(8)));

        $container->get(WithdrawNotificationDispatcher::class)->dispatch(
            new GenericDomainEvent(
                eventName: 'withdraw.processed',
                aggregateId: 'wd-mail-smoke-1',
                occurredAt: '2026-03-28T21:10:00-03:00',
                correlationId: 'corr-mail-smoke-1',
                payload: [
                    'withdraw_id' => 'wd-mail-smoke-1',
                    'account_id' => 'acc-mail-smoke-1',
                    'amount' => '84.35',
                    'method' => 'PIX',
                    'status' => 'DONE',
                    'pix_key_type' => 'EMAIL',
                    'pix_key_masked' => 's***@example.test',
                ],
            ),
            $recipient,
        );

        $message = $this->waitForMailhogMessage($recipient, 'Saque PIX concluido');

        self::assertNotNull($message, 'Smoke suite did not find the SMTP notification in MailHog.');
        self::assertSame([$recipient], $message['Raw']['To'] ?? null);
        self::assertSame('Saque PIX concluido', $message['Content']['Headers']['Subject'][0] ?? null);
    }

    private function applyLocalProviderEnvironment(): void
    {
        putenv('APP_ENV=local');
        putenv('KAFKA_BROKERS=kafka:19092');
        putenv('MAIL_HOST=mailhog');
        putenv('MAIL_PORT=1025');
        putenv('MAIL_FROM_ADDRESS=providers-smoke@pix.local');
        putenv('MAIL_FROM_NAME=Providers Smoke');
        putenv('MAIL_CONNECT_TIMEOUT_SECONDS=3');
        putenv('MAIL_READ_TIMEOUT_SECONDS=5');
    }

    private function earliestConsumerConf(string $groupId): Conf
    {
        $conf = new Conf();
        $conf->set('client.id', 'providers-smoke');
        $conf->set('bootstrap.servers', 'kafka:19092');
        $conf->set('group.id', $groupId);
        $conf->set('enable.auto.commit', 'false');
        $conf->set('auto.offset.reset', 'earliest');

        return $conf;
    }

    private function waitForKafkaMessage(KafkaConsumer $consumer, string $correlationId, int $timeoutMs = 10000): ?Message
    {
        $deadline = microtime(true) + ($timeoutMs / 1000);

        do {
            $message = $consumer->consume(250);

            if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
                if ((($message->headers ?? [])['correlation_id'] ?? null) === $correlationId) {
                    return $message;
                }

                continue;
            }

            if (! in_array($message->err, [RD_KAFKA_RESP_ERR__TIMED_OUT, RD_KAFKA_RESP_ERR__PARTITION_EOF], true)) {
                self::fail(sprintf(
                    'Smoke suite Kafka polling failed with code %d: %s',
                    $message->err,
                    $message->errstr(),
                ));
            }
        } while (microtime(true) < $deadline);

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function waitForMailhogMessage(string $recipient, string $subject, int $timeoutMs = 10000): ?array
    {
        $deadline = microtime(true) + ($timeoutMs / 1000);

        do {
            $payload = file_get_contents('http://mailhog:8025/api/v2/messages');

            self::assertNotFalse($payload, 'Unable to query MailHog API.');

            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

            foreach (($decoded['items'] ?? []) as $message) {
                if (! is_array($message)) {
                    continue;
                }

                if (
                    is_array($message['Raw']['To'] ?? null)
                    && in_array($recipient, $message['Raw']['To'], true)
                    && (($message['Content']['Headers']['Subject'][0] ?? null) === $subject)
                ) {
                    return $message;
                }
            }

            usleep(250000);
        } while (microtime(true) < $deadline);

        return null;
    }

    private function clearEnvironmentOverrides(): void
    {
        foreach ([
            'APP_ENV',
            'APP_DEBUG',
            'APP_CHARSET',
            'APP_LOG_MAX_FILES',
            'APP_LOG_LEVEL',
            'APP_LOCALE',
            'APP_FALLBACK_LOCALE',
            'APP_NAME',
            'APP_TIMEZONE',
            'KAFKA_BROKERS',
            'MAIL_HOST',
            'MAIL_PORT',
            'MAIL_FROM_ADDRESS',
            'MAIL_FROM_NAME',
            'MAIL_CONNECT_TIMEOUT_SECONDS',
            'MAIL_READ_TIMEOUT_SECONDS',
        ] as $environmentVariable) {
            putenv($environmentVariable);
        }
    }
}
