<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Enum;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionDirection;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;

final class AccountTransactionDirectionTest extends TestCase
{
    public function testDirectionEnumeratesDebitAndCredit(): void
    {
        self::assertSame(['DEBIT', 'CREDIT'], AccountTransactionDirection::values());
        self::assertTrue(AccountTransactionDirection::DEBIT->isDebit());
        self::assertTrue(AccountTransactionDirection::CREDIT->isCredit());
    }

    public function testDirectionAppliesBalanceFlow(): void
    {
        self::assertTrue(
            AccountTransactionDirection::DEBIT
                ->apply(Money::fromDecimal('100.00'), Money::fromDecimal('25.00'))
                ->equals(Money::fromDecimal('75.00'))
        );

        self::assertTrue(
            AccountTransactionDirection::CREDIT
                ->apply(Money::fromDecimal('100.00'), Money::fromDecimal('25.00'))
                ->equals(Money::fromDecimal('125.00'))
        );
    }
}
