<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Mappers;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;
use Tecnofit\PixWithdrawal\Plugins\Data\Mappers\AccountWithdrawPixRecordMapper;

final class AccountWithdrawPixRecordMapperTest extends TestCase
{
    public function testMapsWithdrawPixToRecordAndBack(): void
    {
        $createdAt = new DateTimeImmutable('2026-03-28 10:00:00+00:00');
        $mapper = new AccountWithdrawPixRecordMapper();

        $record = $mapper->toRecord(AccountWithdrawPix::reconstitute(
            withdrawId: 'wd-1',
            method: \Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod::PIX,
            pixKey: PixKey::email('user@example.com'),
            createdAt: $createdAt,
            updatedAt: $createdAt,
        ));

        self::assertSame('EMAIL', $record['type']);
        self::assertSame('user@example.com', $record['key']);

        $entity = $mapper->toDomain($record);

        self::assertSame('wd-1', $entity->withdrawId());
        self::assertSame('EMAIL', $entity->pixKeyType()->value);
        self::assertSame('user@example.com', $entity->pixKey()->value());
    }
}
