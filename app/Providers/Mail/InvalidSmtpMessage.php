<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

final class InvalidSmtpMessage extends \InvalidArgumentException
{
    public static function invalidRecipient(string $recipient): self
    {
        return new self(sprintf('SMTP recipient address is invalid: "%s".', $recipient));
    }

    public static function invalidSender(string $sender): self
    {
        return new self(sprintf('SMTP sender address is invalid: "%s".', $sender));
    }

    public static function emptySubject(): self
    {
        return new self('SMTP message subject must be non-empty.');
    }

    public static function emptyBody(): self
    {
        return new self('SMTP message body must be non-empty.');
    }
}
