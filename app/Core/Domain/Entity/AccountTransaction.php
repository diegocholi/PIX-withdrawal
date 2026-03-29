<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Entity;

use DateTimeImmutable;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionDirection;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionReferenceType;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidAccountTransaction;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;

final class AccountTransaction
{
    private function __construct(
        private string $accountId,
        private AccountTransactionReferenceType $referenceType,
        private string $referenceId,
        private AccountTransactionDirection $direction,
        private Money $amount,
        private Money $balanceBefore,
        private Money $balanceAfter,
        private DateTimeImmutable $createdAt,
    ) {
        $this->accountId = trim($this->accountId);
        $this->referenceId = trim($this->referenceId);

        $this->guardState();
    }

    public static function create(
        string $accountId,
        AccountTransactionReferenceType $referenceType,
        string $referenceId,
        AccountTransactionDirection $direction,
        Money $amount,
        Money $balanceBefore,
        Money $balanceAfter,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            accountId: $accountId,
            referenceType: $referenceType,
            referenceId: $referenceId,
            direction: $direction,
            amount: $amount,
            balanceBefore: $balanceBefore,
            balanceAfter: $balanceAfter,
            createdAt: $createdAt,
        );
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function referenceType(): AccountTransactionReferenceType
    {
        return $this->referenceType;
    }

    public function referenceId(): string
    {
        return $this->referenceId;
    }

    public function direction(): AccountTransactionDirection
    {
        return $this->direction;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function balanceBefore(): Money
    {
        return $this->balanceBefore;
    }

    public function balanceAfter(): Money
    {
        return $this->balanceAfter;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function guardState(): void
    {
        foreach (
            [
                'account_id' => $this->accountId,
                'reference_id' => $this->referenceId,
            ] as $field => $value
        ) {
            if ($value === '') {
                throw InvalidAccountTransaction::emptyField($field);
            }
        }

        if ($this->amount->isZero()) {
            throw InvalidAccountTransaction::zeroAmount();
        }

        $expectedBalanceAfter = $this->direction->apply($this->balanceBefore, $this->amount);

        if (! $expectedBalanceAfter->equals($this->balanceAfter)) {
            throw InvalidAccountTransaction::inconsistentBalanceFlow(
                $this->direction,
                $this->referenceType,
                $this->amount,
                $this->balanceBefore,
                $this->balanceAfter,
            );
        }
    }
}
