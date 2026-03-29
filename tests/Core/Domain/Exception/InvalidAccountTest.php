<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Exception;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidAccount;

final class InvalidAccountTest extends TestCase
{
    public function testEmptyIdFactoryCarriesStableMetadata(): void
    {
        $exception = InvalidAccount::emptyId();

        self::assertSame('account.empty_id', $exception->errorCode());
        self::assertSame([], $exception->context());
    }

    public function testEmptyNameFactoryCarriesStableMetadata(): void
    {
        $exception = InvalidAccount::emptyName();

        self::assertSame('account.empty_name', $exception->errorCode());
        self::assertSame([], $exception->context());
    }

    public function testInconsistentTimestampsFactoryCarriesStableMetadata(): void
    {
        $createdAt = new DateTimeImmutable('2026-03-28T10:00:00-03:00');
        $updatedAt = new DateTimeImmutable('2026-03-28T09:59:59-03:00');

        $exception = InvalidAccount::inconsistentTimestamps($createdAt, $updatedAt);

        self::assertSame('account.inconsistent_timestamps', $exception->errorCode());
        self::assertSame(
            [
                'created_at' => '2026-03-28T10:00:00-03:00',
                'updated_at' => '2026-03-28T09:59:59-03:00',
            ],
            $exception->context()
        );
    }

    public function testStaleUpdateFactoryCarriesStableMetadata(): void
    {
        $currentUpdatedAt = new DateTimeImmutable('2026-03-28T10:00:00-03:00');
        $newUpdatedAt = new DateTimeImmutable('2026-03-28T09:59:59-03:00');

        $exception = InvalidAccount::staleUpdate($currentUpdatedAt, $newUpdatedAt);

        self::assertSame('account.stale_update', $exception->errorCode());
        self::assertSame(
            [
                'current_updated_at' => '2026-03-28T10:00:00-03:00',
                'new_updated_at' => '2026-03-28T09:59:59-03:00',
            ],
            $exception->context()
        );
    }
}
