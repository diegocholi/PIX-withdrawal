<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Config;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfigValidator;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;

final class KafkaConfigValidatorTest extends TestCase
{
    public function testRejectsMissingClientId(): void
    {
        $validator = new KafkaConfigValidator($this->providerConfig('local'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kafka client_id configuration must be non-empty.');

        $validator->validate(KafkaConfig::fromArray([
            'brokers' => ['kafka:19092'],
            'client_id' => '',
        ]));
    }

    public function testRejectsMissingRequiredTopicsOutsideTestEnvironment(): void
    {
        $validator = new KafkaConfigValidator($this->providerConfig('local'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kafka topics configuration must define required topics: withdraw_process.');

        $validator->validate(KafkaConfig::fromArray([
            'brokers' => ['kafka:19092'],
            'client_id' => 'pix-withdrawal-local',
            'required_topic_keys' => ['withdraw_process'],
            'topics' => [],
        ]));
    }

    public function testAllowsEmptyBrokersInTestEnvironment(): void
    {
        $validator = new KafkaConfigValidator($this->providerConfig('test'));

        $validator->validate(KafkaConfig::fromArray([
            'brokers' => [],
            'client_id' => 'pix-withdrawal-test',
        ]));

        self::assertTrue(true);
    }

    private function providerConfig(string $environment): ProviderConfigProvider
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')->willReturnMap([
            ['providers.runtime.environment', 'prod', $environment],
        ]);

        return new ProviderConfigProvider($config);
    }
}
