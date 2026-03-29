<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Exception;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidAccountWithdrawPix;

final class InvalidAccountWithdrawPixTest extends TestCase
{
    public function testEmptyWithdrawIdFactoryCarriesStableMetadata(): void
    {
        $exception = InvalidAccountWithdrawPix::emptyWithdrawId();

        self::assertSame('account_withdraw_pix.empty_withdraw_id', $exception->errorCode());
        self::assertSame([], $exception->context());
    }

    public function testUnsupportedMethodFactoryCarriesStableMetadata(): void
    {
        $exception = InvalidAccountWithdrawPix::unsupportedMethod(WithdrawMethod::PIX);

        self::assertSame('account_withdraw_pix.unsupported_method', $exception->errorCode());
        self::assertSame(['method' => 'PIX'], $exception->context());
    }

    public function testUnsupportedPixKeyTypeFactoryCarriesStableMetadata(): void
    {
        $exception = InvalidAccountWithdrawPix::unsupportedPixKeyType(WithdrawMethod::PIX, \Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType::CPF);

        self::assertSame('account_withdraw_pix.unsupported_pix_key_type', $exception->errorCode());
        self::assertSame(
            [
                'method' => 'PIX',
                'pix_key_type' => 'CPF',
            ],
            $exception->context()
        );
    }

    public function testInconsistentTimestampsFactoryCarriesStableMetadata(): void
    {
        $createdAt = new DateTimeImmutable('2026-03-28T10:00:00-03:00');
        $updatedAt = new DateTimeImmutable('2026-03-28T09:59:59-03:00');

        $exception = InvalidAccountWithdrawPix::inconsistentTimestamps($createdAt, $updatedAt);

        self::assertSame('account_withdraw_pix.inconsistent_timestamps', $exception->errorCode());
        self::assertSame(
            [
                'created_at' => '2026-03-28T10:00:00-03:00',
                'updated_at' => '2026-03-28T09:59:59-03:00',
            ],
            $exception->context()
        );
    }
}
