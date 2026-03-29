<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Diagnostic;

final readonly class MailConnectivityCheckResult
{
    public function __construct(
        private string $defaultMailer,
        private string $transport,
        private string $host,
        private int $port,
        private string $fromAddress,
        private string $recipient,
        private string $subject,
    ) {
    }

    public function defaultMailer(): string
    {
        return $this->defaultMailer;
    }

    public function transport(): string
    {
        return $this->transport;
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int
    {
        return $this->port;
    }

    public function fromAddress(): string
    {
        return $this->fromAddress;
    }

    public function recipient(): string
    {
        return $this->recipient;
    }

    public function subject(): string
    {
        return $this->subject;
    }
}
