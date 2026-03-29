<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Exception;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidScheduleAt;

final class InvalidScheduleAtTest extends TestCase
{
    public function testInvalidFormatFactoryCreatesStableErrorMetadata(): void
    {
        $exception = InvalidScheduleAt::invalidFormat('not-a-date');

        self::assertSame('Schedule date format is invalid.', $exception->getMessage());
        self::assertSame('schedule_at.invalid_format', $exception->errorCode());
        self::assertSame(['value' => 'not-a-date'], $exception->context());
    }

    public function testPastDateFactoryCreatesStableErrorMetadata(): void
    {
        $exception = InvalidScheduleAt::pastDate(
            '2026-03-27T10:00:00-03:00',
            '2026-03-28T10:00:00-03:00',
        );

        self::assertSame('Schedule date cannot be in the past.', $exception->getMessage());
        self::assertSame('schedule_at.past_date', $exception->errorCode());
        self::assertSame(
            [
                'value' => '2026-03-27T10:00:00-03:00',
                'current_time' => '2026-03-28T10:00:00-03:00',
            ],
            $exception->context()
        );
    }
}
