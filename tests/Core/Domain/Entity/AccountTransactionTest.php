<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountTransaction;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionDirection;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionReferenceType;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidAccountTransaction;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;

final class AccountTransactionTest extends TestCase
{
    public function testAccountTransactionCanRepresentWithdrawDebitAuditTrail(): void
    {
        $transaction = AccountTransaction::create(
            accountId: ' acc-1 ',
            referenceType: AccountTransactionReferenceType::WITHDRAW,
            referenceId: ' wd-1 ',
            direction: AccountTransactionDirection::DEBIT,
            amount: Money::fromDecimal('25.00'),
            balanceBefore: Money::fromDecimal('100.00'),
            balanceAfter: Money::fromDecimal('75.00'),
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );

        self::assertSame('acc-1', $transaction->accountId());
        self::assertSame(AccountTransactionReferenceType::WITHDRAW, $transaction->referenceType());
        self::assertSame('wd-1', $transaction->referenceId());
        self::assertSame(AccountTransactionDirection::DEBIT, $transaction->direction());
        self::assertTrue($transaction->amount()->equals(Money::fromDecimal('25.00')));
        self::assertTrue($transaction->balanceBefore()->equals(Money::fromDecimal('100.00')));
        self::assertTrue($transaction->balanceAfter()->equals(Money::fromDecimal('75.00')));
    }

    public function testAccountTransactionRejectsEmptyAccountId(): void
    {
        $this->expectException(InvalidAccountTransaction::class);
        $this->expectExceptionMessage('Account transaction account_id cannot be empty.');

        AccountTransaction::create(
            accountId: '   ',
            referenceType: AccountTransactionReferenceType::WITHDRAW,
            referenceId: 'wd-1',
            direction: AccountTransactionDirection::DEBIT,
            amount: Money::fromDecimal('25.00'),
            balanceBefore: Money::fromDecimal('100.00'),
            balanceAfter: Money::fromDecimal('75.00'),
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );
    }

    public function testAccountTransactionRejectsZeroAmount(): void
    {
        $this->expectException(InvalidAccountTransaction::class);
        $this->expectExceptionMessage('Account transaction amount must be greater than zero.');

        AccountTransaction::create(
            accountId: 'acc-1',
            referenceType: AccountTransactionReferenceType::WITHDRAW,
            referenceId: 'wd-1',
            direction: AccountTransactionDirection::DEBIT,
            amount: Money::zero(),
            balanceBefore: Money::fromDecimal('100.00'),
            balanceAfter: Money::fromDecimal('100.00'),
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );
    }

    public function testAccountTransactionRejectsInconsistentBalanceFlow(): void
    {
        $this->expectException(InvalidAccountTransaction::class);
        $this->expectExceptionMessage('Account transaction balance flow is inconsistent.');

        AccountTransaction::create(
            accountId: 'acc-1',
            referenceType: AccountTransactionReferenceType::WITHDRAW,
            referenceId: 'wd-1',
            direction: AccountTransactionDirection::DEBIT,
            amount: Money::fromDecimal('25.00'),
            balanceBefore: Money::fromDecimal('100.00'),
            balanceAfter: Money::fromDecimal('80.00'),
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );
    }
}
