<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared\Contract;

use Throwable;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\FailureCategory;

interface FailureClassifier
{
    public function classify(Throwable $throwable): FailureCategory;
}
