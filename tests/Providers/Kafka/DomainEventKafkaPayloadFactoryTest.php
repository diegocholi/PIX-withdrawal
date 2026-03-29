<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Kafka;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Providers\Kafka\DomainEventKafkaPayloadFactory;
use Tecnofit\PixWithdrawal\Providers\Kafka\InvalidKafkaPayloadContract;

final class DomainEventKafkaPayloadFactoryTest extends TestCase
{
    public function testBuildsCanonicalPayloadEnvelopeForWithdrawQueued(): void
    {
        $payload = (new DomainEventKafkaPayloadFactory())->create([
            'event_name' => 'withdraw.queued',
            'aggregate_id' => 'wd-queued',
            'occurred_at' => '2026-03-28T10:05:00-03:00',
            'correlation_id' => 'corr-queued',
            'trace_metadata' => ['source' => 'worker'],
            'payload' => [
                'withdraw_id' => 'wd-queued',
                'account_id' => 'acc-queued',
                'method' => 'PIX',
                'status' => 'QUEUED',
                'amount' => '23.50',
                'pix_key_type' => 'EMAIL',
                'queued_at' => '2026-03-28T10:05:00-03:00',
                'scheduled_at' => null,
            ],
        ]);

        self::assertSame(
            [
                'event_name' => 'withdraw.queued',
                'aggregate_id' => 'wd-queued',
                'occurred_at' => '2026-03-28T10:05:00-03:00',
                'correlation_id' => 'corr-queued',
                'trace_metadata' => ['source' => 'worker'],
                'payload' => [
                    'withdraw_id' => 'wd-queued',
                    'account_id' => 'acc-queued',
                    'method' => 'PIX',
                    'status' => 'QUEUED',
                    'amount' => '23.50',
                    'pix_key_type' => 'EMAIL',
                    'queued_at' => '2026-03-28T10:05:00-03:00',
                    'scheduled_at' => null,
                ],
            ],
            $payload->toArray(),
        );
    }

    public function testRejectsEventWithoutRequiredPayloadContractField(): void
    {
        $this->expectException(InvalidKafkaPayloadContract::class);
        $this->expectExceptionMessage(
            'Kafka payload contract field "account_id" is required for event "withdraw.queued".',
        );

        (new DomainEventKafkaPayloadFactory())->create([
            'event_name' => 'withdraw.queued',
            'aggregate_id' => 'wd-created',
            'occurred_at' => '2026-03-28T10:00:00-03:00',
            'correlation_id' => 'corr-created',
            'payload' => [
                'withdraw_id' => 'wd-created',
                'method' => 'PIX',
                'status' => 'QUEUED',
                'amount' => '10.00',
                'pix_key_type' => 'EMAIL',
                'queued_at' => '2026-03-28T10:00:00-03:00',
                'scheduled_at' => null,
            ],
        ]);
    }

    public function testRejectsUnsupportedEventPayloadContract(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Kafka payload contract is not defined for event "notification.email.withdraw".',
        );

        (new DomainEventKafkaPayloadFactory())->create([
            'event_name' => 'notification.email.withdraw',
            'aggregate_id' => 'wd-mail',
            'occurred_at' => '2026-03-28T10:10:00-03:00',
            'correlation_id' => 'corr-mail',
            'payload' => [],
        ]);
    }
}
