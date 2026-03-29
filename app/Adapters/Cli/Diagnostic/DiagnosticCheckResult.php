<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Diagnostic;

final readonly class DiagnosticCheckResult
{
    /**
     * @param array<string, scalar|array<array-key, mixed>|null> $details
     */
    private function __construct(
        private string $check,
        private string $status,
        private array $details,
        private ?string $message,
    ) {
    }

    /**
     * @param array<string, scalar|array<array-key, mixed>|null> $details
     */
    public static function ok(string $check, array $details): self
    {
        return new self(
            check: $check,
            status: 'ok',
            details: $details,
            message: null,
        );
    }

    public static function failed(string $check, string $message): self
    {
        return new self(
            check: $check,
            status: 'failed',
            details: [],
            message: $message,
        );
    }

    public function check(): string
    {
        return $this->check;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * @return array<string, scalar|array<array-key, mixed>|null>
     */
    public function toArray(): array
    {
        return array_filter([
            'check' => $this->check,
            'status' => $this->status,
            'message' => $this->message,
        ] + $this->details, static fn (mixed $value): bool => $value !== null);
    }
}
