<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Bootstrap;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\MetricEmitter;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\UuidGenerator;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;
use Tecnofit\PixWithdrawal\Plugins\Observability\HyperfObservability;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaDomainEventDispatcher;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpWithdrawMailer;

final class ProviderBootstrapValidConfigurationTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->clearEnvironmentOverrides();

        parent::tearDown();
    }

    public function testContainerInitializesProvidersModuleWithCoherentLocalConfiguration(): void
    {
        putenv('APP_ENV=local');
        putenv('KAFKA_BROKERS=kafka:19092,kafka-backup:19093');
        putenv('MAIL_HOST=mailhog');
        putenv('MAIL_PORT=1025');
        putenv('MAIL_FROM_ADDRESS=providers-test@pix.local');
        putenv('MAIL_FROM_NAME=Providers Test');
        putenv('MAIL_CONNECT_TIMEOUT_SECONDS=7');
        putenv('MAIL_READ_TIMEOUT_SECONDS=9');

        $container = (new HyperfContainerFactory())->create();
        $config = $container->get(ConfigInterface::class);
        $providerConfig = $container->get(ProviderConfigProvider::class);
        $kafkaConfig = $container->get(KafkaConfig::class);
        $mailConfig = $container->get(MailConfig::class);

        self::assertSame('local', $providerConfig->providerEnvironment());
        self::assertSame('hyperf', $providerConfig->observabilityDriver());
        self::assertSame('logger', $providerConfig->metricDriver());
        self::assertSame('kafka', $providerConfig->domainEventDriver());
        self::assertSame('system', $providerConfig->clockDriver());
        self::assertSame('random', $providerConfig->uuidDriver());

        self::assertSame(['kafka:19092', 'kafka-backup:19093'], $kafkaConfig->brokers());
        self::assertSame('pix-withdrawal-local', $kafkaConfig->clientId());
        self::assertSame('withdraw.process', $kafkaConfig->withdrawProcessTopic());
        self::assertSame('notification.email.withdraw', $kafkaConfig->notificationEmailWithdrawTopic());
        self::assertSame('pix-withdrawal-local-withdraw', $kafkaConfig->consumerGroup('withdraw'));
        self::assertSame('earliest', $kafkaConfig->consumerOptions('withdraw')['auto.offset.reset']);

        self::assertSame('smtp', $mailConfig->defaultMailer());
        self::assertSame('smtp', $mailConfig->transport());
        self::assertSame('mailhog', $mailConfig->host());
        self::assertSame(1025, $mailConfig->port());
        self::assertSame('providers-test@pix.local', $mailConfig->fromAddress());
        self::assertSame('Providers Test', $mailConfig->fromName());
        self::assertSame(7, $mailConfig->connectTimeoutSeconds());
        self::assertSame(9, $mailConfig->readTimeoutSeconds());

        self::assertInstanceOf(HyperfObservability::class, $container->get(Observability::class));
        self::assertSame($container->get(Observability::class), $container->get(StructuredLogger::class));
        self::assertSame($container->get(Observability::class), $container->get(MetricEmitter::class));
        self::assertInstanceOf(KafkaDomainEventDispatcher::class, $container->get(DomainEventDispatcher::class));
        self::assertInstanceOf(SmtpWithdrawMailer::class, $container->get(SmtpWithdrawMailer::class));
        self::assertInstanceOf(Clock::class, $container->get(Clock::class));
        self::assertInstanceOf(UuidGenerator::class, $container->get(UuidGenerator::class));

        self::assertSame('local', $config->get('providers.runtime.environment'));
        self::assertContains(DomainEventDispatcher::class, $providerConfig->requiredBindingsFor('worker'));
        self::assertContains(SmtpWithdrawMailer::class, $providerConfig->requiredBindingsFor('worker'));
        self::assertContains(DomainEventDispatcher::class, $providerConfig->requiredBindingsFor('scheduler'));
        self::assertNotContains(SmtpWithdrawMailer::class, $providerConfig->requiredBindingsFor('scheduler'));
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
