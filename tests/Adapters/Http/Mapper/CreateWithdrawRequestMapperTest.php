<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Mapper;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\CreateWithdrawPixRequestPayload;
use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\CreateWithdrawRequestPayload;
use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\CreateWithdrawScheduleRequestPayload;
use Tecnofit\PixWithdrawal\Adapters\Http\Mapper\CreateWithdrawRequestMapper;

final class CreateWithdrawRequestMapperTest extends TestCase
{
    public function testMapBuildsCreateWithdrawInputForImmediateRequest(): void
    {
        $mapper = new CreateWithdrawRequestMapper();

        $command = $mapper->map(
            accountId: ' acc_123 ',
            correlationId: ' corr_123 ',
            payload: new CreateWithdrawRequestPayload(
                amount: '150.25',
                method: 'PIX',
                pix: new CreateWithdrawPixRequestPayload(
                    keyType: 'EMAIL',
                    key: 'user@example.com',
                ),
            ),
            traceMetadata: [
                'http_method' => 'POST',
                'route' => '/account/acc_123/balance/withdraw',
            ],
        );

        self::assertSame(
            [
                'account_id' => 'acc_123',
                'correlation_id' => 'corr_123',
                'method' => 'PIX',
                'pix_key_type' => 'EMAIL',
                'pix_key' => 'user@example.com',
                'amount' => '150.25',
                'schedule_at' => null,
                'trace_metadata' => [
                    'http_method' => 'POST',
                    'route' => '/account/acc_123/balance/withdraw',
                ],
            ],
            $command->toArray()
        );
    }

    public function testMapBuildsCreateWithdrawInputForScheduledRequest(): void
    {
        $mapper = new CreateWithdrawRequestMapper();

        $command = $mapper->map(
            accountId: 'acc_123',
            correlationId: 'corr_123',
            payload: new CreateWithdrawRequestPayload(
                amount: '150.25',
                method: 'PIX',
                pix: new CreateWithdrawPixRequestPayload(
                    keyType: 'EMAIL',
                    key: 'user@example.com',
                ),
                schedule: new CreateWithdrawScheduleRequestPayload('2026-04-01T12:00:00+00:00'),
            ),
        );

        self::assertSame('2026-04-01T12:00:00+00:00', $command->scheduleAt());
        self::assertSame('PIX', $command->method());
        self::assertSame('EMAIL', $command->pixKeyType());
        self::assertSame('user@example.com', $command->pixKey());
    }
}
