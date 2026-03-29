<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Dto\Public;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\CreateWithdrawAcceptedResponsePayload;
use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\CreateWithdrawPixRequestPayload;
use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\CreateWithdrawRequestPayload;
use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\CreateWithdrawScheduleRequestPayload;

final class CreateWithdrawContractPayloadTest extends TestCase
{
    public function testImmediateRequestPayloadMatchesPublicContract(): void
    {
        $payload = new CreateWithdrawRequestPayload(
            amount: '150.25',
            method: 'PIX',
            pix: new CreateWithdrawPixRequestPayload(
                keyType: 'EMAIL',
                key: 'user@example.com',
            ),
        );

        self::assertSame(
            [
                'amount' => '150.25',
                'method' => 'PIX',
                'pix' => [
                    'key_type' => 'EMAIL',
                    'key' => 'user@example.com',
                ],
                'schedule' => null,
            ],
            $payload->toArray()
        );
    }

    public function testScheduledRequestPayloadMatchesPublicContract(): void
    {
        $payload = new CreateWithdrawRequestPayload(
            amount: '150.25',
            method: 'PIX',
            pix: new CreateWithdrawPixRequestPayload(
                keyType: 'EMAIL',
                key: 'user@example.com',
            ),
            schedule: new CreateWithdrawScheduleRequestPayload('2026-04-01T12:00:00+00:00'),
        );

        self::assertSame(
            [
                'amount' => '150.25',
                'method' => 'PIX',
                'pix' => [
                    'key_type' => 'EMAIL',
                    'key' => 'user@example.com',
                ],
                'schedule' => [
                    'at' => '2026-04-01T12:00:00+00:00',
                ],
            ],
            $payload->toArray()
        );
    }

    public function testAcceptedResponsePayloadMatchesPublicContract(): void
    {
        $payload = new CreateWithdrawAcceptedResponsePayload(
            withdrawId: 'wd_123',
            accountId: 'acc_123',
            status: 'queued',
            scheduled: false,
            scheduledFor: null,
            statusUrl: '/account/acc_123/balance/withdraw/wd_123',
            correlationId: 'corr_123',
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

    public function testAcceptedResponsePayloadKeepsTheSameFieldSetForImmediateAndScheduledWithdraws(): void
    {
        $immediatePayload = new CreateWithdrawAcceptedResponsePayload(
            withdrawId: 'wd_123',
            accountId: 'acc_123',
            status: 'queued',
            scheduled: false,
            scheduledFor: null,
            statusUrl: '/account/acc_123/balance/withdraw/wd_123',
            correlationId: 'corr_123',
        );

        $scheduledPayload = new CreateWithdrawAcceptedResponsePayload(
            withdrawId: 'wd_456',
            accountId: 'acc_123',
            status: 'scheduled',
            scheduled: true,
            scheduledFor: '2026-04-01T12:00:00+00:00',
            statusUrl: '/account/acc_123/balance/withdraw/wd_456',
            correlationId: 'corr_456',
        );

        self::assertSame(
            array_keys($immediatePayload->toArray()['data']),
            array_keys($scheduledPayload->toArray()['data'])
        );
        self::assertSame(
            array_keys($immediatePayload->toArray()['meta']),
            array_keys($scheduledPayload->toArray()['meta'])
        );
    }

    public function testApiDocumentDescribesCreateWithdrawContract(): void
    {
        $projectBasePath = dirname(__DIR__, 5);
        $documentPath = $projectBasePath . '/docs/api/create-withdraw-contract.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('POST /account/{accountId}/balance/withdraw', $documentContents);
        self::assertStringContainsString('"pix"', $documentContents);
        self::assertStringContainsString('"schedule"', $documentContents);
        self::assertStringContainsString('"status": "queued"', $documentContents);
        self::assertStringContainsString('"status": "scheduled"', $documentContents);
        self::assertStringContainsString('"code": "withdraw.insufficient_funds"', $documentContents);
    }
}
