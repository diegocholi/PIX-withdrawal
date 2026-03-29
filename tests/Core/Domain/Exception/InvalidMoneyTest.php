<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Exception;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidMoney;

final class InvalidMoneyTest extends TestCase
{
    public function testInvalidMoneyCarriesStableErrorMetadata(): void
    {
        $exception = InvalidMoney::invalidFormat('10,00');

        self::assertSame('money.invalid_format', $exception->errorCode());
        self::assertSame(['amount' => '10,00'], $exception->context());
    }
}
