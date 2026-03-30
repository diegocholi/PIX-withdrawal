<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Bootstrap;

use Hyperf\Contract\ConfigInterface;
use Psr\Clock\ClockInterface;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AtomicAccountDebit;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\CorrelationIdGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\EventPayloadSerializer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\FailureClassifier;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\LogPayloadSerializer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\MetricEmitter;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SensitiveDataMasker;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\UuidGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawDuplicateGuardFingerprintGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawDuplicateGuardWindow;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawIdempotencyKeyGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\FailureCategoryClassifier;
use Tecnofit\PixWithdrawal\Plugins\Account\DomainAtomicAccountDebit;
use Tecnofit\PixWithdrawal\Plugins\Identifier\DeterministicWithdrawDuplicateGuardFingerprintGenerator;
use Tecnofit\PixWithdrawal\Plugins\Identifier\RandomUuidGenerator;
use Tecnofit\PixWithdrawal\Plugins\Observability\NullObservability;
use Tecnofit\PixWithdrawal\Plugins\Identifier\RandomWithdrawIdempotencyKeyGenerator;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;
use Tecnofit\PixWithdrawal\Providers\Config\ConfiguredWithdrawDuplicateGuardWindow;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;
use Tecnofit\PixWithdrawal\Providers\Factories\ClockFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\DateTimeZoneFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\DomainEventDispatcherFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\EventPayloadSerializerFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\HyperfStructuredLoggerFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\KafkaConfigFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\KafkaConsumerFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\KafkaProducerFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\LogPayloadSerializerFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\MetricEmitterFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\NullMetricEmitterFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\NullObservabilityFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\ObservabilityFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\ProviderConfigProviderFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\ProviderLogContextEnricherFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\ProviderMetricEmitterFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\ProviderPayloadNormalizerFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\SensitiveDataMaskerFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\SmtpConfigFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\StructuredLoggerFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\SmtpWithdrawMailerFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\UuidGeneratorFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\WithdrawNotificationDispatcherFactory;
use Tecnofit\PixWithdrawal\Providers\Factories\WithdrawNotificationKafkaHandlerFactory;
use Tecnofit\PixWithdrawal\Providers\Logging\HyperfStructuredLogger;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaMessageProducer;
use Tecnofit\PixWithdrawal\Providers\Logging\ProviderLogContextEnricher;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpWithdrawMailer;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationDispatcher;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationKafkaHandler;
use Tecnofit\PixWithdrawal\Providers\Metrics\NullMetricEmitter;
use Tecnofit\PixWithdrawal\Providers\Metrics\ProviderMetricEmitter;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderPayloadNormalizer;

final class ProviderBindingRegistry
{
    /**
     * @return array<class-string|string, class-string|\Closure>
     */
    public static function definitions(): array
    {
        return [
            ...self::coreBindings(),
            ...self::providerBindings(),
            ...self::factoryBindings(),
        ];
    }

    /**
     * @return array<class-string|string, class-string|\Closure>
     */
    private static function coreBindings(): array
    {
        return [
            \DateTimeZone::class => static fn ($container) => $container->get(DateTimeZoneFactory::class)->create(),
            AtomicAccountDebit::class => DomainAtomicAccountDebit::class,
            Clock::class => static fn ($container) => $container->get(ClockFactory::class)->create(),
            ClockInterface::class => static fn ($container) => $container->get(Clock::class),
            CorrelationIdGenerator::class => static fn () => new RandomUuidGenerator(),
            FailureClassifier::class => FailureCategoryClassifier::class,
            SensitiveDataMasker::class => static fn ($container) => $container->get(SensitiveDataMaskerFactory::class)->create(),
            LogPayloadSerializer::class => static fn ($container) => $container->get(LogPayloadSerializerFactory::class)->create(),
            EventPayloadSerializer::class => static fn ($container) => $container->get(EventPayloadSerializerFactory::class)->create(),
            UuidGenerator::class => static fn ($container) => $container->get(UuidGeneratorFactory::class)->create(),
            WithdrawDuplicateGuardFingerprintGenerator::class => static fn () => new DeterministicWithdrawDuplicateGuardFingerprintGenerator(),
            WithdrawDuplicateGuardWindow::class => static fn ($container) => new ConfiguredWithdrawDuplicateGuardWindow(
                $container->get(ProviderConfigProvider::class)->withdrawDuplicateGuardWindowSeconds(),
            ),
            WithdrawIdempotencyKeyGenerator::class => static fn () => new RandomWithdrawIdempotencyKeyGenerator(),
        ];
    }

    /**
     * @return array<class-string|string, class-string|\Closure>
     */
    private static function providerBindings(): array
    {
        return [
            KafkaConfig::class => static fn ($container) => $container->get(KafkaConfigFactory::class)->create(),
            KafkaMessageProducer::class => static fn ($container) => $container->get(KafkaProducerFactory::class)->create(),
            MailConfig::class => static fn ($container) => $container->get(SmtpConfigFactory::class)->create(),
            SmtpWithdrawMailer::class => static fn ($container) => $container->get(SmtpWithdrawMailerFactory::class)->create(),
            WithdrawNotificationDispatcher::class => static fn ($container) => $container->get(WithdrawNotificationDispatcherFactory::class)->create(),
            WithdrawNotificationKafkaHandler::class => static fn ($container) => $container->get(WithdrawNotificationKafkaHandlerFactory::class)->create(),
            ProviderConfigProvider::class => static fn ($container) => $container->get(ProviderConfigProviderFactory::class)->create(),
            ProviderPayloadNormalizer::class => static fn ($container) => $container->get(ProviderPayloadNormalizerFactory::class)->create(),
            DomainEventDispatcher::class => static fn ($container) => $container->get(DomainEventDispatcherFactory::class)->create(),
            MetricEmitter::class => static fn ($container) => $container->get(MetricEmitterFactory::class)->create(),
            HyperfStructuredLogger::class => static fn ($container) => $container->get(HyperfStructuredLoggerFactory::class)->create(),
            ProviderLogContextEnricher::class => static fn ($container) => $container->get(ProviderLogContextEnricherFactory::class)->create(),
            ProviderMetricEmitter::class => static fn ($container) => $container->get(ProviderMetricEmitterFactory::class)->create(),
            NullMetricEmitter::class => static fn ($container) => $container->get(NullMetricEmitterFactory::class)->create(),
            StructuredLogger::class => static fn ($container) => $container->get(StructuredLoggerFactory::class)->create(),
            NullObservability::class => static fn ($container) => $container->get(NullObservabilityFactory::class)->create(),
            Observability::class => static fn ($container) => $container->get(ObservabilityFactory::class)->create(),
        ];
    }

    /**
     * @return array<class-string|string, class-string|\Closure>
     */
    private static function factoryBindings(): array
    {
        return [
            DateTimeZoneFactory::class => DateTimeZoneFactory::class,
            ProviderConfigProviderFactory::class => ProviderConfigProviderFactory::class,
            SensitiveDataMaskerFactory::class => SensitiveDataMaskerFactory::class,
            LogPayloadSerializerFactory::class => LogPayloadSerializerFactory::class,
            EventPayloadSerializerFactory::class => EventPayloadSerializerFactory::class,
            ProviderLogContextEnricherFactory::class => ProviderLogContextEnricherFactory::class,
            ProviderMetricEmitterFactory::class => ProviderMetricEmitterFactory::class,
            ProviderPayloadNormalizerFactory::class => ProviderPayloadNormalizerFactory::class,
            NullMetricEmitterFactory::class => NullMetricEmitterFactory::class,
            HyperfStructuredLoggerFactory::class => HyperfStructuredLoggerFactory::class,
            KafkaProducerFactory::class => KafkaProducerFactory::class,
            KafkaConsumerFactory::class => KafkaConsumerFactory::class,
            NullObservabilityFactory::class => NullObservabilityFactory::class,
            ClockFactory::class => ClockFactory::class,
            SmtpWithdrawMailerFactory::class => SmtpWithdrawMailerFactory::class,
            WithdrawNotificationDispatcherFactory::class => WithdrawNotificationDispatcherFactory::class,
            WithdrawNotificationKafkaHandlerFactory::class => WithdrawNotificationKafkaHandlerFactory::class,
            UuidGeneratorFactory::class => UuidGeneratorFactory::class,
            DomainEventDispatcherFactory::class => DomainEventDispatcherFactory::class,
        ];
    }
}
