<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Bootstrap;

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

final readonly class CliCommandRegistry
{
    /**
     * @return list<class-string>
     */
    public static function commandClasses(): array
    {
        return [
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
        ];
    }
}
