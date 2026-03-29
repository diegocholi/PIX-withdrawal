<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Controller;

use Hyperf\HttpMessage\Server\Response as HyperfPsrResponse;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Controller\WithdrawStatusController;
use Tecnofit\PixWithdrawal\Adapters\Http\Mapper\FindWithdrawStatusRequestMapper;
use Tecnofit\PixWithdrawal\Adapters\Http\Mapper\FindWithdrawStatusResponseMapper;
use Tecnofit\PixWithdrawal\Adapters\Http\Middleware\CorrelationIdMiddleware;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\RouteParameterRequest;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\SuccessResponseFactory;
use Tecnofit\PixWithdrawal\Core\Application\Dto\FindWithdrawStatusData;
use Tecnofit\PixWithdrawal\Core\Application\Query\FindWithdrawStatusQuery;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\FindWithdrawStatus;

final class WithdrawStatusControllerTest extends TestCase
{
    public function testShowReturnsPublicWithdrawStatusPayload(): void
    {
        $request = $this->createConfiguredMock(RequestInterface::class, [
            'getAttribute' => 'corr-read-123',
            'header' => 'corr-read-123',
            'getMethod' => 'GET',
            'getPathInfo' => '/account/acc_123/balance/withdraw/wd_123',
        ]);

        $useCase = new InMemoryFindWithdrawStatus(
            new FindWithdrawStatusData(
                withdrawId: 'wd_123',
                status: 'PROCESSING',
                amount: '150.25',
                method: 'PIX',
                scheduled: false,
                scheduledFor: null,
                processedAt: null,
                errorReason: null,
                pixKeyType: 'EMAIL',
                pixKeyMasked: 'u***@example.com',
                correlationId: 'corr-read-123',
            ),
        );

        $controller = new WithdrawStatusController(
            request: $request,
            successResponseFactory: new SuccessResponseFactory($this->jsonResponseStub()),
            findWithdrawStatus: $useCase,
            routeParameterRequest: new RouteParameterRequest(),
            requestMapper: new FindWithdrawStatusRequestMapper(),
            responseMapper: new FindWithdrawStatusResponseMapper(),
        );

        $response = $controller->show(
            ' 7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e ',
            ' 1a20bc55-6f7f-4cd6-9b11-7fd7ed6c2cd0 '
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('corr-read-123', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'data' => [
                    'withdraw_id' => 'wd_123',
                    'account_id' => '7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e',
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
                    'correlation_id' => 'corr-read-123',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
        self::assertSame(
            [
                'account_id' => '7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e',
                'withdraw_id' => '1a20bc55-6f7f-4cd6-9b11-7fd7ed6c2cd0',
                'correlation_id' => 'corr-read-123',
                'trace_metadata' => [
                    'http_method' => 'GET',
                    'route' => '/account/acc_123/balance/withdraw/wd_123',
                    'account_id' => '7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e',
                ],
            ],
            $useCase->receivedQuery()?->toArray()
        );
    }

    public function testShowGeneratesCorrelationIdWhenHeaderIsMissing(): void
    {
        $request = $this->createConfiguredMock(RequestInterface::class, [
            'getAttribute' => 'generated-read-corr',
            'header' => null,
            'getMethod' => 'GET',
            'getPathInfo' => '/account/acc_999/balance/withdraw/wd_999',
        ]);

        $useCase = new InMemoryFindWithdrawStatus(
            new FindWithdrawStatusData(
                withdrawId: 'wd_999',
                status: 'DONE',
                amount: '200.00',
                method: 'PIX',
                scheduled: true,
                scheduledFor: '2026-04-01T12:00:00+00:00',
                processedAt: '2026-04-01T12:05:00+00:00',
                errorReason: null,
                pixKeyType: 'EMAIL',
                pixKeyMasked: 'u***@example.com',
                correlationId: 'generated-read-corr',
            ),
        );

        $controller = new WithdrawStatusController(
            request: $request,
            successResponseFactory: new SuccessResponseFactory($this->jsonResponseStub()),
            findWithdrawStatus: $useCase,
            routeParameterRequest: new RouteParameterRequest(),
            requestMapper: new FindWithdrawStatusRequestMapper(),
            responseMapper: new FindWithdrawStatusResponseMapper(),
        );

        $response = $controller->show(
            '3bf8a6e8-f7d2-4fd9-b8fc-10b31d4d7d37',
            '1a20bc55-6f7f-4cd6-9b11-7fd7ed6c2cd0'
        );

        self::assertSame('generated-read-corr', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
        self::assertSame('completed', json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR)['data']['status']);
    }

    public function testShowRejectsEmptyWithdrawIdPathParameter(): void
    {
        $request = $this->createConfiguredMock(RequestInterface::class, [
            'getAttribute' => 'corr-read-123',
            'header' => 'corr-read-123',
            'getMethod' => 'GET',
            'getPathInfo' => '/account/acc_123/balance/withdraw/',
        ]);

        $controller = new WithdrawStatusController(
            request: $request,
            successResponseFactory: new SuccessResponseFactory($this->jsonResponseStub()),
            findWithdrawStatus: new InMemoryFindWithdrawStatus(
                new FindWithdrawStatusData(
                    withdrawId: 'wd_123',
                    status: 'PROCESSING',
                    amount: '150.25',
                    method: 'PIX',
                    scheduled: false,
                    scheduledFor: null,
                    processedAt: null,
                    errorReason: null,
                    pixKeyType: 'EMAIL',
                    pixKeyMasked: 'u***@example.com',
                    correlationId: 'corr-read-123',
                ),
            ),
            routeParameterRequest: new RouteParameterRequest(),
            requestMapper: new FindWithdrawStatusRequestMapper(),
            responseMapper: new FindWithdrawStatusResponseMapper(),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The withdrawId path parameter is required.');

        $controller->show('7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e', '   ');
    }

    private function jsonResponseStub(): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('json')
            ->willReturnCallback(static function (array $payload): HyperfPsrResponse {
                return (new HyperfPsrResponse())
                    ->withHeader('content-type', 'application/json; charset=utf-8')
                    ->withBody(new SwooleStream(json_encode($payload, JSON_THROW_ON_ERROR)));
            });

        return $response;
    }
}

final class InMemoryFindWithdrawStatus implements FindWithdrawStatus
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
