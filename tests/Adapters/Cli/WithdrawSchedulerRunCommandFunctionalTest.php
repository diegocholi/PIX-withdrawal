<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use Hyperf\DbConnection\Db;
use RdKafka\Message;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;

final class WithdrawSchedulerRunCommandFunctionalTest extends CliCommandIntegrationTestCase
{
    public function testCommandPublishesQueuedEventToKafkaForEligibleScheduledWithdraw(): void
    {
        $withdrawId = 'wd-scheduler-functional-100';
        $accountId = 'acc-scheduler-functional-100';
        $businessCorrelationId = 'corr-wd-scheduler-functional-100';
        $consumer = $this->createSubscribedKafkaConsumer(
            topic: $this->withdrawProcessTopic(),
            groupId: $this->uniqueGroupId('scheduler-functional'),
            offsetReset: 'latest',
        );

        $this->insertAccount($accountId);
        $this->persistWithdraw($this->scheduledWithdraw(
            $withdrawId,
            $accountId,
            '2000-01-01T00:00:00+00:00',
            $businessCorrelationId,
        ));
        $this->persistWithdrawPix(AccountWithdrawPix::create(
            withdrawId: $withdrawId,
            method: WithdrawMethod::PIX,
            pixKey: PixKey::from(PixKeyType::EMAIL, 'functional@example.com'),
            createdAt: new \DateTimeImmutable('2026-03-28T13:00:00+00:00'),
        ));

        $tester = $this->commandTester();

        $exitCode = $tester->execute([
            '--batch-size' => '1',
            '--force' => true,
        ]);
        $commandPayload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $publishedMessage = $this->waitForMatchingKafkaMessage(
            $consumer,
            function (Message $message) use ($withdrawId, $businessCorrelationId): bool {
                $payload = json_decode((string) $message->payload, true);

                return is_array($payload)
                    && ($payload['aggregate_id'] ?? null) === $withdrawId
                    && ($payload['correlation_id'] ?? null) === $businessCorrelationId;
            },
        );

        self::assertSame(0, $exitCode);
        self::assertSame('ok', $commandPayload['status']);
        self::assertSame([$withdrawId], $commandPayload['withdraw_ids']['published']);
        self::assertSame('QUEUED', Db::table('account_withdraw')->where('id', $withdrawId)->value('status'));
        self::assertNotNull($publishedMessage, 'Scheduler command did not publish the expected Kafka message.');
        self::assertSame($this->withdrawProcessTopic(), $publishedMessage->topic_name);
        self::assertSame($accountId, $publishedMessage->key);
        self::assertSame([
            'correlation_id' => $businessCorrelationId,
            'event_name' => 'withdraw.queued',
            'occurred_at' => $publishedMessage->headers['occurred_at'] ?? null,
        ], $publishedMessage->headers ?? []);

        $publishedPayload = json_decode((string) $publishedMessage->payload, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('withdraw.queued', $publishedPayload['event_name']);
        self::assertSame($withdrawId, $publishedPayload['aggregate_id']);
        self::assertSame($businessCorrelationId, $publishedPayload['correlation_id']);
        self::assertSame('cli.withdraw_scheduler', $publishedPayload['trace_metadata']['origin'] ?? null);
        self::assertIsString($publishedPayload['trace_metadata']['cli_correlation_id'] ?? null);
        self::assertNotSame('', $publishedPayload['trace_metadata']['cli_correlation_id'] ?? '');
        self::assertSame($withdrawId, $publishedPayload['payload']['withdraw_id']);
        self::assertSame($accountId, $publishedPayload['payload']['account_id']);
        self::assertSame('PIX', $publishedPayload['payload']['method']);
        self::assertSame('QUEUED', $publishedPayload['payload']['status']);
        self::assertSame('50.00', $publishedPayload['payload']['amount']);
        self::assertSame('EMAIL', $publishedPayload['payload']['pix_key_type']);
        self::assertSame('2000-01-01T00:00:00+00:00', $publishedPayload['payload']['scheduled_at']);
        self::assertNotNull($publishedPayload['payload']['queued_at']);
    }

    public function testCommandDryRunListsEligibleWithdrawsWithoutChangingStateOrPublishingKafkaMessage(): void
    {
        $dueWithdrawId = 'wd-scheduler-dry-run-100';
        $futureWithdrawId = 'wd-scheduler-dry-run-200';
        $consumer = $this->createSubscribedKafkaConsumer(
            topic: $this->withdrawProcessTopic(),
            groupId: $this->uniqueGroupId('scheduler-dry-run'),
            offsetReset: 'latest',
        );

        $this->insertAccount('acc-scheduler-dry-run-100');
        $this->insertAccount('acc-scheduler-dry-run-200');
        $this->persistWithdraw($this->scheduledWithdraw(
            $dueWithdrawId,
            'acc-scheduler-dry-run-100',
            '2000-01-01T00:00:00+00:00',
        ));
        $this->persistWithdraw($this->scheduledWithdraw(
            $futureWithdrawId,
            'acc-scheduler-dry-run-200',
            '2999-01-01T00:00:00+00:00',
        ));

        $tester = $this->commandTester();

        $exitCode = $tester->execute([
            '--batch-size' => '10',
            '--dry-run' => true,
        ]);
        $commandPayload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $publishedMessage = $this->waitForMatchingKafkaMessage(
            $consumer,
            static function (Message $message) use ($dueWithdrawId): bool {
                $payload = json_decode((string) $message->payload, true);

                return is_array($payload)
                    && ($payload['aggregate_id'] ?? null) === $dueWithdrawId
                    && ($payload['event_name'] ?? null) === 'withdraw.queued';
            },
            2000,
        );

        self::assertSame(0, $exitCode);
        self::assertSame('simulated', $commandPayload['status']);
        self::assertTrue($commandPayload['dry_run']);
        self::assertSame(10, $commandPayload['batch_size']);
        self::assertSame(['due' => 1, 'promoted' => 0, 'published' => 0, 'skipped' => 0, 'publication_failed' => 0], $commandPayload['summary']);
        self::assertSame([$dueWithdrawId], $commandPayload['withdraw_ids']['due']);
        self::assertSame([], $commandPayload['withdraw_ids']['promoted']);
        self::assertSame([], $commandPayload['withdraw_ids']['published']);
        self::assertSame([], $commandPayload['withdraw_ids']['publication_failed']);
        self::assertSame('SCHEDULED', Db::table('account_withdraw')->where('id', $dueWithdrawId)->value('status'));
        self::assertSame('SCHEDULED', Db::table('account_withdraw')->where('id', $futureWithdrawId)->value('status'));
        self::assertNull($publishedMessage, 'Dry-run scheduler command must not publish Kafka messages.');
    }
}
