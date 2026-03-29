<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Factories;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Factories\KafkaConsumerFactory;

final class KafkaConsumerFactoryTest extends TestCase
{
    public function testRejectsUnknownConsumerGroup(): void
    {
        $factory = new KafkaConsumerFactory(
            KafkaConfig::fromArray([
                'brokers' => ['kafka:19092'],
                'client_id' => 'pix-withdrawal-local',
                'consumer_groups' => [],
                'operation_timeout_ms' => 1000,
                'producers' => [],
                'flush_timeout_ms' => 5000,
                'required_topic_keys' => [],
                'topics' => [
                    KafkaConfig::TOPIC_WITHDRAW_PROCESS => 'withdraw.process',
                ],
            ]),
            $this->createMock(StructuredLogger::class),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kafka consumer group "withdraw" is not configured.');

        $factory->create();
    }
}
