<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Mapper;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Mapper\FindWithdrawStatusResponseMapper;
use Tecnofit\PixWithdrawal\Core\Application\Dto\FindWithdrawStatusData;

final class FindWithdrawStatusResponseMapperTest extends TestCase
{
    /**
     * @dataProvider supportedStatusMappings
     */
    public function testMapTranslatesCoreStatusesToStablePublicContract(
        string $coreStatus,
        string $expectedPublicStatus,
        ?string $expectedFailureCategory,
    ): void {
        $mapper = new FindWithdrawStatusResponseMapper();

        $payload = $mapper->map(
            accountId: 'acc_123',
            output: new FindWithdrawStatusData(
                withdrawId: 'wd_status',
                status: $coreStatus,
                amount: '10.00',
                method: 'PIX',
                scheduled: false,
                scheduledFor: null,
                processedAt: null,
                errorReason: null,
                pixKeyType: 'EMAIL',
                pixKeyMasked: 'u***@example.com',
                correlationId: 'corr_status',
            ),
        );

        self::assertSame($expectedPublicStatus, $payload->toArray()['data']['status']);
        self::assertSame($expectedFailureCategory, $payload->toArray()['data']['failure_category']);
    }

    public function testMapBuildsPayloadForSuccessfulCompletedWithdraw(): void
    {
        $mapper = new FindWithdrawStatusResponseMapper();

        $payload = $mapper->map(
            accountId: ' acc_123 ',
            output: new FindWithdrawStatusData(
                withdrawId: 'wd_123',
                status: 'DONE',
                amount: '150.25',
                method: 'PIX',
                scheduled: false,
                scheduledFor: null,
                processedAt: '2026-04-01T12:05:00+00:00',
                errorReason: null,
                pixKeyType: 'EMAIL',
                pixKeyMasked: 'u***@example.com',
                correlationId: 'corr_123',
            ),
        );

        self::assertSame(
            [
                'data' => [
                    'withdraw_id' => 'wd_123',
                    'account_id' => 'acc_123',
                    'status' => 'completed',
                    'amount' => '150.25',
                    'method' => 'PIX',
                    'pix' => [
                        'key_type' => 'EMAIL',
                        'key_masked' => 'u***@example.com',
                    ],
                    'scheduled' => false,
                    'scheduled_for' => null,
                    'processed_at' => '2026-04-01T12:05:00+00:00',
                    'error_reason' => null,
                    'failure_category' => null,
                ],
                'meta' => [
                    'correlation_id' => 'corr_123',
                ],
            ],
            $payload->toArray()
        );
    }

    public function testMapBuildsPayloadForFailedWithdraw(): void
    {
        $mapper = new FindWithdrawStatusResponseMapper();

        $payload = $mapper->map(
            accountId: 'acc_123',
            output: new FindWithdrawStatusData(
                withdrawId: 'wd_456',
                status: 'FAILED_INSUFFICIENT_FUNDS',
                amount: '150.25',
                method: 'PIX',
                scheduled: false,
                scheduledFor: null,
                processedAt: '2026-04-01T12:05:00+00:00',
                errorReason: 'Insufficient funds.',
                pixKeyType: 'EMAIL',
                pixKeyMasked: 'u***@example.com',
                correlationId: 'corr_456',
            ),
        );

        self::assertSame('failed', $payload->toArray()['data']['status']);
        self::assertSame('insufficient_funds', $payload->toArray()['data']['failure_category']);
        self::assertSame('Insufficient funds.', $payload->toArray()['data']['error_reason']);
        self::assertSame('u***@example.com', $payload->toArray()['data']['pix']['key_masked']);
    }

    public function testMapRejectsUnsupportedStatus(): void
    {
        $mapper = new FindWithdrawStatusResponseMapper();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported withdraw status "UNKNOWN".');

        $mapper->map(
            accountId: 'acc_123',
            output: new FindWithdrawStatusData(
                withdrawId: 'wd_789',
                status: 'UNKNOWN',
                amount: '150.25',
                method: 'PIX',
                scheduled: false,
                scheduledFor: null,
                processedAt: null,
                errorReason: null,
                pixKeyType: null,
                pixKeyMasked: null,
                correlationId: 'corr_789',
            ),
        );
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: null|string}>
     */
    public static function supportedStatusMappings(): iterable
    {
        yield 'pending' => ['PENDING', 'pending', null];
        yield 'scheduled' => ['SCHEDULED', 'scheduled', null];
        yield 'queued' => ['QUEUED', 'queued', null];
        yield 'processing' => ['PROCESSING', 'processing', null];
        yield 'completed' => ['DONE', 'completed', null];
        yield 'failed insufficient funds' => ['FAILED_INSUFFICIENT_FUNDS', 'failed', 'insufficient_funds'];
        yield 'failed validation' => ['FAILED_VALIDATION', 'failed', 'validation'];
        yield 'failed internal' => ['FAILED_INTERNAL', 'failed', 'internal'];
    }
}
