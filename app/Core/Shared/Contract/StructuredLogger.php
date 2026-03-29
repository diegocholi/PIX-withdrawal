<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared\Contract;

use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

interface StructuredLogger
{
    public function info(string $message, LogContext $context): void;

    public function warning(string $message, LogContext $context): void;

    public function error(string $message, LogContext $context): void;
}
