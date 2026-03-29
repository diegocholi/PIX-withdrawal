<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Diagnostic;

final readonly class KafkaConnectivityCheckResult
{
    public function __construct(
        private string $clientId,
        private int $configuredBrokersCount,
        private int $discoveredBrokersCount,
        private int $discoveredTopicsCount,
        private int $originBrokerId,
        private string $originBrokerName,
    ) {
    }

    public function clientId(): string
    {
        return $this->clientId;
    }

    public function configuredBrokersCount(): int
    {
        return $this->configuredBrokersCount;
    }

    public function discoveredBrokersCount(): int
    {
        return $this->discoveredBrokersCount;
    }

    public function discoveredTopicsCount(): int
    {
        return $this->discoveredTopicsCount;
    }

    public function originBrokerId(): int
    {
        return $this->originBrokerId;
    }

    public function originBrokerName(): string
    {
        return $this->originBrokerName;
    }
}
