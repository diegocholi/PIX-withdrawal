<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Support;

final class CliExecutionContext
{
    private ?string $correlationId = null;

    public function activate(string $correlationId): void
    {
        $normalized = trim($correlationId);

        if ($normalized === '') {
            throw new \InvalidArgumentException('CLI execution correlation id must be non-empty.');
        }

        $this->correlationId = $normalized;
    }

    public function clear(): void
    {
        $this->correlationId = null;
    }

    public function correlationId(): ?string
    {
        return $this->correlationId;
    }

    public function requireCorrelationId(): string
    {
        if ($this->correlationId === null) {
            throw new \RuntimeException('CLI execution correlation id is not active.');
        }

        return $this->correlationId;
    }
}
