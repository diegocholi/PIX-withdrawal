<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Config;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;

final class ProviderConfigProviderTest extends TestCase
{
    public function testReadsProviderConfigurationThroughPredictableMethods(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')->willReturnMap([
            ['providers.runtime.environment', 'prod', 'local'],
            ['providers', [], [
                'runtime' => [
                    'environment' => 'local',
                    'required_bindings' => [
                        'worker' => ['WorkerBinding'],
                    ],
                ],
                'clock' => ['driver' => 'system'],
                'identifiers' => [
                    'uuid_driver' => 'random',
                    'withdraw_duplicate_guard_window_seconds' => 90,
                ],
                'observability' => ['driver' => 'hyperf'],
                'metrics' => ['driver' => 'logger'],
                'domain_events' => ['driver' => 'kafka'],
            ]],
            ['providers.runtime.required_bindings.worker', [], ['WorkerBinding']],
            ['providers.clock.driver', 'system', 'system'],
            ['providers.identifiers.uuid_driver', 'random', 'random'],
            ['providers.identifiers.withdraw_duplicate_guard_window_seconds', 60, 90],
            ['providers.observability.driver', 'hyperf', 'hyperf'],
            ['providers.metrics.driver', 'logger', 'logger'],
            ['providers.domain_events.driver', 'kafka', 'kafka'],
            ['kafka', [], ['brokers' => ['kafka:19092']]],
            ['mail', [], ['default' => 'smtp']],
        ]);

        $providerConfig = new ProviderConfigProvider($config);

        self::assertSame('local', $providerConfig->providerEnvironment());
        self::assertSame(['WorkerBinding'], $providerConfig->requiredBindingsFor('worker'));
        self::assertSame('system', $providerConfig->clockDriver());
        self::assertSame('random', $providerConfig->uuidDriver());
        self::assertSame(90, $providerConfig->withdrawDuplicateGuardWindowSeconds());
        self::assertSame('hyperf', $providerConfig->observabilityDriver());
        self::assertSame('logger', $providerConfig->metricDriver());
        self::assertSame('kafka', $providerConfig->domainEventDriver());
        self::assertSame(['brokers' => ['kafka:19092']], $providerConfig->kafka());
        self::assertSame(['default' => 'smtp'], $providerConfig->mail());
        self::assertArrayHasKey('runtime', $providerConfig->providers());
    }

    public function testReturnsSafeDefaultsWhenProviderConfigurationIsMissing(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')->willReturnCallback(
            static fn (string $key, mixed $default = null): mixed => $default
        );

        $providerConfig = new ProviderConfigProvider($config);

        self::assertSame('prod', $providerConfig->providerEnvironment());
        self::assertSame([], $providerConfig->providers());
        self::assertSame([], $providerConfig->requiredBindingsFor('http'));
        self::assertSame('system', $providerConfig->clockDriver());
        self::assertSame('random', $providerConfig->uuidDriver());
        self::assertSame(60, $providerConfig->withdrawDuplicateGuardWindowSeconds());
        self::assertSame('hyperf', $providerConfig->observabilityDriver());
        self::assertSame('logger', $providerConfig->metricDriver());
        self::assertSame('kafka', $providerConfig->domainEventDriver());
        self::assertSame([], $providerConfig->kafka());
        self::assertSame([], $providerConfig->mail());
    }
}
