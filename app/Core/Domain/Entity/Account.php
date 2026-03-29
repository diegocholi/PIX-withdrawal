<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Entity;

use DateTimeImmutable;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidAccount;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InsufficientAccountBalance;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Shared\Result;

final class Account
{
    private function __construct(
        private string $id,
        private string $name,
        private Money $balance,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
        $this->id = trim($this->id);
        $this->name = trim($this->name);

        $this->guardState();
    }

    public static function create(
        string $id,
        string $name,
        Money $balance,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            balance: $balance,
            createdAt: $createdAt,
            updatedAt: $createdAt,
        );
    }

    public static function reconstitute(
        string $id,
        string $name,
        Money $balance,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $name, $balance, $createdAt, $updatedAt);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function balance(): Money
    {
        return $this->balance;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function hasBalance(): bool
    {
        return ! $this->balance->isZero();
    }

    public function debit(Money $amount, DateTimeImmutable $updatedAt): Result
    {
        if ($updatedAt < $this->updatedAt) {
            throw InvalidAccount::staleUpdate($this->updatedAt, $updatedAt);
        }

        if ($this->balance->lessThan($amount)) {
            return Result::failure(
                InsufficientAccountBalance::forDebit($this->id, $this->balance, $amount)
            );
        }

        $this->balance = $this->balance->subtract($amount);
        $this->updatedAt = $updatedAt;

        return Result::success($this);
    }

    private function guardState(): void
    {
        if ($this->id === '') {
            throw InvalidAccount::emptyId();
        }

        if ($this->name === '') {
            throw InvalidAccount::emptyName();
        }

        if ($this->updatedAt < $this->createdAt) {
            throw InvalidAccount::inconsistentTimestamps($this->createdAt, $this->updatedAt);
        }
    }
}
