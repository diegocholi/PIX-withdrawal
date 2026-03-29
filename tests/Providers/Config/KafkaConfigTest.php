<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Config;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;

final class KafkaConfigTest extends TestCase
{
    public function testCreatesTypedKafkaConfigFromArray(): void
    {
        $config = KafkaConfig::fromArray([
            'brokers' => [' kafka:19092 ', '', 'kafka:29092'],
            'client_id' => ' pix-withdrawal-local ',
            'required_topic_keys' => [KafkaConfig::TOPIC_WITHDRAW_PROCESS, KafkaConfig::TOPIC_WITHDRAW_FAILED],
            'consumer_groups' => ['withdraw' => 'withdraw-local', 'notification' => 'notification-local'],
            'producers' => ['domain_events' => ['acks' => 'all']],
            'topics' => [
                ' withdraw_process ' => ' withdraw.process ',
                KafkaConfig::TOPIC_WITHDRAW_FAILED => ' withdraw.failed ',
            ],
            'operation_timeout_ms' => 1500,
            'flush_timeout_ms' => 7000,
        ]);

        self::assertSame(['kafka:19092', 'kafka:29092'], $config->brokers());
        self::assertSame('pix-withdrawal-local', $config->clientId());
        self::assertSame([KafkaConfig::TOPIC_WITHDRAW_PROCESS, KafkaConfig::TOPIC_WITHDRAW_FAILED], $config->requiredTopicKeys());
        self::assertSame(['withdraw' => 'withdraw-local', 'notification' => 'notification-local'], $config->consumerGroups());
        self::assertSame('withdraw-local', $config->consumerGroup('withdraw'));
        self::assertSame('notification-local', $config->consumerGroup('notification'));
        self::assertSame([], $config->consumerOptions('withdraw'));
        self::assertSame([], $config->consumerOptions('notification'));
        self::assertSame(['domain_events' => ['acks' => 'all']], $config->producers());
        self::assertSame(
            [
                KafkaConfig::TOPIC_WITHDRAW_PROCESS => 'withdraw.process',
                KafkaConfig::TOPIC_WITHDRAW_FAILED => 'withdraw.failed',
            ],
            $config->topics()
        );
        self::assertSame('withdraw.process', $config->withdrawProcessTopic());
        self::assertSame('withdraw.failed', $config->withdrawFailedTopic());
        self::assertSame(1500, $config->operationTimeoutMs());
        self::assertSame(7000, $config->flushTimeoutMs());
    }

    public function testAppliesSafeDefaultsWhenFieldsAreMissing(): void
    {
        $config = KafkaConfig::fromArray([]);

        self::assertSame([], $config->brokers());
        self::assertSame('', $config->clientId());
        self::assertSame([], $config->consumerGroups());
        self::assertSame([], $config->producers());
        self::assertSame([], $config->topics());
        self::assertSame(1000, $config->operationTimeoutMs());
        self::assertSame(5000, $config->flushTimeoutMs());
        self::assertSame(
            [
                'brokers' => [],
                'client_id' => '',
                'required_topic_keys' => [],
                'consumer_groups' => [],
                'producers' => [],
                'topics' => [],
                'operation_timeout_ms' => 1000,
                'flush_timeout_ms' => 5000,
            ],
            $config->toArray()
        );
    }

    public function testRejectsUnknownTopicLookup(): void
    {
        $config = KafkaConfig::fromArray([
            'topics' => [
                KafkaConfig::TOPIC_WITHDRAW_PROCESS => 'withdraw.process',
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kafka topic "withdraw_failed" is not configured.');

        $config->withdrawFailedTopic();
    }

    public function testReturnsTypedConsumerGroupOptionsWhenDefinitionUsesArrayShape(): void
    {
        $config = KafkaConfig::fromArray([
            'consumer_groups' => [
                'withdraw' => [
                    'group_id' => 'withdraw-local',
                    'auto.offset.reset' => 'earliest',
                    'enable.partition.eof' => 'true',
                ],
            ],
        ]);

        self::assertSame('withdraw-local', $config->consumerGroup('withdraw'));
        self::assertSame(
            [
                'auto.offset.reset' => 'earliest',
                'enable.partition.eof' => 'true',
            ],
            $config->consumerOptions('withdraw')
        );
    }
}
