<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Psr\Log\LoggerInterface;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\LogPayloadSerializer;
use Tecnofit\PixWithdrawal\Providers\Logging\HyperfStructuredLogger;
use Tecnofit\PixWithdrawal\Providers\Logging\ProviderLogContextEnricher;

final readonly class HyperfStructuredLoggerFactory
{
    public function __construct(
        private LoggerInterface $logger,
        private LogPayloadSerializer $logPayloadSerializer,
        private ProviderLogContextEnricher $providerLogContextEnricher,
    ) {
    }

    public function create(): HyperfStructuredLogger
    {
        return new HyperfStructuredLogger(
            $this->logger,
            $this->logPayloadSerializer,
            $this->providerLogContextEnricher,
        );
    }
}
