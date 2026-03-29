<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

use Throwable;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

final readonly class SmtpSendFailurePolicy
{
    public function __construct(private StructuredLogger $structuredLogger)
    {
    }

    public function handle(SmtpMessage $message, Throwable $throwable): never
    {
        $this->logFailure($message, $throwable);

        if ($throwable instanceof SmtpTransportException && $throwable->isRetryable()) {
            throw new TransientInfrastructureException(
                'SMTP message delivery failed due to a transient transport error.',
                context: [
                    'provider' => 'mail.smtp',
                    'recipient' => $message->recipient(),
                ],
                previous: $throwable,
            );
        }

        throw $throwable;
    }

    private function logFailure(SmtpMessage $message, Throwable $throwable): void
    {
        $context = new LogContext(
            correlationId: 'mail-smtp',
            errorCode: $this->errorCode($throwable),
            context: [
                'provider' => 'mail.smtp',
                'operation' => 'mail.send',
                'recipient' => $message->recipient(),
                'reason' => $throwable->getMessage(),
                'retryable' => $throwable instanceof SmtpTransportException && $throwable->isRetryable(),
            ],
        );

        if ($throwable instanceof SmtpTransportException && $throwable->isRetryable()) {
            $this->structuredLogger->warning('mail.send.failed.retryable', $context);

            return;
        }

        $this->structuredLogger->error('mail.send.failed', $context);
    }

    private function errorCode(Throwable $throwable): string
    {
        if ($throwable instanceof SmtpTransportException && $throwable->isRetryable()) {
            return 'mail.smtp.transient_failure';
        }

        return 'mail.smtp.delivery_failed';
    }
}
