<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Logging;

use Psr\Log\LoggerInterface;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\LogPayloadSerializer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

final readonly class HyperfStructuredLogger implements StructuredLogger
{
    public function __construct(
        private LoggerInterface $logger,
        private LogPayloadSerializer $logPayloadSerializer,
        private ProviderLogContextEnricher $providerLogContextEnricher,
    ) {
    }

    public function info(string $message, LogContext $context): void
    {
        $this->logger->info($message, $this->serialize($message, $context));
    }

    public function warning(string $message, LogContext $context): void
    {
        $this->logger->warning($message, $this->serialize($message, $context));
    }

    public function error(string $message, LogContext $context): void
    {
        $this->logger->error($message, $this->serialize($message, $context));
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(string $message, LogContext $context): array
    {
        return $this->logPayloadSerializer->serialize(
            $this->providerLogContextEnricher->enrich($message, $context)
        );
    }
}
