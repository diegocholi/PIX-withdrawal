<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Factories;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tecnofit\PixWithdrawal\Plugins\Observability\HyperfObservability;
use Tecnofit\PixWithdrawal\Plugins\Observability\NullObservability;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;
use Tecnofit\PixWithdrawal\Providers\Factories\ObservabilityFactory;
use Tecnofit\PixWithdrawal\Providers\Logging\HyperfStructuredLogger;
use Tecnofit\PixWithdrawal\Providers\Logging\ProviderLogContextEnricher;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderPayloadNormalizer;
use Tecnofit\PixWithdrawal\Providers\Metrics\NullMetricEmitter;
use Tecnofit\PixWithdrawal\Providers\Metrics\ProviderMetricEmitter;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderSensitiveDataMasker;
use Tecnofit\PixWithdrawal\Providers\Serialization\SafeLogPayloadSerializer;

final class ObservabilityFactoryTest extends TestCase
{
    public function testCreatesHyperformObservabilityWhenDriverIsHyperform(): void
    {
        $factory = new ObservabilityFactory(
            $this->providerConfig('hyperf'),
            new HyperfStructuredLogger(
                $this->createMock(LoggerInterface::class),
                new SafeLogPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer()),
                new ProviderLogContextEnricher()
            ),
            new ProviderMetricEmitter($this->createMock(LoggerInterface::class)),
            new NullMetricEmitter(),
            new NullObservability()
        );

        self::assertInstanceOf(HyperfObservability::class, $factory->create());
    }

    public function testCreatesHyperformObservabilityWithNoOpMetricsFallback(): void
    {
        $metricLogger = $this->createMock(LoggerInterface::class);
        $metricLogger->expects(self::never())->method('info');

        $factory = new ObservabilityFactory(
            $this->providerConfig('hyperf', 'null'),
            new HyperfStructuredLogger(
                $this->createMock(LoggerInterface::class),
                new SafeLogPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer()),
                new ProviderLogContextEnricher()
            ),
            new ProviderMetricEmitter($metricLogger),
            new NullMetricEmitter(),
            new NullObservability()
        );

        $factory->create()->increment(
            new \Tecnofit\PixWithdrawal\Core\Shared\MetricPoint(
                \Tecnofit\PixWithdrawal\Core\Shared\MetricName::WITHDRAW_PROCESSED,
                1
            )
        );
    }

    public function testCreatesNullObservabilityWhenDriverIsNull(): void
    {
        $nullObservability = new NullObservability();
        $factory = new ObservabilityFactory(
            $this->providerConfig('null'),
            new HyperfStructuredLogger(
                $this->createMock(LoggerInterface::class),
                new SafeLogPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer()),
                new ProviderLogContextEnricher()
            ),
            new ProviderMetricEmitter($this->createMock(LoggerInterface::class)),
            new NullMetricEmitter(),
            $nullObservability
        );

        self::assertSame($nullObservability, $factory->create());
    }

    public function testFailsFastWhenObservabilityDriverIsUnsupported(): void
    {
        $factory = new ObservabilityFactory(
            $this->providerConfig('custom'),
            new HyperfStructuredLogger(
                $this->createMock(LoggerInterface::class),
                new SafeLogPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer()),
                new ProviderLogContextEnricher()
            ),
            new ProviderMetricEmitter($this->createMock(LoggerInterface::class)),
            new NullMetricEmitter(),
            new NullObservability()
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported providers.observability.driver "custom".');

        $factory->create();
    }

    public function testFailsFastWhenMetricDriverIsUnsupported(): void
    {
        $factory = new ObservabilityFactory(
            $this->providerConfig('hyperf', 'custom'),
            new HyperfStructuredLogger(
                $this->createMock(LoggerInterface::class),
                new SafeLogPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer()),
                new ProviderLogContextEnricher()
            ),
            new ProviderMetricEmitter($this->createMock(LoggerInterface::class)),
            new NullMetricEmitter(),
            new NullObservability()
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported providers.metrics.driver "custom".');

        $factory->create();
    }

    private function providerConfig(string $driver, string $metricDriver = 'logger'): ProviderConfigProvider
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')->willReturnMap([
            ['providers.observability.driver', 'hyperf', $driver],
            ['providers.metrics.driver', 'logger', $metricDriver],
        ]);

        return new ProviderConfigProvider($config);
    }
}
