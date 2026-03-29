<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use Hyperf\Contract\ApplicationInterface;
use Hyperf\DbConnection\Db;
use RdKafka\Message;
use Symfony\Component\Console\Tester\CommandTester;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\WithdrawStatusViewQuery;

final class WithdrawProcessWorkerCommandFunctionalTest extends CliCommandIntegrationTestCase
{
    public function testCommandConsumesQueuedMessageProcessesWithdrawCommitsOffsetAndPublishesSuccessEvent(): void
    {
        $withdrawId = 'wd-worker-functional-100';
        $accountId = 'acc-worker-functional-100';
        $businessCorrelationId = 'corr-worker-functional-100';
        $workerGroupId = $this->uniqueGroupId('worker-functional-process');
        $inputTopic = sprintf('it.withdraw.process.%s', bin2hex(random_bytes(8)));
        $consumer = $this->createSubscribedKafkaConsumer(
            topic: $this->withdrawSucceededTopic(),
            groupId: $this->uniqueGroupId('worker-functional-success'),
            offsetReset: 'latest',
        );
        $notificationConsumer = $this->createSubscribedKafkaConsumer(
            topic: $this->notificationEmailWithdrawTopic(),
            groupId: $this->uniqueGroupId('worker-functional-notification'),
            offsetReset: 'latest',
        );

        $this->insertAccount($accountId);
        $this->persistWithdraw($this->queuedWithdraw($withdrawId, $accountId, $businessCorrelationId));
        $this->persistWithdrawPix(AccountWithdrawPix::create(
            withdrawId: $withdrawId,
            method: WithdrawMethod::PIX,
            pixKey: PixKey::from(PixKeyType::EMAIL, 'worker-functional@example.com'),
            createdAt: new \DateTimeImmutable('2026-03-28T13:00:00+00:00'),
        ));

        $this->publishRawMessage(
            topic: $inputTopic,
            payload: json_encode([
                'event_name' => 'withdraw.queued',
                'aggregate_id' => $withdrawId,
                'occurred_at' => '2026-03-28T10:05:00-03:00',
                'correlation_id' => $businessCorrelationId,
                'trace_metadata' => [
                    'origin' => 'cli.withdraw_scheduler',
                    'cli_correlation_id' => 'cli-worker-functional-seed',
                ],
                'payload' => [
                    'withdraw_id' => $withdrawId,
                    'account_id' => $accountId,
                    'method' => 'PIX',
                    'status' => 'QUEUED',
                    'amount' => '50.00',
                    'pix_key_type' => 'EMAIL',
                    'queued_at' => '2026-03-28T10:05:00-03:00',
                    'scheduled_at' => '2000-01-01T00:00:00+00:00',
                ],
            ], JSON_THROW_ON_ERROR),
            key: $accountId,
            headers: [
                'correlation_id' => $businessCorrelationId,
                'event_name' => 'withdraw.queued',
                'occurred_at' => '2026-03-28T10:05:00-03:00',
            ],
        );

        $tester = $this->commandTester();

        $exitCode = $tester->execute([
            '--max-messages' => '1',
            '--group-id' => $workerGroupId,
            '--topic' => $inputTopic,
        ]);
        $commandPayload = $this->decodeCommandPayload($tester->getDisplay());
        $publishedMessage = $this->waitForMatchingKafkaMessage(
            $consumer,
            function (Message $message) use ($withdrawId, $businessCorrelationId): bool {
                $payload = json_decode((string) $message->payload, true);

                return is_array($payload)
                    && ($payload['aggregate_id'] ?? null) === $withdrawId
                    && ($payload['correlation_id'] ?? null) === $businessCorrelationId
                    && ($payload['event_name'] ?? null) === 'withdraw.processed';
            },
        );
        $notificationMessage = $this->waitForMatchingKafkaMessage(
            $notificationConsumer,
            function (Message $message) use ($withdrawId, $businessCorrelationId): bool {
                $payload = json_decode((string) $message->payload, true);

                return is_array($payload)
                    && ($payload['aggregate_id'] ?? null) === $withdrawId
                    && ($payload['correlation_id'] ?? null) === $businessCorrelationId
                    && ($payload['event_name'] ?? null) === 'notification.email.withdraw';
            },
        );
        $statusView = $this->container
            ->get(WithdrawStatusViewQuery::class)
            ->find($accountId, $withdrawId);
        $committedMessage = $this->rawKafkaMessage($inputTopic, $workerGroupId, 2000);

        self::assertSame(0, $exitCode);
        self::assertSame('withdraw:worker:process', $commandPayload['command']);
        self::assertSame('ok', $commandPayload['status']);
        self::assertSame(1, $commandPayload['max_messages']);
        self::assertSame($workerGroupId, $commandPayload['group_id']);
        self::assertSame($inputTopic, $commandPayload['topic']);
        self::assertSame(1, $commandPayload['processed_messages']);
        self::assertNotNull($statusView);
        self::assertSame('DONE', $statusView->status());
        self::assertSame('50.00', $statusView->amount());
        self::assertSame('PIX', $statusView->method());
        self::assertTrue($statusView->scheduled());
        self::assertSame('2000-01-01T00:00:00-02:00', $statusView->scheduledFor());
        self::assertNotNull($statusView->processedAt());
        self::assertNull($statusView->errorReason());
        self::assertSame('EMAIL', $statusView->pixKeyType());
        self::assertSame('worker-functional@example.com', $statusView->pixKeyValue());
        self::assertSame('950.00', Db::table('account')->where('id', $accountId)->value('balance'));
        self::assertSame(1, Db::table('account_transaction')->where('reference_id', $withdrawId)->count());
        self::assertNotNull($publishedMessage, 'Withdraw process worker did not publish the expected success Kafka message.');
        self::assertNotNull($notificationMessage, 'Withdraw process worker did not publish the expected notification Kafka message.');
        self::assertSame($this->withdrawSucceededTopic(), $publishedMessage->topic_name);
        self::assertSame($accountId, $publishedMessage->key);
        self::assertSame([
            'correlation_id' => $businessCorrelationId,
            'event_name' => 'withdraw.processed',
            'occurred_at' => $publishedMessage->headers['occurred_at'] ?? null,
        ], $publishedMessage->headers ?? []);

        $publishedPayload = json_decode((string) $publishedMessage->payload, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('withdraw.processed', $publishedPayload['event_name']);
        self::assertSame($withdrawId, $publishedPayload['aggregate_id']);
        self::assertSame($businessCorrelationId, $publishedPayload['correlation_id']);
        self::assertSame('cli.withdraw_scheduler', $publishedPayload['trace_metadata']['origin'] ?? null);
        self::assertSame('cli-worker-functional-seed', $publishedPayload['trace_metadata']['cli_correlation_id'] ?? null);
        self::assertSame('cli.withdraw_worker', $publishedPayload['trace_metadata']['source'] ?? null);
        self::assertSame($inputTopic, $publishedPayload['trace_metadata']['kafka_topic'] ?? null);
        self::assertSame('DONE', $publishedPayload['payload']['status']);
        self::assertSame('EMAIL', $publishedPayload['payload']['pix_key_type']);
        self::assertSame('w***@example.com', $publishedPayload['payload']['pix_key_masked']);
        self::assertSame($this->notificationEmailWithdrawTopic(), $notificationMessage->topic_name);
        self::assertSame($withdrawId, $notificationMessage->key);
        self::assertSame([
            'correlation_id' => $businessCorrelationId,
            'event_name' => 'notification.email.withdraw',
            'occurred_at' => $notificationMessage->headers['occurred_at'] ?? null,
        ], $notificationMessage->headers ?? []);

        $notificationPayload = json_decode((string) $notificationMessage->payload, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('notification.email.withdraw', $notificationPayload['event_name']);
        self::assertSame($withdrawId, $notificationPayload['aggregate_id']);
        self::assertSame($businessCorrelationId, $notificationPayload['correlation_id']);
        self::assertSame('worker-functional@example.com', $notificationPayload['payload']['recipient']);
        self::assertSame('withdraw.processed', $notificationPayload['payload']['event']['event_name']);
        self::assertSame('w***@example.com', $notificationPayload['payload']['event']['payload']['pix_key_masked']);
        self::assertNull($committedMessage, 'Worker should commit the processed offset for the same consumer group.');
    }

    protected function commandTester(): CommandTester
    {
        $application = $this->container->get(ApplicationInterface::class);

        return new CommandTester($application->find('withdraw:worker:process'));
    }

    private function queuedWithdraw(string $withdrawId, string $accountId, string $correlationId): AccountWithdraw
    {
        $withdraw = AccountWithdraw::createScheduled(
            id: $withdrawId,
            accountId: $accountId,
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('50.00'),
            scheduledFor: ScheduleAt::fromDateTime(
                new \DateTimeImmutable('2000-01-01T00:00:00+00:00'),
                $this->testClockAt('1999-12-01T00:00:00+00:00'),
            ),
            correlationId: $correlationId,
            idempotencyKey: sprintf('idem-%s', $withdrawId),
            createdAt: new \DateTimeImmutable('2026-03-28T13:00:00+00:00'),
        );
        $withdraw->markAsQueued(new \DateTimeImmutable('2026-03-28T13:05:00+00:00'));

        return $withdraw;
    }

    private function withdrawSucceededTopic(): string
    {
        return 'withdraw.succeeded';
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeCommandPayload(string $display): array
    {
        $lines = preg_split('/\R/', trim($display)) ?: [];
        $jsonLine = null;

        foreach (array_reverse($lines) as $line) {
            $trimmedLine = trim($line);

            if (str_starts_with($trimmedLine, '{') && str_ends_with($trimmedLine, '}')) {
                $jsonLine = $trimmedLine;
                break;
            }
        }

        self::assertIsString($jsonLine, sprintf('Could not find JSON payload in command output: %s', $display));

        /** @var array<string, mixed> $payload */
        $payload = json_decode($jsonLine, true, 512, JSON_THROW_ON_ERROR);

        return $payload;
    }

    private function testClockAt(string $dateTime): \Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock
    {
        return new class ($dateTime) implements \Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock {
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
