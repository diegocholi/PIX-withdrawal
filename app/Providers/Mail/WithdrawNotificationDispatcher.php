<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

final readonly class WithdrawNotificationDispatcher
{
    public function __construct(
        private SmtpWithdrawMailer $smtpWithdrawMailer,
        private WithdrawNotificationRenderer $withdrawNotificationRenderer,
        private WithdrawNotificationSubjectTemplate $withdrawNotificationSubjectTemplate,
        private StructuredLogger $structuredLogger,
    ) {
    }

    public function dispatch(DomainEvent $event, string $recipient): void
    {
        $this->dispatchCommand(new WithdrawNotificationDispatch($event, $recipient));
    }

    public function dispatchCommand(WithdrawNotificationDispatch $dispatch): void
    {
        $event = $dispatch->event();

        $this->structuredLogger->info(
            'notification.dispatch.started',
            $this->logContext($event, $dispatch->recipient()),
        );

        $this->smtpWithdrawMailer->send(new SmtpMessage(
            recipient: $dispatch->recipient(),
            subject: $this->withdrawNotificationSubjectTemplate->value(),
            body: $this->withdrawNotificationRenderer->render($event),
        ));

        $this->structuredLogger->info(
            'notification.dispatch.sent',
            $this->logContext($event, $dispatch->recipient()),
        );
    }

    private function logContext(DomainEvent $event, string $recipient): LogContext
    {
        return new LogContext(
            correlationId: $event->correlationId(),
            withdrawId: $event->aggregateId(),
            context: [
                'provider' => 'mail.smtp',
                'operation' => 'notification.dispatch',
                'recipient' => trim($recipient),
                'event_name' => $event->eventName(),
            ],
        );
    }
}
