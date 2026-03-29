<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Command;

use Hyperf\Command\Command as HyperfCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Diagnostic\MailConnectivityCheck;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExitCode;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliOutputSanitizer;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;

final class SystemCheckMailCommand extends HyperfCommand
{
    protected ?string $signature = 'system:check:mail';

    protected string $description = 'Verifica conectividade SMTP e envio minimo usando o mailer configurado.';

    public function __construct(
        private readonly MailConnectivityCheck $mailConnectivityCheck,
        private readonly CliOutputSanitizer $cliOutputSanitizer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $result = $this->mailConnectivityCheck->run();
        } catch (TransientInfrastructureException $exception) {
            $this->line($this->cliOutputSanitizer->encodeJson([
                'command' => 'system:check:mail',
                'check' => 'mail',
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ]));

            return CliExitCode::DEPENDENCY_FAILURE;
        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());

            return CliExitCode::RUNTIME_FAILURE;
        }

        $this->line($this->cliOutputSanitizer->encodeJson([
            'command' => 'system:check:mail',
            'check' => 'mail',
            'status' => 'ok',
            'default_mailer' => $result->defaultMailer(),
            'transport' => $result->transport(),
            'host' => $result->host(),
            'port' => $result->port(),
            'from_address' => $result->fromAddress(),
            'recipient' => $result->recipient(),
            'subject' => $result->subject(),
        ]));

        return CliExitCode::SUCCESS;
    }
}
