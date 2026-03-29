<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionDirection;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionReferenceType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\Service\WithdrawDebitTransactionFactory;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;

final class WithdrawDebitTransactionFactoryTest extends TestCase
{
    public function testFactoryCreatesWithdrawDebitAuditTransaction(): void
    {
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('25.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );

        $transaction = (new WithdrawDebitTransactionFactory())->create(
            withdraw: $withdraw,
            balanceBefore: Money::fromDecimal('100.00'),
            balanceAfter: Money::fromDecimal('75.00'),
            createdAt: new DateTimeImmutable('2026-03-28T10:05:00-03:00'),
        );

        self::assertSame('acc-1', $transaction->accountId());
        self::assertSame('wd-1', $transaction->referenceId());
        self::assertSame(AccountTransactionReferenceType::WITHDRAW, $transaction->referenceType());
        self::assertSame(AccountTransactionDirection::DEBIT, $transaction->direction());
        self::assertTrue($transaction->amount()->equals(Money::fromDecimal('25.00')));
        self::assertTrue($transaction->balanceBefore()->equals(Money::fromDecimal('100.00')));
        self::assertTrue($transaction->balanceAfter()->equals(Money::fromDecimal('75.00')));
        self::assertSame('2026-03-28T10:05:00-03:00', $transaction->createdAt()->format(DATE_ATOM));
    }
}
