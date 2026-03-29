<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Diagnostic;

final readonly class DiagnosticReport
{
    /**
     * @param list<DiagnosticCheckResult> $checks
     */
    public function __construct(private array $checks)
    {
    }

    public function totalChecks(): int
    {
        return count($this->checks);
    }

    public function okCount(): int
    {
        return count(array_filter(
            $this->checks,
            static fn (DiagnosticCheckResult $check): bool => $check->status() === 'ok',
        ));
    }

    public function failedCount(): int
    {
        return count(array_filter(
            $this->checks,
            static fn (DiagnosticCheckResult $check): bool => $check->hasFailed(),
        ));
    }

    public function hasFailures(): bool
    {
        return $this->failedCount() > 0;
    }

    public function hasPartialFailure(): bool
    {
        return $this->okCount() > 0 && $this->hasFailures();
    }

    public function status(): string
    {
        if (! $this->hasFailures()) {
            return 'ok';
        }

        return $this->hasPartialFailure() ? 'partial' : 'failed';
    }

    /**
     * @return array<string, array<string, scalar|array<array-key, mixed>|null>>
     */
    public function checksByName(): array
    {
        $checks = [];

        foreach ($this->checks as $check) {
            $checks[$check->check()] = $check->toArray();
        }

        return $checks;
    }
}
