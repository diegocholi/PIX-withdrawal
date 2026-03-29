<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Command;

use Hyperf\Command\Command as HyperfCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Diagnostic\SystemCheckAll;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExitCode;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliOutputSanitizer;

final class SystemCheckAllCommand extends HyperfCommand
{
    protected ?string $signature = 'system:check:all';

    protected string $description = 'Executa as verificacoes principais do ambiente: MySQL, Kafka e mail.';

    public function __construct(
        private readonly SystemCheckAll $systemCheckAll,
        private readonly CliOutputSanitizer $cliOutputSanitizer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $result = $this->systemCheckAll->run();
        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());

            return CliExitCode::RUNTIME_FAILURE;
        }

        $this->line($this->cliOutputSanitizer->encodeJson([
            'command' => 'system:check:all',
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
