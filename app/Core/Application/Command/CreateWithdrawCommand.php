<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Command;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\Traceable;

interface CreateWithdrawCommand extends Traceable
{
    public function accountId(): string;

    public function method(): string;

    public function pixKeyType(): string;

    public function pixKey(): string;

    public function amount(): string;

    public function scheduleAt(): ?string;
}
