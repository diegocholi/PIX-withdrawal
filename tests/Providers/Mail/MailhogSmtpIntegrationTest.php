<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Mail;

use Tecnofit\PixWithdrawal\Core\Domain\Event\GenericDomainEvent;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;
use Tecnofit\PixWithdrawal\Providers\Mail\NativeSmtpConnectionFactory;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpSendFailurePolicy;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpWithdrawMailer;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationDispatcher;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationSubjectTemplate;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationTemplate;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationRenderer;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderPayloadNormalizer;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderSensitiveDataMasker;
use Tecnofit\PixWithdrawal\Providers\Serialization\SafeEventPayloadSerializer;

final class MailhogSmtpIntegrationTest extends MailhogIntegrationTestCase
{
    public function testDispatchesWithdrawNotificationToRealMailhogInbox(): void
    {
        $logger = $this->nullStructuredLogger();
        $recipient = $this->uniqueEmail('withdraw-mailhog');
        $dispatcher = new WithdrawNotificationDispatcher(
            new SmtpWithdrawMailer(
                $this->mailhogConfig(),
                new NativeSmtpConnectionFactory(),
                $logger,
                new SmtpSendFailurePolicy($logger),
            ),
            new WithdrawNotificationRenderer(
                new WithdrawNotificationTemplate(),
                new SafeEventPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer()),
            ),
            new WithdrawNotificationSubjectTemplate(),
            $logger,
        );

        $dispatcher->dispatch(
            new GenericDomainEvent(
                eventName: 'withdraw.processed',
                aggregateId: 'wd-mailhog-1',
                occurredAt: '2026-03-28T20:00:00-03:00',
                correlationId: 'corr-mailhog-1',
                payload: [
                    'withdraw_id' => 'wd-mailhog-1',
                    'account_id' => 'acc-mailhog-1',
                    'amount' => '1250.50',
                    'method' => 'PIX',
                    'status' => 'DONE',
                    'pix_key_type' => 'EMAIL',
                    'pix_key_masked' => 'u***@example.test',
                ],
            ),
            $recipient,
        );

        $message = $this->waitForMailhogMessage(
            $recipient,
            'Saque PIX concluido',
        );

        self::assertNotNull($message, 'MailHog did not receive the SMTP notification within the expected timeout.');
        self::assertSame([$recipient], $message['Raw']['To']);
        self::assertSame('Saque PIX concluido', $message['Content']['Headers']['Subject'][0] ?? null);
        self::assertStringContainsString(
            'Sua solicitacao de saque PIX foi concluida.',
            (string) ($message['Content']['Body'] ?? ''),
        );
        self::assertStringContainsString(
            'Valor sacado: R$ 1.250,50',
            (string) ($message['Content']['Body'] ?? ''),
        );
        self::assertStringContainsString(
            'Chave PIX: u***@example.test',
            (string) ($message['Content']['Body'] ?? ''),
        );
    }

    private function mailhogConfig(): MailConfig
    {
        return MailConfig::fromArray([
            'default' => 'smtp',
            'mailers' => [
                'smtp' => [
                    'transport' => 'smtp',
                    'host' => 'mailhog',
                    'port' => 1025,
                    'username' => null,
                    'password' => null,
                    'encryption' => null,
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
