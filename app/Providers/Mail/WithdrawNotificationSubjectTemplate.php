<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

final readonly class WithdrawNotificationSubjectTemplate
{
    public function value(): string
    {
        return 'Saque PIX concluido';
    }
}
