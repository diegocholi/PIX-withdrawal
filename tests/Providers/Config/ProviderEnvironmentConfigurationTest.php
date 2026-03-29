<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Config;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

final class ProviderEnvironmentConfigurationTest extends TestCase
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
        putenv('KAFKA_BROKERS');
        putenv('MAIL_HOST');
        putenv('MAIL_PORT');
        putenv('MAIL_USERNAME');
        putenv('MAIL_PASSWORD');
        putenv('MAIL_ENCRYPTION');
        putenv('MAIL_FROM_ADDRESS');
        putenv('MAIL_FROM_NAME');
        putenv('MAIL_CONNECT_TIMEOUT_SECONDS');
        putenv('MAIL_READ_TIMEOUT_SECONDS');

        parent::tearDown();
    }

    public function testLocalEnvironmentLoadsProviderFriendlyKafkaAndMailDefaults(): void
    {
        putenv('APP_ENV=local');
        putenv('KAFKA_BROKERS=kafka:19092');
        putenv('MAIL_HOST=mailhog');
        putenv('MAIL_PORT=1025');
        putenv('MAIL_FROM_ADDRESS=no-reply@example.test');
        putenv('MAIL_FROM_NAME=PIX Withdrawal Local');
        putenv('MAIL_CONNECT_TIMEOUT_SECONDS=3');
        putenv('MAIL_READ_TIMEOUT_SECONDS=7');

        $container = (new HyperfContainerFactory())->create();
        $config = $container->get(ConfigInterface::class);

        self::assertSame('local', $config->get('providers.runtime.environment'));
        self::assertSame(['kafka:19092'], $config->get('kafka.brokers'));
        self::assertSame('pix-withdrawal-local', $config->get('kafka.client_id'));
        self::assertSame('smtp', $config->get('mail.default'));
        self::assertSame('mailhog', $config->get('mail.mailers.smtp.host'));
        self::assertSame(1025, $config->get('mail.mailers.smtp.port'));
        self::assertSame('no-reply@example.test', $config->get('mail.mailers.smtp.from.address'));
        self::assertSame('PIX Withdrawal Local', $config->get('mail.mailers.smtp.from.name'));
        self::assertSame(3, $config->get('mail.mailers.smtp.timeouts.connect_seconds'));
        self::assertSame(7, $config->get('mail.mailers.smtp.timeouts.read_seconds'));
    }

    public function testTestEnvironmentDisablesExternalDefaultsForKafkaAndMail(): void
    {
        putenv('APP_ENV=test');
        putenv('KAFKA_BROKERS');
        putenv('MAIL_HOST');
        putenv('MAIL_PORT');
        putenv('MAIL_USERNAME');
        putenv('MAIL_PASSWORD');
        putenv('MAIL_ENCRYPTION');
        putenv('MAIL_FROM_ADDRESS');
        putenv('MAIL_FROM_NAME');
        putenv('MAIL_CONNECT_TIMEOUT_SECONDS');
        putenv('MAIL_READ_TIMEOUT_SECONDS');

        $container = (new HyperfContainerFactory())->create();
        $config = $container->get(ConfigInterface::class);

        self::assertSame('test', $config->get('providers.runtime.environment'));
        self::assertSame([], $config->get('kafka.brokers'));
        self::assertSame('pix-withdrawal-test', $config->get('kafka.client_id'));
        self::assertNull($config->get('mail.default'));
        self::assertSame([], $config->get('mail.mailers'));
    }
}
