<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

final readonly class SmtpWithdrawMailer
{
    public function __construct(
        private MailConfig $config,
        private SmtpConnectionFactory $connectionFactory,
        private StructuredLogger $structuredLogger,
        private SmtpSendFailurePolicy $failurePolicy,
    ) {
    }

    public function send(SmtpMessage $message): void
    {
        try {
            $this->assertMailerIsEnabled();
            $this->logAttempt($message);
            $this->deliver($message);
            $this->logSuccess($message);
        } catch (\Throwable $throwable) {
            $this->failurePolicy->handle($message, $throwable);
        }
    }

    private function deliver(SmtpMessage $message): void
    {
        $connection = $this->connectionFactory->connect($this->config);

        try {
            $this->expectResponse($connection, 'greeting', [220]);
            $this->sendCommand($connection, 'EHLO pix-withdrawal.local', [250], 'EHLO');

            if ($this->config->encryption() === 'tls') {
                $this->sendCommand($connection, 'STARTTLS', [220], 'STARTTLS');
                $connection->enableTls();
                $this->sendCommand($connection, 'EHLO pix-withdrawal.local', [250], 'EHLO');
            }

            $this->authenticate($connection);
            $this->sendCommand($connection, sprintf('MAIL FROM:<%s>', $message->senderAddress($this->config)), [250], 'MAIL FROM');
            $this->sendCommand($connection, sprintf('RCPT TO:<%s>', $message->recipient()), [250, 251], 'RCPT TO');
            $this->sendCommand($connection, 'DATA', [354], 'DATA');
            $connection->write($this->messageData($message) . "\r\n.\r\n");
            $this->expectResponse($connection, 'DATA payload', [250]);
            $this->sendQuit($connection);
        } finally {
            $connection->close();
        }
    }

    private function logAttempt(SmtpMessage $message): void
    {
        $this->structuredLogger->info(
            'mail.send.attempt',
            $this->logContext($message),
        );
    }

    private function logSuccess(SmtpMessage $message): void
    {
        $this->structuredLogger->info(
            'mail.send.succeeded',
            $this->logContext($message),
        );
    }

    private function authenticate(SmtpConnection $connection): void
    {
        if (! $this->config->hasAuthentication()) {
            return;
        }

        $this->sendCommand($connection, 'AUTH LOGIN', [334], 'AUTH LOGIN');
        $this->sendCommand($connection, base64_encode((string) $this->config->username()), [334], 'AUTH LOGIN username');
        $this->sendCommand($connection, base64_encode((string) $this->config->password()), [235], 'AUTH LOGIN password');
    }

    private function assertMailerIsEnabled(): void
    {
        if (
            $this->config->defaultMailer() !== 'smtp'
            || trim($this->config->host()) === ''
            || $this->config->port() <= 0
        ) {
            throw SmtpTransportException::mailerDisabled();
        }
    }

    /**
     * @param list<int> $expectedCodes
     */
    private function sendCommand(
        SmtpConnection $connection,
        string $command,
        array $expectedCodes,
        string $responseCommand,
    ): void {
        $connection->write($command . "\r\n");
        $this->expectResponse($connection, $responseCommand, $expectedCodes);
    }

    /**
     * @param list<int> $expectedCodes
     */
    private function expectResponse(SmtpConnection $connection, string $command, array $expectedCodes): void
    {
        $response = $this->readResponse($connection, $command);
        $code = (int) substr($response, 0, 3);

        if (in_array($code, $expectedCodes, true)) {
            return;
        }

        throw SmtpTransportException::unexpectedResponse($command, $expectedCodes, $response);
    }

    private function readResponse(SmtpConnection $connection, string $command): string
    {
        $lines = [];

        do {
            $line = $connection->readLine();

            if ($line === null) {
                throw $connection->didTimeOut()
                    ? SmtpTransportException::timedOut($command)
                    : SmtpTransportException::emptyResponse($command);
            }

            $lines[] = $line;
            $hasMoreLines = preg_match('/^\d{3}-/', $line) === 1;
        } while ($hasMoreLines);

        return implode("\n", $lines);
    }

    private function messageData(SmtpMessage $message): string
    {
        return implode("\r\n", [
            'From: ' . $this->formattedMailbox($message->senderAddress($this->config), $message->senderName($this->config)),
            'To: ' . $this->formattedMailbox($message->recipient()),
            'Subject: ' . $this->sanitizeHeaderValue($message->subject()),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $this->escapeBody($message->body()),
        ]);
    }

    private function formattedMailbox(string $address, ?string $name = null): string
    {
        if ($name === null || trim($name) === '') {
            return sprintf('<%s>', $this->sanitizeHeaderValue($address));
        }

        return sprintf(
            '%s <%s>',
            $this->sanitizeHeaderValue($name),
            $this->sanitizeHeaderValue($address),
        );
    }

    private function sanitizeHeaderValue(string $value): string
    {
        return trim(str_replace(["\r", "\n"], ' ', $value));
    }

    private function escapeBody(string $body): string
    {
        $normalizedBody = str_replace(["\r\n", "\r"], "\n", trim($body));

        $escapedLines = array_map(
            static fn (string $line): string => str_starts_with($line, '.') ? '.' . $line : $line,
            explode("\n", $normalizedBody),
        );

        return implode("\r\n", $escapedLines);
    }

    private function sendQuit(SmtpConnection $connection): void
    {
        try {
            $this->sendCommand($connection, 'QUIT', [221], 'QUIT');
        } catch (SmtpTransportException) {
        }
    }

    private function logContext(SmtpMessage $message): LogContext
    {
        return new LogContext(
            correlationId: 'mail-smtp',
            context: [
                'provider' => 'mail.smtp',
                'operation' => 'mail.send',
                'recipient' => $message->recipient(),
            ],
        );
    }
}
