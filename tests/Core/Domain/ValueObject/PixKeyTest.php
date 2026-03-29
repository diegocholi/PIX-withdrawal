<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidPixKey;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;

final class PixKeyTest extends TestCase
{
    public function testPixKeyAcceptsNormalizedEmail(): void
    {
        $pixKey = PixKey::from(PixKeyType::EMAIL, ' User@Example.Com ');

        self::assertTrue((new ReflectionClass($pixKey))->isReadOnly());
        self::assertInstanceOf(SerializableDto::class, $pixKey);
        self::assertSame(PixKeyType::EMAIL, $pixKey->type());
        self::assertSame('user@example.com', $pixKey->value());
        self::assertTrue($pixKey->isEmail());
        self::assertSame(
            [
                'type' => 'EMAIL',
                'value' => 'user@example.com',
            ],
            $pixKey->toArray()
        );
    }

    public function testPixKeySupportsEqualityComparison(): void
    {
        $left = PixKey::email('user@example.com');
        $right = PixKey::from(PixKeyType::EMAIL, 'user@example.com');

        self::assertTrue($left->equals($right));
    }

    public function testPixKeyRejectsInvalidEmailFormat(): void
    {
        $this->expectException(InvalidPixKey::class);
        $this->expectExceptionMessage('Pix email key format is invalid.');

        PixKey::from(PixKeyType::EMAIL, 'invalid-email');
    }

    public function testPixKeyRejectsTypesThatAreNotEnabledYet(): void
    {
        $this->expectException(InvalidPixKey::class);
        $this->expectExceptionMessage('Pix key type "CPF" is not enabled yet.');

        PixKey::from(PixKeyType::CPF, '12345678901');
    }
}
