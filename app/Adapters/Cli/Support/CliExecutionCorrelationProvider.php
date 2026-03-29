<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Support;

use Symfony\Component\Console\Input\InputInterface;

final readonly class CliExecutionCorrelationProvider
{
    public function __construct(
        private CliExecutionContext $executionContext,
        private CliCorrelationIdResolver $correlationIdResolver,
    ) {
    }

    public function resolve(InputInterface $input): string
    {
        $activeCorrelationId = $this->executionContext->correlationId();

        if ($activeCorrelationId !== null) {
            return $activeCorrelationId;
        }

        $correlationId = $this->correlationIdResolver->resolve($input);
        $this->executionContext->activate($correlationId);

        return $correlationId;
    }
}
