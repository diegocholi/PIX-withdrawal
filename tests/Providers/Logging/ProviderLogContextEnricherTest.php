<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Logging;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Logging\ProviderLogContextEnricher;

final class ProviderLogContextEnricherTest extends TestCase
{
    public function testAddsDefaultProviderAndOperationWhenTheyAreMissing(): void
    {
        $enricher = new ProviderLogContextEnricher();

        $context = $enricher->enrich(
            'kafka.publish',
            new LogContext(
                correlationId: 'corr-1',
                context: ['topic' => 'withdraw.process']
            )
        );

        self::assertSame([
            'provider' => 'providers',
            'operation' => 'kafka.publish',
            'topic' => 'withdraw.process',
        ], $context->context());
    }

    public function testPreservesOperationalFieldsAlreadyProvidedByCaller(): void
    {
        $enricher = new ProviderLogContextEnricher();

        $context = $enricher->enrich(
            'mail.send',
            new LogContext(
                correlationId: 'corr-2',
                context: [
                    'provider' => 'mail.smtp',
                    'operation' => 'notification.dispatch',
                    'recipient' => 'user@example.com',
                    'partition' => 3,
                ]
            )
        );

        self::assertSame([
            'provider' => 'mail.smtp',
            'operation' => 'notification.dispatch',
            'recipient' => 'user@example.com',
            'partition' => 3,
        ], $context->context());
    }
}
