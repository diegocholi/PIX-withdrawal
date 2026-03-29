<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Dto\Public;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\FindWithdrawStatusResponsePayload;

final class FindWithdrawStatusContractPayloadTest extends TestCase
{
    public function testResponsePayloadMatchesPublicContract(): void
    {
        $payload = new FindWithdrawStatusResponsePayload(
            withdrawId: 'wd_123',
            accountId: 'acc_123',
            status: 'processing',
            amount: '150.25',
            method: 'PIX',
            pix: new \Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\FindWithdrawStatusPixResponsePayload(
                keyType: 'EMAIL',
                keyMasked: 'u***@example.com',
            ),
            scheduled: false,
            scheduledFor: null,
            processedAt: null,
            errorReason: null,
            failureCategory: null,
            correlationId: 'corr_123',
        );

        self::assertSame(
            [
                'data' => [
                    'withdraw_id' => 'wd_123',
                    'account_id' => 'acc_123',
                    'status' => 'processing',
                    'amount' => '150.25',
                    'method' => 'PIX',
                    'pix' => [
                        'key_type' => 'EMAIL',
                        'key_masked' => 'u***@example.com',
                    ],
                    'scheduled' => false,
                    'scheduled_for' => null,
                    'processed_at' => null,
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

    public function testApiDocumentDescribesFindWithdrawStatusContract(): void
    {
        $projectBasePath = dirname(__DIR__, 5);
        $documentPath = $projectBasePath . '/docs/api/find-withdraw-status-contract.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('GET /account/{accountId}/balance/withdraw/{withdrawId}', $documentContents);
        self::assertStringContainsString('`DONE` do core -> `completed`', $documentContents);
        self::assertStringContainsString('`FAILED_INSUFFICIENT_FUNDS` do core -> `failed`', $documentContents);
        self::assertStringContainsString('"pix"', $documentContents);
        self::assertStringContainsString('"key_masked": "u***@example.com"', $documentContents);
        self::assertStringContainsString('"failure_category": "insufficient_funds"', $documentContents);
    }
}
