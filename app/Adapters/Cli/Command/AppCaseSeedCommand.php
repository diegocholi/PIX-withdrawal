<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Command;

use Hyperf\Command\Command as HyperfCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExitCode;
use Tecnofit\PixWithdrawal\Plugins\Data\Seeds\PixWithdrawalCaseSeeder;

final class AppCaseSeedCommand extends HyperfCommand
{
    protected ?string $signature = 'app:seed:case';

    protected string $description = 'Aplica a massa canonica do case de saque PIX apenas uma vez por banco.';

    public function __construct(private readonly PixWithdrawalCaseSeeder $seeder)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $result = $this->seeder->seed();
        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());

            return CliExitCode::DEPENDENCY_FAILURE;
        }

        $this->line(json_encode([
            'command' => 'app:seed:case',
            'seed' => $result->seedName(),
            'status' => $result->status(),
            'executed_at' => $result->executedAt()->format(DATE_ATOM),
        ], JSON_THROW_ON_ERROR));

        return CliExitCode::SUCCESS;
    }
}
