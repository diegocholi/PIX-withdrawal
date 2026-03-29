<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Cli\Bootstrap\CliCommandRegistry;
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
use Tecnofit\PixWithdrawal\Adapters\Cli\Command\WithdrawSchedulerRunCommand;

final class CliCommandRegistryTest extends TestCase
{
    public function testCliCommandRegistryReturnsRegisteredCommandClasses(): void
    {
        self::assertSame(
            [
                AppCaseSeedCommand::class,
                AppSchemaMigrateCommand::class,
                BootstrapSanityCheckCommand::class,
                ConfigValidateCommand::class,
                SystemCheckMySqlCommand::class,
                SystemCheckKafkaCommand::class,
                SystemCheckMailCommand::class,
                SystemCheckAllCommand::class,
                WithdrawSchedulerRunCommand::class,
                WithdrawProcessWorkerCommand::class,
                WithdrawNotifyWorkerCommand::class,
            ],
            CliCommandRegistry::commandClasses(),
        );
    }

    public function testCommandsAutoloadConfigurationDelegatesToCliCommandRegistry(): void
    {
        $configPath = dirname(__DIR__, 3) . '/config/autoload/commands.php';

        /** @var mixed $registeredCommands */
        $registeredCommands = require $configPath;

        self::assertSame(CliCommandRegistry::commandClasses(), $registeredCommands);
    }
}
