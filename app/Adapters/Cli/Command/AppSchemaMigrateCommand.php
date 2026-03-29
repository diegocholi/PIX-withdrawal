<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Command;

use Hyperf\Command\Command as HyperfCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExitCode;
use Tecnofit\PixWithdrawal\Plugins\Data\Migrations\DataSchemaMigrationPlan;

final class AppSchemaMigrateCommand extends HyperfCommand
{
    protected ?string $signature = 'app:schema:migrate';

    protected string $description = 'Aplica o schema canonico da aplicacao antes do runtime subir.';

    public function __construct(
        private readonly DataSchemaMigrationPlan $migrationPlan,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $this->migrationPlan->up();
        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());

            return CliExitCode::DEPENDENCY_FAILURE;
        }

        $this->line(json_encode([
            'command' => 'app:schema:migrate',
            'status' => 'ok',
            'schema' => 'current',
        ], JSON_THROW_ON_ERROR));

        return CliExitCode::SUCCESS;
    }
}
