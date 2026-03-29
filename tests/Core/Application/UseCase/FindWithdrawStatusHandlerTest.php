<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Application\UseCase;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotFound;
use Tecnofit\PixWithdrawal\Core\Application\Query\FindWithdrawStatusInput;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\FindWithdrawStatusHandler;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\WithdrawStatusView;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\WithdrawStatusViewQuery;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderSensitiveDataMasker;

final class FindWithdrawStatusHandlerTest extends TestCase
{
    public function testHandlerReturnsStructuredWithdrawStatusPayload(): void
    {
        $output = (new FindWithdrawStatusHandler(
            new FindStatusWithdrawStatusViewQuery(
                new WithdrawStatusView(
                    withdrawId: 'wd-1',
                    status: 'FAILED_INSUFFICIENT_FUNDS',
                    amount: '25.00',
                    method: 'PIX',
                    scheduled: true,
                    scheduledFor: '2026-03-29T10:00:00-03:00',
                    processedAt: '2026-03-29T10:00:07-03:00',
                    errorReason: 'insufficient_balance',
                    pixKeyType: 'EMAIL',
                    pixKeyValue: 'user@example.com',
                ),
            ),
            new ProviderSensitiveDataMasker(),
        ))->execute(
            new FindWithdrawStatusInput('acc-1', 'wd-1', 'corr-read')
        );

        self::assertSame(
            [
                'withdraw_id' => 'wd-1',
                'status' => 'FAILED_INSUFFICIENT_FUNDS',
                'amount' => '25.00',
                'method' => 'PIX',
                'scheduled' => true,
                'scheduled_for' => '2026-03-29T10:00:00-03:00',
                'processed_at' => '2026-03-29T10:00:07-03:00',
                'error_reason' => 'insufficient_balance',
                'pix_key_type' => 'EMAIL',
                'pix_key_masked' => 'u***@example.com',
                'correlation_id' => 'corr-read',
                'trace_metadata' => [],
            ],
            $output->toArray()
        );
    }

    public function testHandlerRejectsUnknownWithdraw(): void
    {
        $this->expectException(WithdrawNotFound::class);

        (new FindWithdrawStatusHandler(
            new FindStatusWithdrawStatusViewQuery(null),
            new ProviderSensitiveDataMasker(),
        ))->execute(
            new FindWithdrawStatusInput('acc-1', 'wd-404', 'corr-read')
        );
    }

    public function testHandlerRejectsWithdrawFromAnotherAccount(): void
    {
        $this->expectException(WithdrawNotFound::class);

        (new FindWithdrawStatusHandler(
            new FindStatusWithdrawStatusViewQuery(null),
            new ProviderSensitiveDataMasker(),
        ))->execute(
            new FindWithdrawStatusInput('acc-other', 'wd-1', 'corr-read')
        );
    }

    private function clockAt(string $now): Clock
    {
        $currentTime = new DateTimeImmutable($now);

        return new class ($currentTime) implements Clock {
            public function __construct(private readonly DateTimeImmutable $currentTime)
            {
            }

            public function now(): DateTimeImmutable
            {
                return $this->currentTime;
            }
        };
    }
}

final class FindStatusWithdrawStatusViewQuery implements WithdrawStatusViewQuery
{
    public function __construct(private ?WithdrawStatusView $withdrawStatusView)
    {
    }

    public function find(string $accountId, string $withdrawId): ?WithdrawStatusView
    {
        if ($this->withdrawStatusView === null) {
            return null;
        }

        if ($this->withdrawStatusView->withdrawId() !== $withdrawId) {
            return null;
        }

        return $accountId === 'acc-1' ? $this->withdrawStatusView : null;
    }
}
