<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Mapper;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Mapper\CreateWithdrawResponseMapper;
use Tecnofit\PixWithdrawal\Core\Application\Dto\CreateWithdrawData;

final class CreateWithdrawResponseMapperTest extends TestCase
{
    public function testMapBuildsAcceptedPayloadForImmediateWithdraw(): void
    {
        $mapper = new CreateWithdrawResponseMapper();

        $payload = $mapper->map(
            accountId: ' acc_123 ',
            output: new CreateWithdrawData(
                withdrawId: 'wd_123',
                correlationId: 'corr_123',
                status: 'QUEUED',
            ),
        );

        self::assertSame(
            [
                'data' => [
                    'withdraw_id' => 'wd_123',
                    'account_id' => 'acc_123',
                    'status' => 'queued',
                    'scheduled' => false,
                    'scheduled_for' => null,
                    'status_url' => '/account/acc_123/balance/withdraw/wd_123',
                ],
                'meta' => [
                    'correlation_id' => 'corr_123',
                ],
            ],
            $payload->toArray()
        );
    }

    public function testMapBuildsAcceptedPayloadForScheduledWithdraw(): void
    {
        $mapper = new CreateWithdrawResponseMapper();

        $payload = $mapper->map(
            accountId: 'acc_123',
            output: new CreateWithdrawData(
                withdrawId: 'wd_456',
                correlationId: 'corr_456',
                status: 'SCHEDULED',
            ),
            scheduledFor: '2026-04-01T12:00:00+00:00',
        );

        self::assertSame('scheduled', $payload->toArray()['data']['status']);
        self::assertTrue($payload->toArray()['data']['scheduled']);
        self::assertSame('2026-04-01T12:00:00+00:00', $payload->toArray()['data']['scheduled_for']);
    }

    public function testMapRejectsUnsupportedCreateWithdrawStatus(): void
    {
        $mapper = new CreateWithdrawResponseMapper();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported create-withdraw status "DONE".');

        $mapper->map(
            accountId: 'acc_123',
            output: new CreateWithdrawData(
                withdrawId: 'wd_789',
                correlationId: 'corr_789',
                status: 'DONE',
            ),
        );
    }
}
