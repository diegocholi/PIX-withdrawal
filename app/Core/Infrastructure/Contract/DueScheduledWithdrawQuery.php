<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Infrastructure\Contract;

use DateTimeImmutable;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;

interface DueScheduledWithdrawQuery
{
    /**
     * @return list<AccountWithdraw>
     */
    public function findDue(DateTimeImmutable $scheduledUntil, int $limit): array;
}
