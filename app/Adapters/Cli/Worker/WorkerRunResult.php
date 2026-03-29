<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Worker;

final readonly class WorkerRunResult
{
    public function __construct(
        private int $processedMessages,
        private bool $interrupted,
    ) {
    }

    public function processedMessages(): int
    {
        return $this->processedMessages;
    }

    public function interrupted(): bool
    {
        return $this->interrupted;
    }
}
