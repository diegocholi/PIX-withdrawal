<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Mail;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

abstract class MailhogIntegrationTestCase extends TestCase
{
    protected function uniqueEmail(string $prefix): string
    {
        return sprintf('%s-%s@example.test', $prefix, bin2hex(random_bytes(8)));
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function waitForMailhogMessage(string $recipient, string $subject, int $timeoutMs = 10000): ?array
    {
        $deadline = microtime(true) + ($timeoutMs / 1000);

        do {
            $payload = file_get_contents('http://mailhog:8025/api/v2/messages');

            self::assertNotFalse($payload, 'Unable to query MailHog API.');

            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

            foreach (($decoded['items'] ?? []) as $message) {
                if (! is_array($message)) {
                    continue;
                }

                $rawRecipients = $message['Raw']['To'] ?? [];
                $messageSubject = $message['Content']['Headers']['Subject'][0] ?? null;

                if (
                    is_array($rawRecipients)
                    && in_array($recipient, $rawRecipients, true)
                    && $messageSubject === $subject
                ) {
                    return $message;
                }
            }

            usleep(250000);
        } while (microtime(true) < $deadline);

        return null;
    }

    protected function nullStructuredLogger(): StructuredLogger
    {
        return new class implements StructuredLogger {
            public function emergency(string $message, LogContext $context): void
            {
            }

            public function alert(string $message, LogContext $context): void
            {
            }

            public function critical(string $message, LogContext $context): void
            {
            }

            public function error(string $message, LogContext $context): void
            {
            }

            public function warning(string $message, LogContext $context): void
            {
            }

            public function notice(string $message, LogContext $context): void
            {
            }

            public function info(string $message, LogContext $context): void
            {
            }

            public function debug(string $message, LogContext $context): void
            {
            }
        };
    }
}
