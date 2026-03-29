<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Mail;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpMessage;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpSendFailurePolicy;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpTransportException;

final class SmtpSendFailurePolicyTest extends TestCase
{
    public function testLogsRetryableTransportFailureAndWrapsItAsTransientInfrastructureException(): void
    {
        $logger = $this->createMock(StructuredLogger::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'mail.send.failed.retryable',
                self::callback(static function (LogContext $context): bool {
                    return $context->toArray()['error_code'] === 'mail.smtp.transient_failure'
                        && $context->context()['recipient'] === 'customer@example.test'
                        && $context->context()['retryable'] === true;
                }),
            );
        $logger->expects(self::never())->method('error');

        $policy = new SmtpSendFailurePolicy($logger);

        $this->expectException(TransientInfrastructureException::class);
        $this->expectExceptionMessage('SMTP message delivery failed due to a transient transport error.');

        $policy->handle(
            new SmtpMessage(
                recipient: 'customer@example.test',
                subject: 'Withdraw processed',
                body: 'Your withdraw was processed.',
            ),
            SmtpTransportException::timedOut('DATA'),
        );
    }

    public function testLogsPermanentFailureAndPropagatesOriginalException(): void
    {
        $logger = $this->createMock(StructuredLogger::class);
        $logger->expects(self::never())->method('warning');
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'mail.send.failed',
                self::callback(static function (LogContext $context): bool {
                    return $context->toArray()['error_code'] === 'mail.smtp.delivery_failed'
                        && $context->context()['retryable'] === false;
                }),
            );

        $policy = new SmtpSendFailurePolicy($logger);
        $exception = SmtpTransportException::unexpectedResponse('RCPT TO', [250, 251], '550 mailbox unavailable');

        $this->expectExceptionObject($exception);

        $policy->handle(
            new SmtpMessage(
                recipient: 'customer@example.test',
                subject: 'Withdraw processed',
                body: 'Your withdraw was processed.',
            ),
            $exception,
        );
    }
}
