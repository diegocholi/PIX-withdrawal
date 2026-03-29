<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Enum;

use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;

enum AccountTransactionDirection: string
{
    case DEBIT = 'DEBIT';
    case CREDIT = 'CREDIT';

    public function isDebit(): bool
    {
        return $this === self::DEBIT;
    }

    public function isCredit(): bool
    {
        return $this === self::CREDIT;
    }

    public function apply(Money $balanceBefore, Money $amount): Money
    {
        return match ($this) {
            self::DEBIT => $balanceBefore->subtract($amount),
            self::CREDIT => $balanceBefore->add($amount),
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $direction): string => $direction->value,
            self::cases()
        );
    }
}
