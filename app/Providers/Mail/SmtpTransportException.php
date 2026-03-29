<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

final class SmtpTransportException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly bool $retryable = false,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    public static function mailerDisabled(): self
    {
        return new self('SMTP mailer is disabled for the current runtime.');
    }

    public static function unableToConnect(string $host, int $port, string $reason): self
    {
        return new self(sprintf('Unable to connect to SMTP server %s:%d: %s', $host, $port, $reason), true);
    }

    /**
     * @param list<int> $expectedCodes
     */
    public static function unexpectedResponse(string $command, array $expectedCodes, string $response): self
    {
        return new self(
            sprintf(
                'SMTP command "%s" expected response code [%s], got: %s',
                $command,
                implode(', ', $expectedCodes),
                $response,
            ),
            self::responseIsRetryable($response),
        );
    }

    public static function emptyResponse(string $command): self
    {
        return new self(sprintf('SMTP server returned an empty response for "%s".', $command), true);
    }

    public static function timedOut(string $command): self
    {
        return new self(sprintf('SMTP operation timed out while waiting for "%s".', $command), true);
    }

    public static function writeFailed(string $command): self
    {
        return new self(sprintf('SMTP command "%s" could not be written to the socket.', $command), true);
    }

    public static function tlsNegotiationFailed(): self
    {
        return new self('SMTP STARTTLS negotiation failed.', true);
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    private static function responseIsRetryable(string $response): bool
    {
        $code = (int) substr(ltrim($response), 0, 3);

        return $code >= 400 && $code < 500;
    }
}
