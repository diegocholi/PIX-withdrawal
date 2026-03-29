<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

final readonly class WithdrawNotificationTemplate
{
    /**
     * @return list<string>
     */
    public function placeholders(): array
    {
        return [
            'withdraw_processed_at',
            'withdraw_amount',
            'pix_key_type',
            'pix_key_masked',
        ];
    }

    public function contentType(): string
    {
        return 'text/plain; charset=UTF-8';
    }

    public function body(): string
    {
        return implode("\n", [
            'Sua solicitacao de saque PIX foi concluida.',
            '',
            'Data e hora do saque: {{withdraw_processed_at}}',
            'Valor sacado: R$ {{withdraw_amount}}',
            'Tipo de chave PIX: {{pix_key_type}}',
            'Chave PIX: {{pix_key_masked}}',
        ]);
    }
}
