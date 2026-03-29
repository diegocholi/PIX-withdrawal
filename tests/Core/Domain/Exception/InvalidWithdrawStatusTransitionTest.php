<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Exception;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidWithdrawStatusTransition;

final class InvalidWithdrawStatusTransitionTest extends TestCase
{
    public function testFromToCarriesStableMetadata(): void
    {
        $exception = InvalidWithdrawStatusTransition::fromTo(
            WithdrawStatus::PENDING,
            WithdrawStatus::PROCESSING,
        );

        self::assertSame('account_withdraw.invalid_status_transition', $exception->errorCode());
        self::assertSame(
            [
                'current_status' => 'PENDING',
                'target_status' => 'PROCESSING',
            ],
            $exception->context()
        );
    }
}
