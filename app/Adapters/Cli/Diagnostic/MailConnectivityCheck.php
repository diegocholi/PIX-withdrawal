<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Diagnostic;

use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\UuidGenerator;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpMessage;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpWithdrawMailer;

final readonly class MailConnectivityCheck
{
    public function __construct(
        private MailConfig $mailConfig,
        private SmtpWithdrawMailer $smtpWithdrawMailer,
        private UuidGenerator $uuidGenerator,
    ) {
    }

    public function run(): MailConnectivityCheckResult
    {
        $recipient = $this->recipient();
        $subject = 'PIX Withdrawal CLI Mail Check';

        try {
            $this->smtpWithdrawMailer->send(new SmtpMessage(
                recipient: $recipient,
                subject: $subject,
                body: 'Minimal SMTP diagnostic message sent by system:check:mail.',
            ));
        } catch (\Throwable $throwable) {
            throw new TransientInfrastructureException('Mail connectivity check failed.', 0, $throwable);
        }

        return new MailConnectivityCheckResult(
            defaultMailer: (string) ($this->mailConfig->defaultMailer() ?? ''),
            transport: $this->mailConfig->transport(),
            host: $this->mailConfig->host(),
            port: $this->mailConfig->port(),
            fromAddress: $this->mailConfig->fromAddress(),
            recipient: $recipient,
            subject: $subject,
        );
    }

    private function recipient(): string
    {
        return sprintf('mail-check-%s@example.test', $this->uuidGenerator->generate());
    }
}
