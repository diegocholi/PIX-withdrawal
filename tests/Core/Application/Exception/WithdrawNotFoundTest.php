<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Application\Exception;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotFound;

final class WithdrawNotFoundTest extends TestCase
{
    public function testFactoryCarriesStableMetadata(): void
    {
        $exception = WithdrawNotFound::withId('wd-1');

        self::assertSame('withdraw.not_found', $exception->errorCode());
        self::assertSame(['withdraw_id' => 'wd-1'], $exception->context());
    }
}
