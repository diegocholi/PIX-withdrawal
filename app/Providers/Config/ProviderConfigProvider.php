<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Config;

use Hyperf\Contract\ConfigInterface;

final readonly class ProviderConfigProvider
{
    public function __construct(private ConfigInterface $config)
    {
    }

    public function providerEnvironment(): string
    {
        return (string) $this->config->get('providers.runtime.environment', ProviderEnvironment::PROD);
    }

    /**
     * @return array<string, mixed>
     */
    public function providers(): array
    {
        /** @var array<string, mixed> $providers */
        $providers = $this->config->get('providers', []);

        return $providers;
    }

    /**
     * @return list<class-string>
     */
    public function requiredBindingsFor(string $runtime): array
    {
        /** @var list<class-string> $bindings */
        $bindings = $this->config->get(sprintf('providers.runtime.required_bindings.%s', $runtime), []);

        return $bindings;
    }

    public function observabilityDriver(): string
    {
        return (string) $this->config->get('providers.observability.driver', 'hyperf');
    }

    public function metricDriver(): string
    {
        return (string) $this->config->get('providers.metrics.driver', 'logger');
    }

    public function domainEventDriver(): string
    {
        return (string) $this->config->get('providers.domain_events.driver', 'kafka');
    }

    public function clockDriver(): string
    {
        return (string) $this->config->get('providers.clock.driver', 'system');
    }

    public function uuidDriver(): string
    {
        return (string) $this->config->get('providers.identifiers.uuid_driver', 'random');
    }

    public function withdrawDuplicateGuardWindowSeconds(): int
    {
        $value = (int) $this->config->get(
            'providers.identifiers.withdraw_duplicate_guard_window_seconds',
            60
        );

        return max(1, $value);
    }

    /**
     * @return array<string, mixed>
     */
    public function kafka(): array
    {
        /** @var array<string, mixed> $kafka */
        $kafka = $this->config->get('kafka', []);

        return $kafka;
    }

    /**
     * @return array<string, mixed>
     */
    public function mail(): array
    {
        /** @var array<string, mixed> $mail */
        $mail = $this->config->get('mail', []);

        return $mail;
    }
}
