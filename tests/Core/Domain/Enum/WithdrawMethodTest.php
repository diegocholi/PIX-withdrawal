<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Enum;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;

final class WithdrawMethodTest extends TestCase
{
    public function testWithdrawMethodStartsWithPix(): void
    {
        self::assertSame([WithdrawMethod::PIX], WithdrawMethod::cases());
        self::assertSame('PIX', WithdrawMethod::PIX->value);
        self::assertTrue(WithdrawMethod::PIX->isPix());
    }

    public function testValuesReturnsStableScalarRepresentation(): void
    {
        self::assertSame(['PIX'], WithdrawMethod::values());
    }

    public function testPixMethodExposesSupportedPixPayloadAndKeyTypes(): void
    {
        self::assertTrue(WithdrawMethod::PIX->supportsPixPayload());
        self::assertSame([PixKeyType::EMAIL], WithdrawMethod::PIX->supportedPixKeyTypes());
        self::assertTrue(WithdrawMethod::PIX->supportsPixKeyType(PixKeyType::EMAIL));
        self::assertFalse(WithdrawMethod::PIX->supportsPixKeyType(PixKeyType::CPF));
    }
}
