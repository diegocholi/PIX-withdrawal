<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

final class NativeSmtpConnection implements SmtpConnection
{
    /**
     * @param resource $stream
     */
    public function __construct(private $stream)
    {
    }

    public function readLine(): ?string
    {
        $line = fgets($this->stream);

        if ($line === false) {
            return null;
        }

        return rtrim($line, "\r\n");
    }

    public function write(string $payload): void
    {
        $remainingPayload = $payload;

        while ($remainingPayload !== '') {
            $writtenBytes = fwrite($this->stream, $remainingPayload);

            if (! is_int($writtenBytes) || $writtenBytes <= 0) {
                throw SmtpTransportException::writeFailed($this->summarizeCommand($payload));
            }

            $remainingPayload = substr($remainingPayload, $writtenBytes);
        }
    }

    public function enableTls(): void
    {
        $enabled = stream_socket_enable_crypto(
            $this->stream,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT,
        );

        if ($enabled === true) {
            return;
        }

        throw SmtpTransportException::tlsNegotiationFailed();
    }

    public function didTimeOut(): bool
    {
        $metadata = stream_get_meta_data($this->stream);

        return (bool) ($metadata['timed_out'] ?? false);
    }

    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    private function summarizeCommand(string $payload): string
    {
        $firstLine = strtok($payload, "\r\n");

        return $firstLine === false ? 'payload' : $firstLine;
    }
}
