<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Enum;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;

final class WithdrawStatusTest extends TestCase
{
    public function testWithdrawStatusContainsExpectedCases(): void
    {
        self::assertSame(
            [
                'PENDING',
                'SCHEDULED',
                'QUEUED',
                'PROCESSING',
                'DONE',
                'FAILED_INSUFFICIENT_FUNDS',
                'FAILED_VALIDATION',
                'FAILED_INTERNAL',
            ],
            WithdrawStatus::values()
        );
    }

    public function testFinalStateHelper(): void
    {
        self::assertFalse(WithdrawStatus::PENDING->isFinal());
        self::assertFalse(WithdrawStatus::SCHEDULED->isFinal());
        self::assertFalse(WithdrawStatus::QUEUED->isFinal());
        self::assertFalse(WithdrawStatus::PROCESSING->isFinal());
        self::assertTrue(WithdrawStatus::DONE->isFinal());
        self::assertTrue(WithdrawStatus::FAILED_INSUFFICIENT_FUNDS->isFinal());
        self::assertTrue(WithdrawStatus::FAILED_VALIDATION->isFinal());
        self::assertTrue(WithdrawStatus::FAILED_INTERNAL->isFinal());
    }

    public function testQueueEligibilityHelper(): void
    {
        self::assertTrue(WithdrawStatus::PENDING->canBeQueued());
        self::assertTrue(WithdrawStatus::SCHEDULED->canBeQueued());
        self::assertFalse(WithdrawStatus::QUEUED->canBeQueued());
        self::assertFalse(WithdrawStatus::PROCESSING->canBeQueued());
        self::assertFalse(WithdrawStatus::DONE->canBeQueued());
    }

    public function testProcessingEligibilityHelper(): void
    {
        self::assertFalse(WithdrawStatus::PENDING->canBeProcessed());
        self::assertFalse(WithdrawStatus::SCHEDULED->canBeProcessed());
        self::assertTrue(WithdrawStatus::QUEUED->canBeProcessed());
        self::assertTrue(WithdrawStatus::PROCESSING->canBeProcessed());
        self::assertFalse(WithdrawStatus::FAILED_INTERNAL->canBeProcessed());
    }
}
