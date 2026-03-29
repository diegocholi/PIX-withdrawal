<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Infrastructure\Contract;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AtomicAccountDebit;
use Tecnofit\PixWithdrawal\Core\Shared\Result;

final class AtomicAccountDebitContractTest extends TestCase
{
    public function testAtomicAccountDebitContract(): void
    {
        $reflection = new ReflectionClass(AtomicAccountDebit::class);

        self::assertTrue($reflection->isInterface());
        self::assertTrue($reflection->hasMethod('execute'));
        self::assertSame(Result::class, (string) $reflection->getMethod('execute')->getReturnType());

        $parameters = $reflection->getMethod('execute')->getParameters();

        self::assertCount(3, $parameters);
        self::assertSame(Account::class, (string) $parameters[0]->getType());
        self::assertSame(Money::class, (string) $parameters[1]->getType());
        self::assertSame(DateTimeImmutable::class, (string) $parameters[2]->getType());
    }
}
