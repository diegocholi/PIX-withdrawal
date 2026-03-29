<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Enum;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;

final class PixKeyTypeTest extends TestCase
{
    public function testPixKeyTypeEnumeratesKnownTypes(): void
    {
        self::assertSame(
            ['EMAIL', 'CPF', 'PHONE', 'RANDOM'],
            PixKeyType::values()
        );
    }

    public function testOnlyEmailIsEnabledInitially(): void
    {
        self::assertTrue(PixKeyType::EMAIL->isEnabled());
        self::assertFalse(PixKeyType::CPF->isEnabled());
        self::assertFalse(PixKeyType::PHONE->isEnabled());
        self::assertFalse(PixKeyType::RANDOM->isEnabled());
    }

    public function testPixKeyTypeCanEvaluateMethodCompatibility(): void
    {
        self::assertTrue(PixKeyType::EMAIL->isSupportedBy(WithdrawMethod::PIX));
        self::assertFalse(PixKeyType::CPF->isSupportedBy(WithdrawMethod::PIX));
        self::assertFalse(PixKeyType::PHONE->isSupportedBy(WithdrawMethod::PIX));
    }
}
