<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Mail;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationSubjectTemplate;

final class WithdrawNotificationSubjectTemplateTest extends TestCase
{
    public function testSubjectTemplateRemainsReadonlyAndStable(): void
    {
        $template = new WithdrawNotificationSubjectTemplate();

        self::assertTrue((new ReflectionClass($template))->isReadOnly());
        self::assertSame('Saque PIX concluido', $template->value());
    }
}
