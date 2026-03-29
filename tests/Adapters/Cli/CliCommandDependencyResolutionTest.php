<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use Hyperf\Contract\ApplicationInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Tecnofit\PixWithdrawal\Adapters\Cli\Command\AppCaseSeedCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Command\AppSchemaMigrateCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Command\BootstrapSanityCheckCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Command\ConfigValidateCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Command\SystemCheckAllCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Command\SystemCheckKafkaCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Command\SystemCheckMailCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Command\SystemCheckMySqlCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Command\WithdrawNotifyWorkerCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Command\WithdrawProcessWorkerCommand;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

final class CliCommandDependencyResolutionTest extends TestCase
{
    public function testContainerResolvesCliCommandWithItsRequiredDependencies(): void
    {
        $container = (new HyperfContainerFactory())->create();

        $command = $container->get(AppCaseSeedCommand::class);

        self::assertInstanceOf(AppCaseSeedCommand::class, $command);
    }

    public function testConsoleApplicationResolvesRegisteredCliCommandInstance(): void
    {
        $container = (new HyperfContainerFactory())->create();
        $application = $container->get(ApplicationInterface::class);
        $command = $application->find('app:seed:case');

        self::assertInstanceOf(Command::class, $command);
        self::assertInstanceOf(AppCaseSeedCommand::class, $command);
    }

    public function testContainerResolvesSchemaMigrateCommandWithItsRequiredDependencies(): void
    {
        $container = (new HyperfContainerFactory())->create();

        $command = $container->get(AppSchemaMigrateCommand::class);

        self::assertInstanceOf(AppSchemaMigrateCommand::class, $command);
    }

    public function testContainerResolvesSanityCheckCommandWithItsRequiredDependencies(): void
    {
        $container = (new HyperfContainerFactory())->create();

        $command = $container->get(BootstrapSanityCheckCommand::class);

        self::assertInstanceOf(BootstrapSanityCheckCommand::class, $command);
    }

    public function testContainerResolvesWithdrawWorkerCommandWithItsRequiredDependencies(): void
    {
        $container = (new HyperfContainerFactory())->create();

        $command = $container->get(WithdrawProcessWorkerCommand::class);

        self::assertInstanceOf(WithdrawProcessWorkerCommand::class, $command);
    }

    public function testContainerResolvesWithdrawNotificationWorkerCommandWithItsRequiredDependencies(): void
    {
        $container = (new HyperfContainerFactory())->create();

        $command = $container->get(WithdrawNotifyWorkerCommand::class);

        self::assertInstanceOf(WithdrawNotifyWorkerCommand::class, $command);
    }

    public function testContainerResolvesSystemCheckMySqlCommandWithItsRequiredDependencies(): void
    {
        $container = (new HyperfContainerFactory())->create();

        $command = $container->get(SystemCheckMySqlCommand::class);

        self::assertInstanceOf(SystemCheckMySqlCommand::class, $command);
    }

    public function testContainerResolvesSystemCheckKafkaCommandWithItsRequiredDependencies(): void
    {
        $container = (new HyperfContainerFactory())->create();

        $command = $container->get(SystemCheckKafkaCommand::class);

        self::assertInstanceOf(SystemCheckKafkaCommand::class, $command);
    }

    public function testContainerResolvesSystemCheckMailCommandWithItsRequiredDependencies(): void
    {
        $container = (new HyperfContainerFactory())->create();

        $command = $container->get(SystemCheckMailCommand::class);

        self::assertInstanceOf(SystemCheckMailCommand::class, $command);
    }

    public function testContainerResolvesSystemCheckAllCommandWithItsRequiredDependencies(): void
    {
        $container = (new HyperfContainerFactory())->create();

        $command = $container->get(SystemCheckAllCommand::class);

        self::assertInstanceOf(SystemCheckAllCommand::class, $command);
    }

    public function testContainerResolvesConfigValidateCommandWithItsRequiredDependencies(): void
    {
        $container = (new HyperfContainerFactory())->create();

        $command = $container->get(ConfigValidateCommand::class);

        self::assertInstanceOf(ConfigValidateCommand::class, $command);
    }
}
