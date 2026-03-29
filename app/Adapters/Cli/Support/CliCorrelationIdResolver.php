<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Support;

use Symfony\Component\Console\Input\InputInterface;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\CorrelationIdGenerator;

final readonly class CliCorrelationIdResolver
{
    public function __construct(
        private CorrelationIdGenerator $correlationIdGenerator,
    ) {
    }

    public function resolve(InputInterface $input): string
    {
        $correlationId = trim((string) $input->getParameterOption('--' . CliOptionName::CORRELATION_ID, ''));

        if ($correlationId !== '') {
            return $correlationId;
        }

        return $this->correlationIdGenerator->generate();
    }
}
