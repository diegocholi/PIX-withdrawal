<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use Hyperf\Contract\ApplicationInterface;
use Hyperf\DbConnection\Db;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\Producer;
use Symfony\Component\Console\Tester\CommandTester;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

abstract class CliCommandIntegrationTestCase extends DataSchemaMigrationIntegrationTestCase
{
    protected function setUp(): void
    {
        putenv('KAFKA_BROKERS=kafka:19092');

        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv('KAFKA_BROKERS');

        parent::tearDown();
    }

    protected function commandTester(): CommandTester
    {
        $application = $this->container->get(ApplicationInterface::class);

        return new CommandTester($application->find('withdraw:scheduler:run'));
    }

    protected function insertAccount(string $accountId): void
    {
        Db::table('account')->insert([
            'id' => $accountId,
            'name' => 'CLI Scheduler Test Account',
            'balance' => '1000.00',
            'created_at' => '2026-03-28 12:00:00.000000',
            'updated_at' => '2026-03-28 12:00:00.000000',
        ]);
    }

    protected function persistWithdraw(AccountWithdraw $withdraw): void
    {
        Db::table('account_withdraw')->insert([
            'id' => $withdraw->id(),
            'account_id' => $withdraw->accountId(),
            'method' => $withdraw->method()->value,
            'amount' => $withdraw->amount()->toDecimal(),
            'scheduled' => $withdraw->isScheduled() ? 1 : 0,
            'scheduled_for' => $withdraw->scheduledFor()?->value()->format('Y-m-d H:i:s.u'),
            'status' => $withdraw->status()->value,
            'error_reason' => $withdraw->errorReason(),
            'requested_at' => $withdraw->createdAt()->format('Y-m-d H:i:s.u'),
            'queued_at' => $withdraw->queuedAt()?->format('Y-m-d H:i:s.u'),
            'processing_started_at' => $withdraw->processingStartedAt()?->format('Y-m-d H:i:s.u'),
            'processed_at' => $withdraw->processedAt()?->format('Y-m-d H:i:s.u'),
            'correlation_id' => $withdraw->correlationId(),
            'idempotency_key' => $withdraw->idempotencyKey(),
            'retry_count' => $withdraw->retryCount(),
            'last_retry_at' => $withdraw->lastRetryAt()?->format('Y-m-d H:i:s.u'),
            'created_at' => $withdraw->createdAt()->format('Y-m-d H:i:s.u'),
            'updated_at' => $withdraw->updatedAt()->format('Y-m-d H:i:s.u'),
        ]);
    }

    protected function persistWithdrawPix(AccountWithdrawPix $withdrawPix): void
    {
        Db::table('account_withdraw_pix')->insert([
            'account_withdraw_id' => $withdrawPix->withdrawId(),
            'type' => $withdrawPix->pixKeyType()->value,
            'key' => $withdrawPix->pixKey()->value(),
            'created_at' => $withdrawPix->createdAt()->format('Y-m-d H:i:s.u'),
            'updated_at' => $withdrawPix->updatedAt()->format('Y-m-d H:i:s.u'),
        ]);
    }

    protected function scheduledWithdraw(
        string $withdrawId,
        string $accountId,
        string $scheduledFor,
        ?string $correlationId = null,
    ): AccountWithdraw {
        return AccountWithdraw::createScheduled(
            id: $withdrawId,
            accountId: $accountId,
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('50.00'),
            scheduledFor: ScheduleAt::fromDateTime(
                new \DateTimeImmutable($scheduledFor),
                $this->clockAt('1999-12-01T00:00:00+00:00'),
            ),
            correlationId: $correlationId ?? sprintf('corr-%s', $withdrawId),
            idempotencyKey: sprintf('idem-%s', $withdrawId),
            createdAt: new \DateTimeImmutable('2026-03-28 13:00:00+00:00'),
        );
    }

    protected function uniqueGroupId(string $prefix): string
    {
        return sprintf('%s-%s', $prefix, bin2hex(random_bytes(8)));
    }

    protected function createSubscribedKafkaConsumer(
        string $topic,
        string $groupId,
        string $offsetReset = 'latest',
    ): KafkaConsumer {
        $consumer = new KafkaConsumer($this->consumerConf($groupId, $offsetReset));
        $consumer->subscribe([$topic]);
        $consumer->consume(250);

        return $consumer;
    }

    protected function waitForMatchingKafkaMessage(
        KafkaConsumer $consumer,
        callable $matches,
        int $timeoutMs = 10000,
    ): ?Message {
        $deadline = microtime(true) + ($timeoutMs / 1000);

        do {
            $message = $consumer->consume(250);

            if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR && $matches($message) === true) {
                return $message;
            }

            if (! in_array($message->err, [RD_KAFKA_RESP_ERR_NO_ERROR, RD_KAFKA_RESP_ERR__TIMED_OUT, RD_KAFKA_RESP_ERR__PARTITION_EOF], true)) {
                self::fail(sprintf(
                    'Kafka consumer polling failed for topic "%s" with code %d: %s',
                    $message->topic_name,
                    $message->err,
                    $message->errstr(),
                ));
            }
        } while (microtime(true) < $deadline);

        return null;
    }

    protected function rawKafkaMessage(string $topic, string $groupId, int $timeoutMs = 4000): ?Message
    {
        $consumer = new KafkaConsumer($this->consumerConf($groupId, 'earliest'));
        $consumer->subscribe([$topic]);

        $deadline = microtime(true) + ($timeoutMs / 1000);

        do {
            $message = $consumer->consume(250);

            if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
                return $message;
            }

            if (! in_array($message->err, [RD_KAFKA_RESP_ERR__TIMED_OUT, RD_KAFKA_RESP_ERR__PARTITION_EOF], true)) {
                self::fail(sprintf(
                    'Kafka consumer polling failed for topic "%s" with code %d: %s',
                    $topic,
                    $message->err,
                    $message->errstr(),
                ));
            }
        } while (microtime(true) < $deadline);

        return null;
    }

    /**
     * @param array<string, string> $headers
     */
    protected function publishRawMessage(
        string $topic,
        string $payload,
        ?string $key = null,
        array $headers = [],
    ): void {
        $producer = new Producer($this->producerConf());
        $topicProducer = $producer->newTopic($topic);
        $topicProducer->producev(RD_KAFKA_PARTITION_UA, 0, $payload, $key, $headers);
        $producer->poll(0);

        $flushResult = $producer->flush(5000);

        self::assertSame(
            RD_KAFKA_RESP_ERR_NO_ERROR,
            $flushResult,
            sprintf('Kafka raw publish flush failed with code %d.', $flushResult),
        );
    }

    protected function withdrawProcessTopic(): string
    {
        return 'withdraw.process';
    }

    protected function notificationEmailWithdrawTopic(): string
    {
        return 'notification.email.withdraw';
    }

    protected function uniqueEmail(string $prefix): string
    {
        return sprintf('%s-%s@example.test', $prefix, bin2hex(random_bytes(8)));
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function waitForMailhogMessage(string $recipient, string $subject, int $timeoutMs = 10000): ?array
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

                $rawRecipients = $message['Raw']['To'] ?? [];
                $messageSubject = $message['Content']['Headers']['Subject'][0] ?? null;

                if (
                    is_array($rawRecipients)
                    && in_array($recipient, $rawRecipients, true)
                    && $messageSubject === $subject
                ) {
                    return $message;
                }
            }

            usleep(250000);
        } while (microtime(true) < $deadline);

        return null;
    }

    private function consumerConf(string $groupId, string $offsetReset): Conf
    {
        $conf = new Conf();
        $conf->set('client.id', 'pix-withdrawal-cli-scheduler-integration');
        $conf->set('bootstrap.servers', $this->brokers());
        $conf->set('group.id', $groupId);
        $conf->set('enable.auto.commit', 'false');
        $conf->set('auto.offset.reset', $offsetReset);

        return $conf;
    }

    private function producerConf(): Conf
    {
        $conf = new Conf();
        $conf->set('client.id', 'pix-withdrawal-cli-scheduler-integration');
        $conf->set('bootstrap.servers', $this->brokers());

        return $conf;
    }

    private function brokers(): string
    {
        $brokers = getenv('KAFKA_BROKERS');

        return is_string($brokers) && trim($brokers) !== '' ? trim($brokers) : 'kafka:19092';
    }

    private function clockAt(string $dateTime): Clock
    {
        return new class ($dateTime) implements Clock {
            public function __construct(private string $dateTime)
            {
            }

            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable($this->dateTime);
            }
        };
    }
}
