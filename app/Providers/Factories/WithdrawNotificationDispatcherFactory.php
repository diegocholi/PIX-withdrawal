<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\EventPayloadSerializer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpWithdrawMailer;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationDispatcher;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationRenderer;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationSubjectTemplate;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationTemplate;

final readonly class WithdrawNotificationDispatcherFactory
{
    public function __construct(
        private SmtpWithdrawMailer $smtpWithdrawMailer,
        private EventPayloadSerializer $eventPayloadSerializer,
        private StructuredLogger $structuredLogger,
    ) {
    }

    public function create(): WithdrawNotificationDispatcher
    {
        return new WithdrawNotificationDispatcher(
            $this->smtpWithdrawMailer,
            new WithdrawNotificationRenderer(
                new WithdrawNotificationTemplate(),
                $this->eventPayloadSerializer,
            ),
            new WithdrawNotificationSubjectTemplate(),
            $this->structuredLogger,
        );
    }
}
