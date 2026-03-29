<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Seeds;

use DateTimeImmutable;

final readonly class SeedExecutionResult
{
    private function __construct(
        private string $seedName,
        private string $status,
        private DateTimeImmutable $executedAt,
    ) {
    }

    public static function applied(string $seedName, DateTimeImmutable $executedAt): self
    {
        return new self(trim($seedName), 'applied', $executedAt);
    }

    public static function skipped(string $seedName, DateTimeImmutable $executedAt): self
    {
        return new self(trim($seedName), 'skipped', $executedAt);
    }

    public function seedName(): string
    {
        return $this->seedName;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function executedAt(): DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function wasApplied(): bool
    {
        return $this->status === 'applied';
    }

    public function wasSkipped(): bool
    {
        return $this->status === 'skipped';
    }
}
