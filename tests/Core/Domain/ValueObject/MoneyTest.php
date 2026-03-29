<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidMoney;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;

final class MoneyTest extends TestCase
{
    public function testMoneyNormalizesDecimalRepresentation(): void
    {
        $money = Money::fromDecimal('001.5');

        self::assertTrue((new ReflectionClass($money))->isReadOnly());
        self::assertInstanceOf(SerializableDto::class, $money);
        self::assertSame(150, $money->minorAmount());
        self::assertSame('1.50', $money->toDecimal());
        self::assertSame('1.50', (string) $money);
        self::assertSame(
            [
                'amount' => '1.50',
                'minor_amount' => 150,
            ],
            $money->toArray()
        );
    }

    public function testMoneySupportsSafeComparisonAndArithmetic(): void
    {
        $balance = Money::fromDecimal('10.00');
        $fee = Money::fromDecimal('2.35');
        $remaining = $balance->subtract($fee);
        $increased = $remaining->add(Money::fromDecimal('0.40'));

        self::assertTrue($balance->greaterThan($fee));
        self::assertTrue($balance->greaterThanOrEqual(Money::fromDecimal('10.00')));
        self::assertTrue($fee->lessThan($balance));
        self::assertTrue($remaining->equals(Money::fromDecimal('7.65')));
        self::assertSame('8.05', $increased->toDecimal());
    }

    public function testMoneyCanRepresentZero(): void
    {
        $money = Money::zero();

        self::assertTrue($money->isZero());
        self::assertSame('0.00', $money->toDecimal());
    }

    public function testMoneyRejectsInvalidFormat(): void
    {
        $this->expectException(InvalidMoney::class);
        $this->expectExceptionMessage('Money amount format is invalid.');

        Money::fromDecimal('10,00');
    }

    public function testMoneyRejectsNegativeAmount(): void
    {
        $this->expectException(InvalidMoney::class);
        $this->expectExceptionMessage('Money amount cannot be negative.');

        Money::fromDecimal('-1.00');
    }

    public function testMoneyRejectsSubtractionThatProducesNegativeAmount(): void
    {
        $this->expectException(InvalidMoney::class);
        $this->expectExceptionMessage('Money subtraction cannot produce a negative amount.');

        Money::fromDecimal('1.00')->subtract(Money::fromDecimal('1.01'));
    }
}
