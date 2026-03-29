<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Exception;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InsufficientAccountBalance;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;

final class InsufficientAccountBalanceTest extends TestCase
{
    public function testForDebitCarriesStableMetadata(): void
    {
        $exception = InsufficientAccountBalance::forDebit(
            'acc-1',
            Money::fromDecimal('10.00'),
            Money::fromDecimal('12.50'),
        );

        self::assertSame('account.insufficient_balance', $exception->errorCode());
        self::assertSame(
            [
                'account_id' => 'acc-1',
                'current_balance' => '10.00',
                'debit_amount' => '12.50',
            ],
            $exception->context()
        );
    }
}
