<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Bootstrap;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AtomicAccountDebit;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\CorrelationIdGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\EventPayloadSerializer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\LogPayloadSerializer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\MetricEmitter;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SensitiveDataMasker;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\UuidGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawDuplicateGuardFingerprintGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawDuplicateGuardWindow;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawIdempotencyKeyGenerator;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;
use Tecnofit\PixWithdrawal\Plugins\Observability\HyperfObservability;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;
use Tecnofit\PixWithdrawal\Providers\Bootstrap\ProviderBindingRegistry;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaDomainEventDispatcher;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpWithdrawMailer;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationDispatcher;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationKafkaHandler;
use Tecnofit\PixWithdrawal\Providers\Support\NullDomainEventDispatcher;

final class ProviderBindingRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('APP_ENV');
        putenv('APP_DEBUG');
        putenv('APP_CHARSET');
        putenv('APP_LOG_MAX_FILES');
        putenv('APP_LOG_LEVEL');
        putenv('APP_LOCALE');
        putenv('APP_FALLBACK_LOCALE');
        putenv('APP_NAME');
        putenv('APP_TIMEZONE');

        parent::tearDown();
    }

    public function testDefinitionsExposeCoreContractsExpectedByProvidersBacklog(): void
    {
        $definitions = ProviderBindingRegistry::definitions();

        self::assertArrayHasKey(AtomicAccountDebit::class, $definitions);
        self::assertArrayHasKey(Clock::class, $definitions);
        self::assertArrayHasKey(ClockInterface::class, $definitions);
        self::assertArrayHasKey(CorrelationIdGenerator::class, $definitions);
        self::assertArrayHasKey(KafkaConfig::class, $definitions);
        self::assertArrayHasKey(MailConfig::class, $definitions);
        self::assertArrayHasKey(SmtpWithdrawMailer::class, $definitions);
        self::assertArrayHasKey(WithdrawNotificationDispatcher::class, $definitions);
        self::assertArrayHasKey(WithdrawNotificationKafkaHandler::class, $definitions);
        self::assertArrayHasKey(ProviderConfigProvider::class, $definitions);
        self::assertArrayHasKey(UuidGenerator::class, $definitions);
        self::assertArrayHasKey(StructuredLogger::class, $definitions);
        self::assertArrayHasKey(MetricEmitter::class, $definitions);
        self::assertArrayHasKey(Observability::class, $definitions);
        self::assertArrayHasKey(SensitiveDataMasker::class, $definitions);
        self::assertArrayHasKey(LogPayloadSerializer::class, $definitions);
        self::assertArrayHasKey(EventPayloadSerializer::class, $definitions);
        self::assertArrayHasKey(DomainEventDispatcher::class, $definitions);
        self::assertArrayHasKey(WithdrawDuplicateGuardFingerprintGenerator::class, $definitions);
        self::assertArrayHasKey(WithdrawDuplicateGuardWindow::class, $definitions);
        self::assertArrayHasKey(WithdrawIdempotencyKeyGenerator::class, $definitions);
    }

    public function testContainerResolvesConcreteImplementationsRegisteredByProviderBindings(): void
    {
        $container = (new HyperfContainerFactory())->create();

        self::assertInstanceOf(KafkaConfig::class, $container->get(KafkaConfig::class));
        self::assertInstanceOf(MailConfig::class, $container->get(MailConfig::class));
        self::assertInstanceOf(SmtpWithdrawMailer::class, $container->get(SmtpWithdrawMailer::class));
        self::assertInstanceOf(WithdrawNotificationDispatcher::class, $container->get(WithdrawNotificationDispatcher::class));
        self::assertInstanceOf(WithdrawNotificationKafkaHandler::class, $container->get(WithdrawNotificationKafkaHandler::class));
        self::assertInstanceOf(ProviderConfigProvider::class, $container->get(ProviderConfigProvider::class));
        self::assertInstanceOf(KafkaDomainEventDispatcher::class, $container->get(DomainEventDispatcher::class));
        self::assertInstanceOf(HyperfObservability::class, $container->get(Observability::class));
        self::assertInstanceOf(SensitiveDataMasker::class, $container->get(SensitiveDataMasker::class));
        self::assertInstanceOf(LogPayloadSerializer::class, $container->get(LogPayloadSerializer::class));
        self::assertInstanceOf(EventPayloadSerializer::class, $container->get(EventPayloadSerializer::class));
        self::assertSame($container->get(Observability::class), $container->get(StructuredLogger::class));
        self::assertSame($container->get(Observability::class), $container->get(MetricEmitter::class));
        self::assertInstanceOf(ConfigInterface::class, $container->get(ConfigInterface::class));
    }

    public function testContainerSwitchesToNullDriversForTestEnvironment(): void
    {
        putenv('APP_ENV=test');

        $container = (new HyperfContainerFactory())->create();

        self::assertInstanceOf(NullDomainEventDispatcher::class, $container->get(DomainEventDispatcher::class));
        self::assertNotInstanceOf(HyperfObservability::class, $container->get(Observability::class));
        self::assertSame($container->get(Observability::class), $container->get(StructuredLogger::class));
        self::assertSame($container->get(Observability::class), $container->get(MetricEmitter::class));
    }
}
