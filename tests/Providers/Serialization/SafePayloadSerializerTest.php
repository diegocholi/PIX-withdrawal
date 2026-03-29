<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Serialization;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Event\GenericDomainEvent;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderSensitiveDataMasker;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderPayloadNormalizer;
use Tecnofit\PixWithdrawal\Providers\Serialization\SafeEventPayloadSerializer;
use Tecnofit\PixWithdrawal\Providers\Serialization\SafeLogPayloadSerializer;

final class SafePayloadSerializerTest extends TestCase
{
    public function testEventPayloadSerializerMasksPixKeyFields(): void
    {
        $serializer = new SafeEventPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer());
        $event = new GenericDomainEvent(
            eventName: 'withdraw.created',
            aggregateId: 'wd-1',
            occurredAt: '2026-03-28T10:00:00-03:00',
            correlationId: 'corr-1',
            payload: [
                'pix_key' => 'user@example.com',
                'pix_key_type' => 'EMAIL',
            ],
        );

        self::assertSame(
            [
                'event_name' => 'withdraw.created',
                'aggregate_id' => 'wd-1',
                'occurred_at' => '2026-03-28T10:00:00-03:00',
                'correlation_id' => 'corr-1',
                'trace_metadata' => [],
                'payload' => [
                    'pix_key' => 'u***@example.com',
                    'pix_key_type' => 'EMAIL',
                ],
            ],
            $serializer->serialize($event)
        );
    }

    public function testLogPayloadSerializerMasksPixKeyInAdditionalContext(): void
    {
        $serializer = new SafeLogPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer());
        $context = new LogContext(
            correlationId: 'corr-2',
            withdrawId: 'wd-2',
            context: [
                'pix_key' => 'queue@example.com',
                'step' => 'enqueue',
            ]
        );

        self::assertSame(
            [
                'correlation_id' => 'corr-2',
                'withdraw_id' => 'wd-2',
                'account_id' => null,
                'status' => null,
                'error_code' => null,
                'trace_metadata' => [],
                'context' => [
                    'pix_key' => 'q***@example.com',
                    'step' => 'enqueue',
                ],
            ],
            $serializer->serialize($context)
        );
    }

    public function testLogPayloadSerializerMasksNestedHttpSensitiveContext(): void
    {
        $serializer = new SafeLogPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer());
        $context = new LogContext(
            correlationId: 'corr-3',
            context: [
                'pix' => [
                    'key' => 'nested@example.com',
                    'key_type' => 'EMAIL',
                ],
                'authorization' => 'Bearer secret-token',
            ]
        );

        self::assertSame(
            [
                'correlation_id' => 'corr-3',
                'withdraw_id' => null,
                'account_id' => null,
                'status' => null,
                'error_code' => null,
                'trace_metadata' => [],
                'context' => [
                    'pix' => [
                        'key' => 'n***@example.com',
                        'key_type' => 'EMAIL',
                    ],
                    'authorization' => '***',
                ],
            ],
            $serializer->serialize($context)
        );
    }

    public function testEventPayloadSerializerNormalizesDatesEnumsAndSerializableDtos(): void
    {
        $serializer = new SafeEventPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer());
        $event = new GenericDomainEvent(
            eventName: 'withdraw.payload.normalized',
            aggregateId: 'wd-9',
            occurredAt: '2026-03-28T10:00:00-03:00',
            correlationId: 'corr-9',
            payload: [
                'processed_at' => new DateTimeImmutable('2026-03-28T10:15:00-03:00'),
                'status' => WithdrawStatus::DONE,
                'amount' => Money::fromDecimal('10.50'),
                'pix_key' => 'normalize@example.com',
            ],
        );

        self::assertSame(
            [
                'event_name' => 'withdraw.payload.normalized',
                'aggregate_id' => 'wd-9',
                'occurred_at' => '2026-03-28T10:00:00-03:00',
                'correlation_id' => 'corr-9',
                'trace_metadata' => [],
                'payload' => [
                    'processed_at' => '2026-03-28T10:15:00-03:00',
                    'status' => 'DONE',
                    'amount' => [
                        'amount' => '10.50',
                        'minor_amount' => 1050,
                    ],
                    'pix_key' => 'n***@example.com',
                ],
            ],
            $serializer->serialize($event)
        );
    }

    public function testSensitiveDataMaskerMasksPixKeyDirectly(): void
    {
        $masker = new ProviderSensitiveDataMasker();

        self::assertSame('u***@example.com', $masker->maskPixKey('user@example.com'));
        self::assertSame('***', $masker->maskPixKey('12345678901'));
        self::assertNull($masker->maskPixKey(null));
    }
}
