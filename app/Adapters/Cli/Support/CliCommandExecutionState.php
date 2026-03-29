<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Support;

final readonly class CliCommandExecutionState
{
    public function __construct(
        private string $commandName,
        private string $commandClass,
        private string $correlationId,
        private int $startedAtNanoseconds,
        private ?string $errorMessage = null,
    ) {
    }

    public function commandName(): string
    {
        return $this->commandName;
    }

    public function commandClass(): string
    {
        return $this->commandClass;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function startedAtNanoseconds(): int
    {
        return $this->startedAtNanoseconds;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function withErrorMessage(string $errorMessage): self
    {
        return new self(
            commandName: $this->commandName,
            commandClass: $this->commandClass,
            correlationId: $this->correlationId,
            startedAtNanoseconds: $this->startedAtNanoseconds,
            errorMessage: trim($errorMessage) === '' ? null : trim($errorMessage),
        );
    }
}
