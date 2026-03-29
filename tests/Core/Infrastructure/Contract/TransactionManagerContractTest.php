<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Infrastructure\Contract;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\TransactionManager;

final class TransactionManagerContractTest extends TestCase
{
    public function testTransactionManagerContract(): void
    {
        $reflection = new ReflectionClass(TransactionManager::class);

        self::assertTrue($reflection->isInterface());
        self::assertTrue($reflection->hasMethod('run'));
        self::assertSame('mixed', (string) $reflection->getMethod('run')->getReturnType());
        self::assertSame('callable', (string) $reflection->getMethod('run')->getParameters()[0]->getType());
    }
}
