<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Account;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InsufficientAccountBalance;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Plugins\Account\DomainAtomicAccountDebit;

final class DomainAtomicAccountDebitTest extends TestCase
{
    public function testExecuteReturnsUpdatedAccountOnSuccessfulDebit(): void
    {
        $account = Account::create(
            'acc-1',
            'Main Account',
            Money::fromDecimal('100.00'),
            new DateTimeImmutable('2026-03-28T09:00:00-03:00'),
        );

        $result = (new DomainAtomicAccountDebit())->execute(
            $account,
            Money::fromDecimal('25.00'),
            new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );

        self::assertTrue($result->isSuccess());
        self::assertSame($account, $result->value());
        self::assertSame('75.00', $account->balance()->toDecimal());
    }

    public function testExecuteReturnsFailureWhenBalanceWouldBecomeNegative(): void
    {
        $account = Account::create(
            'acc-1',
            'Main Account',
            Money::fromDecimal('10.00'),
            new DateTimeImmutable('2026-03-28T09:00:00-03:00'),
        );

        $result = (new DomainAtomicAccountDebit())->execute(
            $account,
            Money::fromDecimal('25.00'),
            new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );

        self::assertTrue($result->isFailure());
        self::assertInstanceOf(InsufficientAccountBalance::class, $result->error());
        self::assertSame('10.00', $account->balance()->toDecimal());
    }
}
