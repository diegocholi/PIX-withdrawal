<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Bootstrap;

use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\CorrelationIdGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\MetricEmitter;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\UuidGenerator;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderEnvironment;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpWithdrawMailer;

final class ProviderRuntimeBootstrap
{
    /**
     * @return array<string, mixed>
     */
    public function bootstrap(string $applicationEnvironment, int $withdrawDuplicateGuardWindowSeconds = 60): array
    {
        $environment = ProviderEnvironment::normalize($applicationEnvironment);

        return [
            'runtime' => [
                'environment' => $environment,
                'required_bindings' => $this->requiredBindings(),
            ],
            'clock' => [
                'driver' => $environment === ProviderEnvironment::TEST ? 'test' : 'system',
            ],
            'identifiers' => [
                'uuid_driver' => $environment === ProviderEnvironment::TEST ? 'fake' : 'random',
                'withdraw_duplicate_guard_window_seconds' => max(1, $withdrawDuplicateGuardWindowSeconds),
            ],
            'observability' => [
                'driver' => $environment === ProviderEnvironment::TEST ? 'null' : 'hyperf',
            ],
            'metrics' => [
                'driver' => $environment === ProviderEnvironment::TEST ? 'null' : 'logger',
            ],
            'domain_events' => [
                'driver' => $environment === ProviderEnvironment::TEST ? 'null' : 'kafka',
            ],
        ];
    }

    /**
     * @return array<string, list<class-string>>
     */
    private function requiredBindings(): array
    {
        return [
            'http' => [
                Clock::class,
                CorrelationIdGenerator::class,
                Observability::class,
                StructuredLogger::class,
                MetricEmitter::class,
            ],
            'cli' => [
                Clock::class,
                UuidGenerator::class,
                Observability::class,
                StructuredLogger::class,
                MetricEmitter::class,
            ],
            'worker' => [
                Clock::class,
                UuidGenerator::class,
                Observability::class,
                StructuredLogger::class,
                MetricEmitter::class,
                DomainEventDispatcher::class,
                SmtpWithdrawMailer::class,
            ],
            'scheduler' => [
                Clock::class,
                UuidGenerator::class,
                Observability::class,
                StructuredLogger::class,
                MetricEmitter::class,
                DomainEventDispatcher::class,
            ],
        ];
    }
}
