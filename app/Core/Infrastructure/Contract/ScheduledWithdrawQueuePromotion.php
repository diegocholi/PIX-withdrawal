<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Infrastructure\Contract;

use DateTimeImmutable;

interface ScheduledWithdrawQueuePromotion
{
    public function promote(string $withdrawId, DateTimeImmutable $queuedAt): bool;
}
