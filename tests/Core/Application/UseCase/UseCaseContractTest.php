<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Application\UseCase;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawCommand;
use Tecnofit\PixWithdrawal\Core\Application\Command\ProcessWithdrawCommand;
use Tecnofit\PixWithdrawal\Core\Application\Dto\CreateWithdrawOutput;
use Tecnofit\PixWithdrawal\Core\Application\Dto\FindWithdrawStatusOutput;
use Tecnofit\PixWithdrawal\Core\Application\Dto\ProcessWithdrawOutput;
use Tecnofit\PixWithdrawal\Core\Application\Query\FindWithdrawStatusQuery;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\CreateWithdraw;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\FindWithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\ProcessWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\CorrelationIdGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Traceable;

final class UseCaseContractTest extends TestCase
{
    public function testCreateWithdrawContract(): void
    {
        $this->assertUseCaseSignature(
            CreateWithdraw::class,
            CreateWithdrawCommand::class,
            CreateWithdrawOutput::class
        );
    }

    public function testProcessWithdrawContract(): void
    {
        $this->assertUseCaseSignature(
            ProcessWithdraw::class,
            ProcessWithdrawCommand::class,
            ProcessWithdrawOutput::class
        );
    }

    public function testFindWithdrawStatusContract(): void
    {
        $this->assertUseCaseSignature(
            FindWithdrawStatus::class,
            FindWithdrawStatusQuery::class,
            FindWithdrawStatusOutput::class
        );
    }

    public function testApplicationInputAndOutputContractsRemainInterfaces(): void
    {
        self::assertTrue((new ReflectionClass(CreateWithdrawCommand::class))->isInterface());
        self::assertTrue((new ReflectionClass(ProcessWithdrawCommand::class))->isInterface());
        self::assertTrue((new ReflectionClass(FindWithdrawStatusQuery::class))->isInterface());
        self::assertTrue((new ReflectionClass(CreateWithdrawOutput::class))->isInterface());
        self::assertTrue((new ReflectionClass(ProcessWithdrawOutput::class))->isInterface());
        self::assertTrue((new ReflectionClass(FindWithdrawStatusOutput::class))->isInterface());
        self::assertTrue((new ReflectionClass(CorrelationIdGenerator::class))->isInterface());
        self::assertTrue((new ReflectionClass(Traceable::class))->isInterface());
        self::assertTrue((new ReflectionClass(CreateWithdrawCommand::class))->implementsInterface(Traceable::class));
        self::assertTrue((new ReflectionClass(ProcessWithdrawCommand::class))->implementsInterface(Traceable::class));
        self::assertTrue((new ReflectionClass(FindWithdrawStatusQuery::class))->implementsInterface(Traceable::class));
        self::assertTrue((new ReflectionClass(CreateWithdrawOutput::class))->implementsInterface(Traceable::class));
        self::assertTrue((new ReflectionClass(ProcessWithdrawOutput::class))->implementsInterface(Traceable::class));
        self::assertTrue((new ReflectionClass(FindWithdrawStatusOutput::class))->implementsInterface(Traceable::class));
        self::assertTrue((new ReflectionClass(DomainEvent::class))->implementsInterface(Traceable::class));
    }

    private function assertUseCaseSignature(
        string $useCaseClass,
        string $expectedInputClass,
        string $expectedOutputClass,
    ): void {
        $reflection = new ReflectionClass($useCaseClass);

        self::assertTrue($reflection->isInterface());
        self::assertTrue($reflection->hasMethod('execute'));

        $method = $reflection->getMethod('execute');
        $parameters = $method->getParameters();

        self::assertCount(1, $parameters);
        self::assertSame($expectedInputClass, (string) $parameters[0]->getType());
        self::assertSame($expectedOutputClass, (string) $method->getReturnType());
    }
}
