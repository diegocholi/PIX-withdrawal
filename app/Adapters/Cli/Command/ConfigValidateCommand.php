<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Command;

use Hyperf\Command\Command as HyperfCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Diagnostic\ConfigValidation;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExitCode;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliOutputSanitizer;

final class ConfigValidateCommand extends HyperfCommand
{
    protected ?string $signature = 'app:config:validate';

    protected string $description = 'Valida a presenca e a coerencia das configuracoes criticas da aplicacao.';

    public function __construct(
        private readonly ConfigValidation $configValidation,
        private readonly CliOutputSanitizer $cliOutputSanitizer,
    ) {
        parent::__construct();

        $this->setAliases(['config:validate']);
    }

    public function handle(): int
    {
        try {
            $result = $this->configValidation->validate();
        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());

            return CliExitCode::RUNTIME_FAILURE;
        }

        $this->line($this->cliOutputSanitizer->encodeJson([
            'command' => 'app:config:validate',
            'status' => $result->status(),
            'summary' => [
                'total' => $result->totalChecks(),
                'ok' => $result->okCount(),
                'failed' => $result->failedCount(),
            ],
            'checks' => $result->checksByName(),
        ]));

        if ($result->hasPartialFailure()) {
            return CliExitCode::PARTIAL_FAILURE;
        }

        if ($result->hasFailures()) {
            return CliExitCode::DEPENDENCY_FAILURE;
        }

        return CliExitCode::SUCCESS;
    }
}
