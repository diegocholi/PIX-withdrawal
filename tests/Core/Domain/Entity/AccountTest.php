<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidAccount;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InsufficientAccountBalance;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Shared\Result;

final class AccountTest extends TestCase
{
    public function testAccountCanBeCreatedWithConsistentInitialState(): void
    {
        $createdAt = new DateTimeImmutable('2026-03-28T10:00:00-03:00');
        $account = Account::create(' acc-1 ', ' Main Account ', Money::fromDecimal('150.50'), $createdAt);

        self::assertSame('acc-1', $account->id());
        self::assertSame('Main Account', $account->name());
        self::assertTrue($account->balance()->equals(Money::fromDecimal('150.50')));
        self::assertSame('2026-03-28T10:00:00-03:00', $account->createdAt()->format(DATE_ATOM));
        self::assertSame('2026-03-28T10:00:00-03:00', $account->updatedAt()->format(DATE_ATOM));
        self::assertTrue($account->hasBalance());
    }

    public function testAccountCanBeReconstitutedWithPersistedTimestamps(): void
    {
        $account = Account::reconstitute(
            'acc-1',
            'Main Account',
            Money::zero(),
            new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            new DateTimeImmutable('2026-03-28T10:15:00-03:00'),
        );

        self::assertFalse($account->hasBalance());
        self::assertSame('2026-03-28T10:15:00-03:00', $account->updatedAt()->format(DATE_ATOM));
    }

    public function testAccountRejectsEmptyId(): void
    {
        $this->expectException(InvalidAccount::class);
        $this->expectExceptionMessage('Account id cannot be empty.');

        Account::create('   ', 'Main Account', Money::zero(), new DateTimeImmutable('2026-03-28T10:00:00-03:00'));
    }

    public function testAccountRejectsEmptyName(): void
    {
        $this->expectException(InvalidAccount::class);
        $this->expectExceptionMessage('Account name cannot be empty.');

        Account::create('acc-1', '   ', Money::zero(), new DateTimeImmutable('2026-03-28T10:00:00-03:00'));
    }

    public function testAccountRejectsUpdatedAtEarlierThanCreatedAt(): void
    {
        $this->expectException(InvalidAccount::class);
        $this->expectExceptionMessage('Account updated_at cannot be earlier than created_at.');

        Account::reconstitute(
            'acc-1',
            'Main Account',
            Money::zero(),
            new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            new DateTimeImmutable('2026-03-28T09:59:59-03:00'),
        );
    }

    public function testAccountDebitReturnsSuccessAndUpdatesBalance(): void
    {
        $account = Account::create(
            'acc-1',
            'Main Account',
            Money::fromDecimal('150.50'),
            new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );

        $result = $account->debit(
            Money::fromDecimal('50.25'),
            new DateTimeImmutable('2026-03-28T10:05:00-03:00'),
        );

        self::assertInstanceOf(Result::class, $result);
        self::assertTrue($result->isSuccess());
        self::assertSame($account, $result->value());
        self::assertSame('100.25', $account->balance()->toDecimal());
        self::assertSame('2026-03-28T10:05:00-03:00', $account->updatedAt()->format(DATE_ATOM));
    }

    public function testAccountDebitReturnsFailureWhenBalanceIsInsufficient(): void
    {
        $account = Account::create(
            'acc-1',
            'Main Account',
            Money::fromDecimal('10.00'),
            new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );

        $result = $account->debit(
            Money::fromDecimal('12.50'),
            new DateTimeImmutable('2026-03-28T10:05:00-03:00'),
        );

        self::assertTrue($result->isFailure());
        self::assertInstanceOf(InsufficientAccountBalance::class, $result->error());
        self::assertSame('10.00', $account->balance()->toDecimal());
        self::assertSame('2026-03-28T10:00:00-03:00', $account->updatedAt()->format(DATE_ATOM));
    }

    public function testAccountDebitRejectsTimestampEarlierThanCurrentUpdatedAt(): void
    {
        $account = Account::reconstitute(
            'acc-1',
            'Main Account',
            Money::fromDecimal('10.00'),
            new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            new DateTimeImmutable('2026-03-28T10:05:00-03:00'),
        );

        $this->expectException(InvalidAccount::class);
        $this->expectExceptionMessage('Account update timestamp cannot move backwards.');

        $account->debit(
            Money::fromDecimal('1.00'),
            new DateTimeImmutable('2026-03-28T10:04:59-03:00'),
        );
    }
}
