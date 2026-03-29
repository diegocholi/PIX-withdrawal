<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Factories;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfigValidator;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;
use Tecnofit\PixWithdrawal\Providers\Factories\KafkaConfigFactory;

final class KafkaConfigFactoryTest extends TestCase
{
    public function testCreatesKafkaConfigFromCentralProviderConfiguration(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')->willReturnMap([
            ['providers.runtime.environment', 'prod', 'local'],
            ['kafka', [], [
                'brokers' => ['kafka:19092'],
                'client_id' => 'pix-withdrawal-local',
                'required_topic_keys' => [
                    KafkaConfig::TOPIC_WITHDRAW_PROCESS,
                    KafkaConfig::TOPIC_WITHDRAW_SUCCEEDED,
                    KafkaConfig::TOPIC_WITHDRAW_FAILED,
                    KafkaConfig::TOPIC_NOTIFICATION_EMAIL_WITHDRAW,
                ],
                'consumer_groups' => ['withdraw' => 'withdraw-local'],
                'producers' => ['domain_events' => ['acks' => 'all']],
                'topics' => [
                    KafkaConfig::TOPIC_WITHDRAW_PROCESS => 'withdraw.process',
                    KafkaConfig::TOPIC_WITHDRAW_SUCCEEDED => 'withdraw.succeeded',
                    KafkaConfig::TOPIC_WITHDRAW_FAILED => 'withdraw.failed',
                    KafkaConfig::TOPIC_NOTIFICATION_EMAIL_WITHDRAW => 'notification.email.withdraw',
                ],
                'operation_timeout_ms' => 2000,
                'flush_timeout_ms' => 6000,
            ]],
        ]);

        $providerConfigProvider = new ProviderConfigProvider($config);
        $logger = $this->createMock(StructuredLogger::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                'providers.bootstrap.kafka.loaded',
                self::callback(static function (LogContext $context): bool {
                    return $context->context()['environment'] === 'local'
                        && $context->context()['brokers_count'] === 1
                        && $context->context()['producer_keys'] === ['domain_events']
                        && $context->context()['consumer_group_keys'] === ['withdraw'];
                }),
            );

        $factory = new KafkaConfigFactory(
            $providerConfigProvider,
            new KafkaConfigValidator($providerConfigProvider),
            $logger,
        );
        $kafkaConfig = $factory->create();

        self::assertInstanceOf(KafkaConfig::class, $kafkaConfig);
        self::assertSame(['kafka:19092'], $kafkaConfig->brokers());
        self::assertSame('pix-withdrawal-local', $kafkaConfig->clientId());
        self::assertSame(
            [
                KafkaConfig::TOPIC_WITHDRAW_PROCESS,
                KafkaConfig::TOPIC_WITHDRAW_SUCCEEDED,
                KafkaConfig::TOPIC_WITHDRAW_FAILED,
                KafkaConfig::TOPIC_NOTIFICATION_EMAIL_WITHDRAW,
            ],
            $kafkaConfig->requiredTopicKeys()
        );
        self::assertSame('withdraw.process', $kafkaConfig->withdrawProcessTopic());
        self::assertSame('notification.email.withdraw', $kafkaConfig->notificationEmailWithdrawTopic());
        self::assertSame(2000, $kafkaConfig->operationTimeoutMs());
        self::assertSame(6000, $kafkaConfig->flushTimeoutMs());
    }

    public function testFailsFastWhenKafkaBrokersAreMissingOutsideTestEnvironment(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')->willReturnMap([
            ['providers.runtime.environment', 'prod', 'local'],
            ['kafka', [], [
                'brokers' => [],
                'client_id' => 'pix-withdrawal-local',
                'required_topic_keys' => [],
                'consumer_groups' => [],
                'producers' => [],
                'topics' => [],
            ]],
        ]);

        $providerConfigProvider = new ProviderConfigProvider($config);
        $logger = $this->createMock(StructuredLogger::class);
        $logger->expects(self::never())->method('info');
        $factory = new KafkaConfigFactory(
            $providerConfigProvider,
            new KafkaConfigValidator($providerConfigProvider),
            $logger,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kafka brokers configuration must define at least one broker outside test environment.');

        $factory->create();
    }
}
