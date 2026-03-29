<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Application\Exception;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotProcessable;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;

final class WithdrawNotProcessableTest extends TestCase
{
    public function testFactoryCarriesStableMetadata(): void
    {
        $exception = WithdrawNotProcessable::withStatus('wd-1', WithdrawStatus::PENDING);

        self::assertSame('withdraw.not_processable', $exception->errorCode());
        self::assertSame(
            [
                'withdraw_id' => 'wd-1',
                'status' => 'PENDING',
            ],
            $exception->context()
        );
    }
}
