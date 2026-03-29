<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Mail;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;
use Tecnofit\PixWithdrawal\Providers\Mail\InvalidSmtpMessage;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpMessage;

final class SmtpMessageTest extends TestCase
{
    public function testUsesConfigSenderWhenMessageDoesNotOverrideIt(): void
    {
        $message = new SmtpMessage(
            recipient: 'customer@example.test',
            subject: 'Withdraw processed',
            body: 'Your withdraw was processed.',
        );

        self::assertSame('no-reply@example.test', $message->senderAddress($this->mailConfig()));
        self::assertSame('PIX Withdrawal', $message->senderName($this->mailConfig()));
    }

    public function testRejectsInvalidRecipientAddress(): void
    {
        $this->expectException(InvalidSmtpMessage::class);
        $this->expectExceptionMessage('SMTP recipient address is invalid: "invalid-recipient".');

        new SmtpMessage(
            recipient: 'invalid-recipient',
            subject: 'Withdraw processed',
            body: 'Your withdraw was processed.',
        );
    }

    public function testRejectsEmptySubject(): void
    {
        $this->expectException(InvalidSmtpMessage::class);
        $this->expectExceptionMessage('SMTP message subject must be non-empty.');

        new SmtpMessage(
            recipient: 'customer@example.test',
            subject: '  ',
            body: 'Your withdraw was processed.',
        );
    }

    private function mailConfig(): MailConfig
    {
        return MailConfig::fromArray([
            'default' => 'smtp',
            'mailers' => [
                'smtp' => [
                    'transport' => 'smtp',
                    'host' => 'mailhog',
                    'port' => 1025,
                    'from' => [
                        'address' => 'no-reply@example.test',
                        'name' => 'PIX Withdrawal',
                    ],
                    'timeouts' => [
                        'connect_seconds' => 3,
                        'read_seconds' => 7,
                    ],
                ],
            ],
        ]);
    }
}
