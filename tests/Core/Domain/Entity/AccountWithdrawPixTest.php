<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidAccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;

final class AccountWithdrawPixTest extends TestCase
{
    public function testAccountWithdrawPixCanBeCreatedWithPixMethodAndKey(): void
    {
        $createdAt = new DateTimeImmutable('2026-03-28T10:00:00-03:00');
        $pixKey = PixKey::from(PixKeyType::EMAIL, 'User@Example.Com');

        $withdrawPix = AccountWithdrawPix::create(
            withdrawId: ' wd-1 ',
            method: WithdrawMethod::PIX,
            pixKey: $pixKey,
            createdAt: $createdAt,
        );

        self::assertSame('wd-1', $withdrawPix->withdrawId());
        self::assertSame(WithdrawMethod::PIX, $withdrawPix->method());
        self::assertSame($pixKey, $withdrawPix->pixKey());
        self::assertSame(PixKeyType::EMAIL, $withdrawPix->pixKeyType());
        self::assertSame('2026-03-28T10:00:00-03:00', $withdrawPix->createdAt()->format(DATE_ATOM));
        self::assertSame('2026-03-28T10:00:00-03:00', $withdrawPix->updatedAt()->format(DATE_ATOM));
    }

    public function testAccountWithdrawPixCanBeReconstituted(): void
    {
        $withdrawPix = AccountWithdrawPix::reconstitute(
            withdrawId: 'wd-1',
            method: WithdrawMethod::PIX,
            pixKey: PixKey::from(PixKeyType::EMAIL, 'user@example.com'),
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            updatedAt: new DateTimeImmutable('2026-03-28T10:05:00-03:00'),
        );

        self::assertSame('2026-03-28T10:05:00-03:00', $withdrawPix->updatedAt()->format(DATE_ATOM));
    }

    public function testAccountWithdrawPixRejectsEmptyWithdrawId(): void
    {
        $this->expectException(InvalidAccountWithdrawPix::class);
        $this->expectExceptionMessage('Account withdraw pix withdraw_id cannot be empty.');

        AccountWithdrawPix::create(
            withdrawId: '   ',
            method: WithdrawMethod::PIX,
            pixKey: PixKey::from(PixKeyType::EMAIL, 'user@example.com'),
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );
    }

    public function testAccountWithdrawPixRejectsUpdatedAtEarlierThanCreatedAt(): void
    {
        $this->expectException(InvalidAccountWithdrawPix::class);
        $this->expectExceptionMessage('Account withdraw pix updated_at cannot be earlier than created_at.');

        AccountWithdrawPix::reconstitute(
            withdrawId: 'wd-1',
            method: WithdrawMethod::PIX,
            pixKey: PixKey::from(PixKeyType::EMAIL, 'user@example.com'),
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            updatedAt: new DateTimeImmutable('2026-03-28T09:59:59-03:00'),
        );
    }

    public function testAccountWithdrawPixRejectsPixKeyTypeUnsupportedByMethodMatrix(): void
    {
        $this->expectException(InvalidAccountWithdrawPix::class);
        $this->expectExceptionMessage('Account withdraw pix does not support key type "CPF" for method "PIX".');

        AccountWithdrawPix::create(
            withdrawId: 'wd-1',
            method: WithdrawMethod::PIX,
            pixKey: $this->pixKeyWithType(PixKeyType::CPF, '12345678901'),
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );
    }

    private function pixKeyWithType(PixKeyType $type, string $value): PixKey
    {
        $reflection = new ReflectionClass(PixKey::class);
        $pixKey = $reflection->newInstanceWithoutConstructor();

        $reflection->getProperty('type')->setValue($pixKey, $type);
        $reflection->getProperty('value')->setValue($pixKey, $value);

        return $pixKey;
    }
}
