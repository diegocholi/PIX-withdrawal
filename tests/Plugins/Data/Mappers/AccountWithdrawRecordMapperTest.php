<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Mappers;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;
use Tecnofit\PixWithdrawal\Plugins\Data\Mappers\AccountWithdrawRecordMapper;

final class AccountWithdrawRecordMapperTest extends TestCase
{
    public function testMapsScheduledWithdrawToRecordAndBack(): void
    {
        $createdAt = new DateTimeImmutable('2026-03-28 10:00:00+00:00');
        $scheduledFor = ScheduleAt::fromDateTime(
            new DateTimeImmutable('2026-03-29 12:00:00+00:00'),
            new class(new DateTimeImmutable('2026-03-28 09:00:00+00:00')) implements Clock {
                public function __construct(private readonly DateTimeImmutable $now)
                {
                }

                public function now(): DateTimeImmutable
                {
                    return $this->now;
                }
            }
        );
        $mapper = new AccountWithdrawRecordMapper();

        $record = $mapper->toRecord(AccountWithdraw::reconstitute(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('45.10'),
            status: \Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus::SCHEDULED,
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            retryCount: 0,
            createdAt: $createdAt,
            updatedAt: $createdAt,
            scheduledFor: $scheduledFor,
        ));

        self::assertSame(1, $record['scheduled']);
        self::assertSame('2026-03-29 12:00:00.000000', $record['scheduled_for']);
        self::assertSame('2026-03-28 10:00:00.000000', $record['requested_at']);
        self::assertSame('idem-1', $record['duplicate_guard_fingerprint']);

        $entity = $mapper->toDomain($record);

        self::assertSame('wd-1', $entity->id());
        self::assertSame('acc-1', $entity->accountId());
        self::assertSame('45.10', $entity->amount()->toDecimal());
        self::assertSame('SCHEDULED', $entity->status()->value);
        self::assertSame('idem-1', $entity->duplicateGuardFingerprint());
        self::assertSame('2026-03-29T12:00:00+00:00', $entity->scheduledFor()?->value()->format(DATE_ATOM));
    }
}
