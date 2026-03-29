<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Command;

use Hyperf\Command\Command as HyperfCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Diagnostic\MySqlConnectivityCheck;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExitCode;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliOutputSanitizer;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;

final class SystemCheckMySqlCommand extends HyperfCommand
{
    protected ?string $signature = 'system:check:mysql';

    protected string $description = 'Verifica conectividade e operacao minima da conexao MySQL configurada.';

    public function __construct(
        private readonly MySqlConnectivityCheck $mySqlConnectivityCheck,
        private readonly CliOutputSanitizer $cliOutputSanitizer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $result = $this->mySqlConnectivityCheck->run();
        } catch (TransientInfrastructureException $exception) {
            $this->line($this->cliOutputSanitizer->encodeJson([
                'command' => 'system:check:mysql',
                'check' => 'mysql',
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ]));

            return CliExitCode::DEPENDENCY_FAILURE;
        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());

            return CliExitCode::RUNTIME_FAILURE;
        }

        $this->line($this->cliOutputSanitizer->encodeJson([
            'command' => 'system:check:mysql',
            'check' => 'mysql',
            'status' => 'ok',
            'connection' => $result->connectionName(),
            'driver' => $result->driver(),
            'host' => $result->host(),
            'port' => $result->port(),
            'database' => $result->database(),
            'ping' => $result->ping(),
            'server_version' => $result->serverVersion(),
        ]));

        return CliExitCode::SUCCESS;
    }
}
