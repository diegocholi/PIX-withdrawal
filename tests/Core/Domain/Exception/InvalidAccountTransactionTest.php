<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Exception;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionDirection;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionReferenceType;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidAccountTransaction;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;

final class InvalidAccountTransactionTest extends TestCase
{
    public function testEmptyFieldFactoryCarriesStableMetadata(): void
    {
        $exception = InvalidAccountTransaction::emptyField('reference_id');

        self::assertSame('account_transaction.empty_field', $exception->errorCode());
        self::assertSame(['field' => 'reference_id'], $exception->context());
    }

    public function testZeroAmountFactoryCarriesStableMetadata(): void
    {
        $exception = InvalidAccountTransaction::zeroAmount();

        self::assertSame('account_transaction.zero_amount', $exception->errorCode());
        self::assertSame([], $exception->context());
    }

    public function testInconsistentBalanceFlowFactoryCarriesStableMetadata(): void
    {
        $exception = InvalidAccountTransaction::inconsistentBalanceFlow(
            AccountTransactionDirection::DEBIT,
            AccountTransactionReferenceType::WITHDRAW,
            Money::fromDecimal('25.00'),
            Money::fromDecimal('100.00'),
            Money::fromDecimal('80.00'),
        );

        self::assertSame('account_transaction.inconsistent_balance_flow', $exception->errorCode());
        self::assertSame(
            [
                'direction' => 'DEBIT',
                'reference_type' => 'WITHDRAW',
                'amount' => '25.00',
                'balance_before' => '100.00',
                'balance_after' => '80.00',
            ],
            $exception->context()
        );
    }
}
