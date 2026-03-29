<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Bootstrap;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;

final class ProviderBootstrapInvalidConfigurationTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->clearEnvironmentOverrides();

        parent::tearDown();
    }

    public function testBootstrapFailsFastWhenKafkaBrokersAreMissingOutsideTestEnvironment(): void
    {
        putenv('APP_ENV=local');
        putenv('KAFKA_BROKERS=');

        $container = (new HyperfContainerFactory())->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Kafka brokers configuration must define at least one broker outside test environment.'
        );

        $container->get(KafkaConfig::class);
    }

    public function testBootstrapFailsFastWhenSmtpSenderAddressIsMissingOutsideTestEnvironment(): void
    {
        putenv('APP_ENV=local');
        putenv('MAIL_FROM_ADDRESS=');

        $container = (new HyperfContainerFactory())->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'SMTP default sender address must be non-empty outside test environment.'
        );

        $container->get(MailConfig::class);
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
