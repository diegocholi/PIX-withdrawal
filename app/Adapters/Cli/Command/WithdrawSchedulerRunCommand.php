<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Command;

use Hyperf\Command\Command as HyperfCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Scheduler\ScheduledWithdrawScheduler;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExitCode;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExecutionCorrelationProvider;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliSensitiveCommandGuard;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliOptionName;

final class WithdrawSchedulerRunCommand extends HyperfCommand
{
    protected ?string $signature = 'withdraw:scheduler:run'
        . ' {--' . CliOptionName::BATCH_SIZE . '=100 : Limita quantos saques vencidos serao avaliados na execucao}'
        . ' {--' . CliOptionName::DRY_RUN . ' : Simula a execucao sem promover saques para a fila}'
        . ' {--' . CliOptionName::FORCE . ' : Confirma a promocao manual dos saques elegiveis para a fila}';

    protected string $description = 'Promove saques agendados vencidos para a fila interna de processamento.';

    public function __construct(
        private readonly ScheduledWithdrawScheduler $scheduledWithdrawScheduler,
        private readonly CliExecutionCorrelationProvider $executionCorrelationProvider,
        private readonly CliSensitiveCommandGuard $cliSensitiveCommandGuard,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $batchSize = $this->resolveBatchSize();

        if ($batchSize === null) {
            return CliExitCode::INVALID_ARGUMENT;
        }

        $dryRun = (bool) $this->option(CliOptionName::DRY_RUN);
        $missingForceMessage = $this->cliSensitiveCommandGuard->missingForceMessage(
            commandName: 'withdraw:scheduler:run',
            force: (bool) $this->option(CliOptionName::FORCE),
            dryRun: $dryRun,
        );

        if ($missingForceMessage !== null) {
            $this->error($missingForceMessage);

            return CliExitCode::INVALID_ARGUMENT;
        }

        try {
            $result = $this->scheduledWithdrawScheduler->run(
                batchSize: $batchSize,
                dryRun: $dryRun,
                executionCorrelationId: $this->executionCorrelationProvider->resolve($this->input),
            );
        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());

            return CliExitCode::RUNTIME_FAILURE;
        }

        $this->line(json_encode([
            'command' => 'withdraw:scheduler:run',
            'status' => $result->dryRun()
                ? 'simulated'
                : ($result->hasPartialFailure() ? 'partial' : 'ok'),
            'dry_run' => $result->dryRun(),
            'batch_size' => $result->batchSize(),
            'scheduled_until' => $result->scheduledUntil()->format(DATE_ATOM),
            'summary' => [
                'due' => $result->dueCount(),
                'promoted' => $result->promotedCount(),
                'published' => $result->publishedCount(),
                'skipped' => $result->skippedCount(),
                'publication_failed' => $result->publicationFailedCount(),
            ],
            'withdraw_ids' => [
                'due' => $result->dueWithdrawIds(),
                'promoted' => $result->promotedWithdrawIds(),
                'published' => $result->publishedWithdrawIds(),
                'skipped' => $result->skippedWithdrawIds(),
                'publication_failed' => $result->publicationFailedWithdrawIds(),
            ],
        ], JSON_THROW_ON_ERROR));

        return $result->hasPartialFailure()
            ? CliExitCode::PARTIAL_FAILURE
            : CliExitCode::SUCCESS;
    }

    private function resolveBatchSize(): ?int
    {
        $batchSize = filter_var(
            $this->option(CliOptionName::BATCH_SIZE),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if (is_int($batchSize)) {
            return $batchSize;
        }

        $this->error(sprintf('The --%s option must be an integer greater than zero.', CliOptionName::BATCH_SIZE));

        return null;
    }
}
