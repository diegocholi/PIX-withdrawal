<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Mail;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Event\GenericDomainEvent;
use Tecnofit\PixWithdrawal\Providers\Mail\InvalidWithdrawNotificationEvent;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationRenderer;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationTemplate;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderPayloadNormalizer;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderSensitiveDataMasker;
use Tecnofit\PixWithdrawal\Providers\Serialization\SafeEventPayloadSerializer;

final class WithdrawNotificationRendererTest extends TestCase
{
    public function testRendersProcessedWithdrawNotificationFromSerializedEvent(): void
    {
        $renderer = $this->renderer();

        $content = $renderer->render(new GenericDomainEvent(
            eventName: 'withdraw.processed',
            aggregateId: 'wd-1',
            occurredAt: '2026-03-28T10:03:00-03:00',
            correlationId: 'corr-1',
            payload: [
                'withdraw_id' => 'wd-1',
                'account_id' => 'acc-1',
                'amount' => '1250.5',
                'method' => 'PIX',
                'status' => 'DONE',
                'pix_key_type' => 'EMAIL',
                'pix_key_masked' => 'u***@example.com',
            ],
        ));

        self::assertSame(
            implode("\n", [
                'Sua solicitacao de saque PIX foi concluida.',
                '',
                'Data e hora do saque: 28/03/2026 10:03:00',
                'Valor sacado: R$ 1.250,50',
                'Tipo de chave PIX: EMAIL',
                'Chave PIX: u***@example.com',
            ]),
            $content,
        );
    }

    public function testRejectsUnsupportedEventName(): void
    {
        $renderer = $this->renderer();

        $this->expectException(InvalidWithdrawNotificationEvent::class);
        $this->expectExceptionMessage(
            'Withdraw notification renderer supports only "withdraw.processed" events, got "withdraw.failed".'
        );

        $renderer->render(new GenericDomainEvent(
            eventName: 'withdraw.failed',
            aggregateId: 'wd-1',
            occurredAt: '2026-03-28T10:03:00-03:00',
            correlationId: 'corr-1',
            payload: [],
        ));
    }

    public function testFailsWhenSerializedEventDoesNotContainRequiredNotificationFields(): void
    {
        $renderer = $this->renderer();

        $this->expectException(InvalidWithdrawNotificationEvent::class);
        $this->expectExceptionMessage(
            'Withdraw notification renderer requires the field "pix_key_masked" in the serialized event payload.'
        );

        $renderer->render(new GenericDomainEvent(
            eventName: 'withdraw.processed',
            aggregateId: 'wd-1',
            occurredAt: '2026-03-28T10:03:00-03:00',
            correlationId: 'corr-1',
            payload: [
                'amount' => '25.00',
                'pix_key_type' => 'EMAIL',
            ],
        ));
    }

    private function renderer(): WithdrawNotificationRenderer
    {
        return new WithdrawNotificationRenderer(
            new WithdrawNotificationTemplate(),
            new SafeEventPayloadSerializer(
                new ProviderSensitiveDataMasker(),
                new ProviderPayloadNormalizer(),
            ),
        );
    }
}
