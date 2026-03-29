<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Worker;

final readonly class WorkerRuntimeOptions
{
    public function __construct(
        private int $maxMessages,
        private string $groupId,
        private string $topic,
        private int $pollTimeoutMs,
        private string $correlationId,
    ) {
    }

    public function maxMessages(): int
    {
        return $this->maxMessages;
    }

    public function groupId(): string
    {
        return $this->groupId;
    }

    public function topic(): string
    {
        return $this->topic;
    }

    public function pollTimeoutMs(): int
    {
        return $this->pollTimeoutMs;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }
}
