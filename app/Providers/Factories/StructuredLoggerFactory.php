<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;

final readonly class StructuredLoggerFactory
{
    public function __construct(private Observability $observability)
    {
    }

    public function create(): StructuredLogger
    {
        return $this->observability;
    }
}
