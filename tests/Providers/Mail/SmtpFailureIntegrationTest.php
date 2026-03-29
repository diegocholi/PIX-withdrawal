<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Mail;

use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;
use Tecnofit\PixWithdrawal\Providers\Mail\NativeSmtpConnectionFactory;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpMessage;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpSendFailurePolicy;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpTransportException;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpWithdrawMailer;

final class SmtpFailureIntegrationTest extends MailhogIntegrationTestCase
{
    public function testWrapsUnavailableSmtpServerAsTransientInfrastructureException(): void
    {
        $logger = $this->nullStructuredLogger();
        $mailer = new SmtpWithdrawMailer(
            MailConfig::fromArray([
                'default' => 'smtp',
                'mailers' => [
                    'smtp' => [
                        'transport' => 'smtp',
                        'host' => 'mailhog',
                        'port' => 65025,
                        'username' => null,
                        'password' => null,
                        'encryption' => null,
                        'from' => [
                            'address' => 'no-reply@example.test',
                            'name' => 'PIX Withdrawal',
                        ],
                        'timeouts' => [
                            'connect_seconds' => 1,
                            'read_seconds' => 1,
                        ],
                    ],
                ],
            ]),
            new NativeSmtpConnectionFactory(),
            $logger,
            new SmtpSendFailurePolicy($logger),
        );

        $this->expectException(TransientInfrastructureException::class);
        $this->expectExceptionMessage('SMTP message delivery failed due to a transient transport error.');

        $mailer->send(new SmtpMessage(
            recipient: $this->uniqueEmail('smtp-unavailable'),
            subject: 'SMTP unavailable integration',
            body: 'This message should fail due to connection refusal.',
        ));
    }

    public function testFailsFastWhenSmtpMailerIsMisconfigured(): void
    {
        $logger = $this->nullStructuredLogger();
        $mailer = new SmtpWithdrawMailer(
            MailConfig::fromArray([
                'default' => null,
                'mailers' => [],
            ]),
            new NativeSmtpConnectionFactory(),
            $logger,
            new SmtpSendFailurePolicy($logger),
        );

        $this->expectException(SmtpTransportException::class);
        $this->expectExceptionMessage('SMTP mailer is disabled for the current runtime.');

        $mailer->send(new SmtpMessage(
            recipient: $this->uniqueEmail('smtp-disabled'),
            subject: 'SMTP disabled integration',
            body: 'This message should fail before opening a socket.',
        ));
    }
}
