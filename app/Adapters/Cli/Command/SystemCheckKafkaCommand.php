<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Command;

use Hyperf\Command\Command as HyperfCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Diagnostic\KafkaConnectivityCheck;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExitCode;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliOutputSanitizer;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;

final class SystemCheckKafkaCommand extends HyperfCommand
{
    protected ?string $signature = 'system:check:kafka';

    protected string $description = 'Verifica conectividade e operacao minima do cluster Kafka configurado.';

    public function __construct(
        private readonly KafkaConnectivityCheck $kafkaConnectivityCheck,
        private readonly CliOutputSanitizer $cliOutputSanitizer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $result = $this->kafkaConnectivityCheck->run();
        } catch (TransientInfrastructureException $exception) {
            $this->line($this->cliOutputSanitizer->encodeJson([
                'command' => 'system:check:kafka',
                'check' => 'kafka',
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ]));

            return CliExitCode::DEPENDENCY_FAILURE;
        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());

            return CliExitCode::RUNTIME_FAILURE;
        }

        $this->line($this->cliOutputSanitizer->encodeJson([
            'command' => 'system:check:kafka',
            'check' => 'kafka',
            'status' => 'ok',
            'client_id' => $result->clientId(),
            'configured_brokers_count' => $result->configuredBrokersCount(),
            'discovered_brokers_count' => $result->discoveredBrokersCount(),
            'discovered_topics_count' => $result->discoveredTopicsCount(),
            'origin_broker_id' => $result->originBrokerId(),
            'origin_broker_name' => $result->originBrokerName(),
        ]));

        return CliExitCode::SUCCESS;
    }
}
