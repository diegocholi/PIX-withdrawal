<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Application\Bootstrap;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Bootstrap\ApplicationBindingRegistry;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\CreateWithdraw;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\CreateWithdrawHandler;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\FindWithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\FindWithdrawStatusHandler;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\ProcessWithdraw;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\ProcessWithdrawHandler;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

final class ApplicationBindingRegistryTest extends TestCase
{
    public function testDefinitionsExposeCanonicalUseCaseBindings(): void
    {
        $definitions = ApplicationBindingRegistry::definitions();

        self::assertSame(CreateWithdrawHandler::class, $definitions[CreateWithdraw::class]);
        self::assertSame(FindWithdrawStatusHandler::class, $definitions[FindWithdrawStatus::class]);
        self::assertSame(ProcessWithdrawHandler::class, $definitions[ProcessWithdraw::class]);
    }

    public function testContainerResolvesUseCaseInterfacesThroughApplicationRegistry(): void
    {
        $container = (new HyperfContainerFactory())->create();

        self::assertInstanceOf(CreateWithdrawHandler::class, $container->get(CreateWithdraw::class));
        self::assertInstanceOf(FindWithdrawStatusHandler::class, $container->get(FindWithdrawStatus::class));
        self::assertInstanceOf(ProcessWithdrawHandler::class, $container->get(ProcessWithdraw::class));
    }
}
