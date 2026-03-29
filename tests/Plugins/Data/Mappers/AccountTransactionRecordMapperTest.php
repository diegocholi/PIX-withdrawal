<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Mappers;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountTransaction;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionDirection;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionReferenceType;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Plugins\Data\Mappers\AccountTransactionRecordMapper;

final class AccountTransactionRecordMapperTest extends TestCase
{
    public function testMapsAccountTransactionToRecordAndBack(): void
    {
        $createdAt = new DateTimeImmutable('2026-03-28 10:00:00+00:00');
        $mapper = new AccountTransactionRecordMapper();

        $record = $mapper->toRecord(AccountTransaction::create(
            accountId: 'acc-1',
            referenceType: AccountTransactionReferenceType::WITHDRAW,
            referenceId: 'wd-1',
            direction: AccountTransactionDirection::DEBIT,
            amount: Money::fromDecimal('10.00'),
            balanceBefore: Money::fromDecimal('100.00'),
            balanceAfter: Money::fromDecimal('90.00'),
            createdAt: $createdAt,
        ));

        self::assertSame('DEBIT', $record['direction']);
        self::assertSame('90.00', $record['balance_after']);

        $entity = $mapper->toDomain($record);

        self::assertSame('acc-1', $entity->accountId());
        self::assertSame('WITHDRAW', $entity->referenceType()->value);
        self::assertSame('10.00', $entity->amount()->toDecimal());
        self::assertSame('90.00', $entity->balanceAfter()->toDecimal());
    }
}
