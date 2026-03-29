<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Bootstrap;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\CorrelationIdGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\EventPayloadSerializer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\LogPayloadSerializer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SensitiveDataMasker;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\UuidGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawDuplicateGuardFingerprintGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawDuplicateGuardWindow;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawIdempotencyKeyGenerator;
use Tecnofit\PixWithdrawal\Providers\Bootstrap\ProviderBindingRegistry;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaMessageProducer;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;
use Tecnofit\PixWithdrawal\Providers\Logging\HyperfStructuredLogger;
use Tecnofit\PixWithdrawal\Providers\Logging\ProviderLogContextEnricher;
use Tecnofit\PixWithdrawal\Providers\Metrics\ProviderMetricEmitter;

final class ProviderBindingRegistryConsistencyTest extends TestCase
{
    public function testRegistryDelegatesConcreteInstantiationToFactories(): void
    {
        $definitions = ProviderBindingRegistry::definitions();

        foreach ([
            \DateTimeZone::class,
            Clock::class,
            CorrelationIdGenerator::class,
            KafkaMessageProducer::class,
            ProviderConfigProvider::class,
            DomainEventDispatcher::class,
            EventPayloadSerializer::class,
            LogPayloadSerializer::class,
            HyperfStructuredLogger::class,
            ProviderLogContextEnricher::class,
            ProviderMetricEmitter::class,
            SensitiveDataMasker::class,
            UuidGenerator::class,
            WithdrawDuplicateGuardFingerprintGenerator::class,
            WithdrawDuplicateGuardWindow::class,
            WithdrawIdempotencyKeyGenerator::class,
            Observability::class,
        ] as $binding) {
            self::assertIsCallable(
                $definitions[$binding],
                sprintf('Binding "%s" must delegate creation through a factory-backed callable.', $binding)
            );
        }
    }
}
