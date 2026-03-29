<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Exception;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidAccountWithdraw;

final class InvalidAccountWithdrawTest extends TestCase
{
    public function testEmptyFieldFactoryCarriesStableMetadata(): void
    {
        $exception = InvalidAccountWithdraw::emptyField('account_id');

        self::assertSame('account_withdraw.empty_field', $exception->errorCode());
        self::assertSame(['field' => 'account_id'], $exception->context());
    }

    public function testNegativeRetryCountFactoryCarriesStableMetadata(): void
    {
        $exception = InvalidAccountWithdraw::negativeRetryCount(-1);

        self::assertSame('account_withdraw.negative_retry_count', $exception->errorCode());
        self::assertSame(['retry_count' => -1], $exception->context());
    }

    public function testInconsistentStateFactoryCarriesStableMetadata(): void
    {
        $exception = InvalidAccountWithdraw::inconsistentState(
            WithdrawStatus::PROCESSING,
            'processing_status_requires_processing_started_at',
            ['withdraw_id' => 'wd-1'],
        );

        self::assertSame('account_withdraw.inconsistent_state', $exception->errorCode());
        self::assertSame(
            [
                'status' => 'PROCESSING',
                'reason' => 'processing_status_requires_processing_started_at',
                'withdraw_id' => 'wd-1',
            ],
            $exception->context()
        );
    }
}
