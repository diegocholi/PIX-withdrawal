<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Factories;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Factories\KafkaProducerFactory;

final class KafkaProducerFactoryTest extends TestCase
{
    public function testRejectsUnknownNamedProducer(): void
    {
        $factory = new KafkaProducerFactory(
            KafkaConfig::fromArray([
                'brokers' => ['kafka:19092'],
                'client_id' => 'pix-withdrawal-local',
                'consumer_groups' => [],
                'operation_timeout_ms' => 1000,
                'flush_timeout_ms' => 5000,
                'required_topic_keys' => [],
                'producers' => ['domain_events' => ['acks' => 'all']],
                'topics' => [],
            ]),
            $this->createMock(StructuredLogger::class),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kafka producer "unknown" is not configured.');

        $factory->create('unknown');
    }
}
