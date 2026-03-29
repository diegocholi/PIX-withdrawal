<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Infrastructure\Contract;

interface WithdrawNotificationRecipientQuery
{
    public function findRecipientByWithdrawId(string $withdrawId): ?string;
}
