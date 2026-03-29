<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Infrastructure\Contract;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;

final class DomainEventDispatcherContractTest extends TestCase
{
    public function testDomainEventDispatcherContractUsesDomainEventAbstraction(): void
    {
        $reflection = new ReflectionClass(DomainEventDispatcher::class);

        self::assertTrue($reflection->isInterface());
        self::assertTrue($reflection->hasMethod('dispatch'));

        $method = $reflection->getMethod('dispatch');
        $parameters = $method->getParameters();

        self::assertCount(1, $parameters);
        self::assertSame(DomainEvent::class, (string) $parameters[0]->getType());
        self::assertSame('void', (string) $method->getReturnType());
    }
}
