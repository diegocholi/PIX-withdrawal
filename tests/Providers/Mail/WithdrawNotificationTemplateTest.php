<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Mail;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationTemplate;

final class WithdrawNotificationTemplateTest extends TestCase
{
    public function testTemplateRemainsReadonlyAndUsesPlainTextContentType(): void
    {
        $template = new WithdrawNotificationTemplate();

        self::assertTrue((new ReflectionClass($template))->isReadOnly());
        self::assertSame('text/plain; charset=UTF-8', $template->contentType());
    }

    public function testTemplateBodyContainsRequiredWithdrawAndPixPlaceholders(): void
    {
        $template = new WithdrawNotificationTemplate();

        self::assertSame(
            implode("\n", [
                'Sua solicitacao de saque PIX foi concluida.',
                '',
                'Data e hora do saque: {{withdraw_processed_at}}',
                'Valor sacado: R$ {{withdraw_amount}}',
                'Tipo de chave PIX: {{pix_key_type}}',
                'Chave PIX: {{pix_key_masked}}',
            ]),
            $template->body(),
        );
        self::assertSame(
            [
                'withdraw_processed_at',
                'withdraw_amount',
                'pix_key_type',
                'pix_key_masked',
            ],
            $template->placeholders(),
        );
    }
}
