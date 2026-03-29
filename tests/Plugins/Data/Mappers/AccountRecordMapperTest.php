<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Mappers;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Plugins\Data\Mappers\AccountRecordMapper;

final class AccountRecordMapperTest extends TestCase
{
    public function testMapsAccountToRecordAndBack(): void
    {
        $createdAt = new DateTimeImmutable('2026-03-28 10:00:00+00:00');
        $updatedAt = new DateTimeImmutable('2026-03-28 11:00:00+00:00');
        $mapper = new AccountRecordMapper();

        $record = $mapper->toRecord(Account::reconstitute(
            'acc-1',
            'Conta principal',
            Money::fromDecimal('120.55'),
            $createdAt,
            $updatedAt,
        ));

        self::assertSame('120.55', $record['balance']);
        self::assertSame('2026-03-28 10:00:00.000000', $record['created_at']);

        $entity = $mapper->toDomain($record);

        self::assertSame('acc-1', $entity->id());
        self::assertSame('Conta principal', $entity->name());
        self::assertSame('120.55', $entity->balance()->toDecimal());
        self::assertSame('2026-03-28T11:00:00+00:00', $entity->updatedAt()->setTimezone(new \DateTimeZone('UTC'))->format(DATE_ATOM));
    }
}
