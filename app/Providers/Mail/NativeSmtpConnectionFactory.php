<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;

final class NativeSmtpConnectionFactory implements SmtpConnectionFactory
{
    public function connect(MailConfig $config): SmtpConnection
    {
        $stream = @stream_socket_client(
            $this->connectionAddress($config),
            $errorCode,
            $errorMessage,
            $config->connectTimeoutSeconds(),
            STREAM_CLIENT_CONNECT,
        );

        if (! is_resource($stream)) {
            throw SmtpTransportException::unableToConnect(
                $config->host(),
                $config->port(),
                trim(sprintf('[%d] %s', (int) $errorCode, (string) $errorMessage)),
            );
        }

        stream_set_blocking($stream, true);
        stream_set_timeout($stream, $config->readTimeoutSeconds());

        return new NativeSmtpConnection($stream);
    }

    private function connectionAddress(MailConfig $config): string
    {
        $scheme = $config->encryption() === 'ssl' ? 'ssl' : 'tcp';

        return sprintf('%s://%s:%d', $scheme, $config->host(), $config->port());
    }
}
