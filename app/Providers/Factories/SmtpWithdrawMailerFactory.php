<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Providers\Mail\NativeSmtpConnectionFactory;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpSendFailurePolicy;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpWithdrawMailer;

final readonly class SmtpWithdrawMailerFactory
{
    public function __construct(
        private SmtpConfigFactory $smtpConfigFactory,
        private StructuredLogger $structuredLogger,
    )
    {
    }

    public function create(): SmtpWithdrawMailer
    {
        return new SmtpWithdrawMailer(
            $this->smtpConfigFactory->create(),
            new NativeSmtpConnectionFactory(),
            $this->structuredLogger,
            new SmtpSendFailurePolicy($this->structuredLogger),
        );
    }
}
