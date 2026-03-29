<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Command;

use Hyperf\Command\Command as HyperfCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExitCode;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExecutionCorrelationProvider;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliOptionName;
use Tecnofit\PixWithdrawal\Adapters\Cli\Worker\WorkerRuntimeOptions;
use Tecnofit\PixWithdrawal\Adapters\Cli\Worker\WithdrawProcessingWorker;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;

final class WithdrawProcessWorkerCommand extends HyperfCommand
{
    protected ?string $signature = 'withdraw:worker:process'
        . ' {--' . CliOptionName::MAX_MESSAGES . '=0 : Limita quantas mensagens o worker consumira antes de encerrar}'
        . ' {--' . CliOptionName::GROUP_ID . '= : Sobrescreve o consumer group usado pelo worker}'
        . ' {--' . CliOptionName::TOPIC . '= : Sobrescreve o topico Kafka consumido pelo worker}'
        . ' {--' . CliOptionName::POLL_TIMEOUT . '= : Define o timeout de poll do consumer em milissegundos}';

    protected string $description = 'Inicia o worker Kafka responsavel pelo processamento financeiro dos saques.';

    public function __construct(
        private readonly WithdrawProcessingWorker $withdrawProcessingWorker,
        private readonly KafkaConfig $kafkaConfig,
        private readonly CliExecutionCorrelationProvider $executionCorrelationProvider,
    )
    {
        parent::__construct();

        $this->setAliases(['worker:withdraw:run']);
    }

    public function handle(): int
    {
        $runtimeOptions = $this->resolveRuntimeOptions();

        if ($runtimeOptions === null) {
            return CliExitCode::INVALID_ARGUMENT;
        }

        try {
            $result = $this->withdrawProcessingWorker->run($runtimeOptions);
        } catch (TransientInfrastructureException $exception) {
            $this->error($exception->getMessage());

            return CliExitCode::DEPENDENCY_FAILURE;
        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());

            return CliExitCode::RUNTIME_FAILURE;
        }

        $this->line(json_encode([
            'command' => 'withdraw:worker:process',
            'status' => $result->interrupted() ? 'interrupted' : 'ok',
            'max_messages' => $runtimeOptions->maxMessages(),
            'group_id' => $runtimeOptions->groupId(),
            'topic' => $runtimeOptions->topic(),
            'poll_timeout_ms' => $runtimeOptions->pollTimeoutMs(),
            'processed_messages' => $result->processedMessages(),
        ], JSON_THROW_ON_ERROR));

        return $result->interrupted()
            ? CliExitCode::INTERRUPTED
            : CliExitCode::SUCCESS;
    }

    private function resolveRuntimeOptions(): ?WorkerRuntimeOptions
    {
        $maxMessages = filter_var(
            $this->option(CliOptionName::MAX_MESSAGES),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]],
        );

        if (! is_int($maxMessages)) {
            $this->error(sprintf(
                'The --%s option must be an integer greater than or equal to zero.',
                CliOptionName::MAX_MESSAGES,
            ));

            return null;
        }

        $groupId = trim((string) $this->option(CliOptionName::GROUP_ID));
        $groupId = $groupId === '' ? $this->kafkaConfig->consumerGroup('withdraw') : $groupId;

        $topic = trim((string) $this->option(CliOptionName::TOPIC));
        $topic = $topic === '' ? $this->kafkaConfig->withdrawProcessTopic() : $topic;

        $pollTimeoutOption = trim((string) $this->option(CliOptionName::POLL_TIMEOUT));
        $pollTimeoutMs = $pollTimeoutOption === ''
            ? $this->kafkaConfig->operationTimeoutMs()
            : filter_var(
                $pollTimeoutOption,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]],
            );

        if (! is_int($pollTimeoutMs)) {
            $this->error(sprintf(
                'The --%s option must be an integer greater than zero.',
                CliOptionName::POLL_TIMEOUT,
            ));

            return null;
        }

        return new WorkerRuntimeOptions(
            maxMessages: $maxMessages,
            groupId: $groupId,
            topic: $topic,
            pollTimeoutMs: $pollTimeoutMs,
            correlationId: $this->executionCorrelationProvider->resolve($this->input),
        );
    }
}
