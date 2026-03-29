<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Controller;

use Hyperf\Di\Container;
use Hyperf\Testing\Http\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Swoole\Coroutine;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotFound;
use Tecnofit\PixWithdrawal\Core\Application\Dto\FindWithdrawStatusData;
use Tecnofit\PixWithdrawal\Core\Application\Query\FindWithdrawStatusQuery;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\FindWithdrawStatus;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

final class WithdrawStatusControllerFunctionalTest extends TestCase
{
    public function testShowReturnsPublicStatusPayloadForExistingWithdraw(): void
    {
        /** @var Container $container */
        $container = (new HyperfContainerFactory())->create();
        $useCase = new RecordingFindWithdrawStatusUseCase(
            new FindWithdrawStatusData(
                withdrawId: '1a20bc55-6f7f-4cd6-9b11-7fd7ed6c2cd0',
                status: 'PROCESSING',
                amount: '150.25',
                method: 'PIX',
                scheduled: false,
                scheduledFor: null,
                processedAt: null,
                errorReason: null,
                pixKeyType: 'EMAIL',
                pixKeyMasked: 'u***@example.com',
                correlationId: 'corr-read-functional-123',
            ),
        );
        $container->set(FindWithdrawStatus::class, $useCase);

        $client = new Client($container);
        $response = null;
        $accountId = '7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e';
        $withdrawId = '1a20bc55-6f7f-4cd6-9b11-7fd7ed6c2cd0';

        Coroutine\run(static function () use ($client, $accountId, $withdrawId, &$response): void {
            $response = $client->get(
                "/account/{$accountId}/balance/withdraw/{$withdrawId}",
                [],
                [
                    ApiHeader::ACCEPT => 'application/json',
                    ApiHeader::CORRELATION_ID => 'corr-read-functional-123',
                ]
            );
        });

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('corr-read-functional-123', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'data' => [
                    'withdraw_id' => $withdrawId,
                    'account_id' => $accountId,
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
                    'correlation_id' => 'corr-read-functional-123',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
        self::assertStringContainsString('u***@example.com', (string) $response->getBody());
        self::assertStringNotContainsString('user@example.com', (string) $response->getBody());
        self::assertSame(
            [
                'account_id' => $accountId,
                'withdraw_id' => $withdrawId,
                'correlation_id' => 'corr-read-functional-123',
                'trace_metadata' => [
                    'http_method' => 'GET',
                    'route' => "/account/{$accountId}/balance/withdraw/{$withdrawId}",
                    'account_id' => $accountId,
                ],
            ],
            $useCase->receivedQuery()?->toArray()
        );
    }

    public function testShowReturnsHttp404WhenWithdrawDoesNotExist(): void
    {
        /** @var Container $container */
        $container = (new HyperfContainerFactory())->create();
        $useCase = new ThrowingFindWithdrawStatusUseCase(
            WithdrawNotFound::withId('1a20bc55-6f7f-4cd6-9b11-7fd7ed6c2cd0'),
        );
        $container->set(FindWithdrawStatus::class, $useCase);

        $client = new Client($container);
        $response = null;
        $accountId = '7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e';
        $withdrawId = '1a20bc55-6f7f-4cd6-9b11-7fd7ed6c2cd0';

        Coroutine\run(static function () use ($client, $accountId, $withdrawId, &$response): void {
            $response = $client->get(
                "/account/{$accountId}/balance/withdraw/{$withdrawId}",
                [],
                [
                    ApiHeader::ACCEPT => 'application/json',
                    ApiHeader::CORRELATION_ID => 'corr-read-functional-404',
                ]
            );
        });

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(404, $response->getStatusCode());
        self::assertSame('corr-read-functional-404', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'error' => [
                    'code' => 'withdraw.not_found',
                    'message' => 'Withdraw was not found for processing.',
                    'details' => [
                        'withdraw_id' => $withdrawId,
                    ],
                ],
                'meta' => [
                    'correlation_id' => 'corr-read-functional-404',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
        self::assertSame(
            [
                'account_id' => $accountId,
                'withdraw_id' => $withdrawId,
                'correlation_id' => 'corr-read-functional-404',
                'trace_metadata' => [
                    'http_method' => 'GET',
                    'route' => "/account/{$accountId}/balance/withdraw/{$withdrawId}",
                    'account_id' => $accountId,
                ],
            ],
            $useCase->receivedQuery()?->toArray()
        );
    }

    public function testShowReturnsHttp500WithStandardizedErrorPayload(): void
    {
        /** @var Container $container */
        $container = (new HyperfContainerFactory())->create();
        $container->set(
            FindWithdrawStatus::class,
            new ThrowingFindWithdrawStatusUseCase(new \RuntimeException('boom'))
        );

        $client = new Client($container);
        $response = null;
        $accountId = '7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e';
        $withdrawId = '1a20bc55-6f7f-4cd6-9b11-7fd7ed6c2cd0';

        Coroutine\run(static function () use ($client, $accountId, $withdrawId, &$response): void {
            $response = $client->get(
                "/account/{$accountId}/balance/withdraw/{$withdrawId}",
                [],
                [
                    ApiHeader::ACCEPT => 'application/json',
                    ApiHeader::CORRELATION_ID => 'corr-read-functional-500',
                ]
            );
        });

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(500, $response->getStatusCode());
        self::assertSame('corr-read-functional-500', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'error' => [
                    'code' => 'http.internal_server_error',
                    'message' => 'Internal server error.',
                    'details' => [],
                ],
                'meta' => [
                    'correlation_id' => 'corr-read-functional-500',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
    }
}

final class RecordingFindWithdrawStatusUseCase implements FindWithdrawStatus
{
    private ?FindWithdrawStatusQuery $receivedQuery = null;

    public function __construct(
        private readonly FindWithdrawStatusData $output,
    ) {
    }

    public function execute(FindWithdrawStatusQuery $query): FindWithdrawStatusData
    {
        $this->receivedQuery = $query;

        return $this->output;
    }

    public function receivedQuery(): ?FindWithdrawStatusQuery
    {
        return $this->receivedQuery;
    }
}

final class ThrowingFindWithdrawStatusUseCase implements FindWithdrawStatus
{
    private ?FindWithdrawStatusQuery $receivedQuery = null;

    public function __construct(
        private readonly \Throwable $throwable,
    ) {
    }

    public function execute(FindWithdrawStatusQuery $query): FindWithdrawStatusData
    {
        $this->receivedQuery = $query;

        throw $this->throwable;
    }

    public function receivedQuery(): ?FindWithdrawStatusQuery
    {
        return $this->receivedQuery;
    }
}
