<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;

final readonly class SmtpMessage
{
    public function __construct(
        private string $recipient,
        private string $subject,
        private string $body,
        private ?string $senderAddress = null,
        private ?string $senderName = null,
    ) {
        $this->assertEmail($this->recipient, 'recipient');
        $this->assertNonEmpty($this->subject, 'subject');
        $this->assertNonEmpty($this->body, 'body');

        if ($this->senderAddress !== null) {
            $this->assertEmail($this->senderAddress, 'sender');
        }
    }

    public function recipient(): string
    {
        return $this->recipient;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function senderAddress(MailConfig $config): string
    {
        return $this->senderAddress ?? $config->fromAddress();
    }

    public function senderName(MailConfig $config): string
    {
        return $this->senderName ?? $config->fromName();
    }

    private function assertEmail(string $value, string $field): void
    {
        $normalized = trim($value);

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) !== false) {
            return;
        }

        throw $field === 'recipient'
            ? InvalidSmtpMessage::invalidRecipient($value)
            : InvalidSmtpMessage::invalidSender($value);
    }

    private function assertNonEmpty(string $value, string $field): void
    {
        if (trim($value) !== '') {
            return;
        }

        throw $field === 'subject'
            ? InvalidSmtpMessage::emptySubject()
            : InvalidSmtpMessage::emptyBody();
    }
}
