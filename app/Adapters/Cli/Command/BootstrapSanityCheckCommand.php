<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\ConfigInterface;
use Psr\Clock\ClockInterface;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExitCode;

final class BootstrapSanityCheckCommand extends HyperfCommand
{
    protected ?string $signature = 'app:sanity-check';

    protected string $description = 'Confirma que o runtime CLI subiu e resolveu dependencias basicas.';

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->line(json_encode([
            'name' => (string) $this->config->get('app.name', 'pix-withdrawal'),
            'status' => 'ok',
            'runtime' => 'cli',
            'timestamp' => $this->clock->now()->format(DATE_ATOM),
        ], JSON_THROW_ON_ERROR));

        return CliExitCode::SUCCESS;
    }
}
