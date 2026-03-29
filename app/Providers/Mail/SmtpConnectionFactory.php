<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;

interface SmtpConnectionFactory
{
    public function connect(MailConfig $config): SmtpConnection;
}
