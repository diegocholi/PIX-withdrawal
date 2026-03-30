<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Controller;

use Hyperf\HttpMessage\Server\Response as HyperfPsrResponse;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Controller\WithdrawController;
use Tecnofit\PixWithdrawal\Adapters\Http\Mapper\CreateWithdrawRequestMapper;
use Tecnofit\PixWithdrawal\Adapters\Http\Mapper\CreateWithdrawResponseMapper;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\CreateWithdrawRequest;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\HttpRequestContextResolver;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\RouteParameterRequest;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\SuccessResponseFactory;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\WithdrawAcceptedResponse;
use Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawCommand;
use Tecnofit\PixWithdrawal\Core\Application\Dto\CreateWithdrawData;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\CreateWithdraw;

final class WithdrawControllerTest extends TestCase
{
    public function testCreateReturnsAcceptedResponseForImmediateWithdraw(): void
    {
        $request = $this->createConfiguredMock(RequestInterface::class, [
            'post' => [
                'amount' => '150.25',
                'method' => 'PIX',
                'pix' => [
                    'key_type' => 'EMAIL',
                    'key' => 'user@example.com',
                ],
                'schedule' => null,
            ],
            'getAttribute' => 'corr-header-123',
            'header' => 'corr-header-123',
            'getMethod' => 'POST',
            'getPathInfo' => '/account/acc_123/balance/withdraw',
        ]);

        $createWithdraw = new InMemoryCreateWithdraw(
            new CreateWithdrawData(
                withdrawId: 'wd_123',
                correlationId: 'corr-header-123',
                status: 'QUEUED',
            ),
        );

        $controller = new WithdrawController(
            request: $request,
            createWithdraw: $createWithdraw,
            createWithdrawRequest: new CreateWithdrawRequest(),
            requestContextResolver: new HttpRequestContextResolver($request),
            routeParameterRequest: new RouteParameterRequest(),
            requestMapper: new CreateWithdrawRequestMapper(),
            responseMapper: new CreateWithdrawResponseMapper(),
            withdrawAcceptedResponse: new WithdrawAcceptedResponse(new SuccessResponseFactory($this->jsonResponseStub())),
        );

        $response = $controller->create(' 7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e ');

        self::assertSame(202, $response->getStatusCode());
        self::assertSame('corr-header-123', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
        self::assertSame('/account/7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e/balance/withdraw/wd_123', $response->getHeaderLine(ApiHeader::LOCATION));
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'data' => [
                    'withdraw_id' => 'wd_123',
                    'account_id' => '7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e',
                    'status' => 'queued',
                    'scheduled' => false,
                    'scheduled_for' => null,
                    'status_url' => '/account/7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e/balance/withdraw/wd_123',
                ],
                'meta' => [
                    'correlation_id' => 'corr-header-123',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
        self::assertSame(
            [
                'account_id' => '7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e',
                'correlation_id' => 'corr-header-123',
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
            $createWithdraw->receivedCommand()?->toArray()
        );
    }

    public function testCreateGeneratesCorrelationIdForScheduledWithdrawWhenHeaderIsMissing(): void
    {
        $request = $this->createConfiguredMock(RequestInterface::class, [
            'post' => [
                'amount' => '200.00',
                'method' => 'PIX',
                'pix' => [
                    'key_type' => 'EMAIL',
                    'key' => 'scheduled@example.com',
                ],
                'schedule' => [
                    'at' => '2026-04-01T12:00:00+00:00',
                ],
            ],
            'getAttribute' => 'generated-corr',
            'header' => null,
            'getMethod' => 'POST',
            'getPathInfo' => '/account/acc_999/balance/withdraw',
        ]);

        $createWithdraw = new InMemoryCreateWithdraw(
            new CreateWithdrawData(
                withdrawId: 'wd_999',
                correlationId: 'generated-corr',
                status: 'SCHEDULED',
            ),
        );

        $controller = new WithdrawController(
            request: $request,
            createWithdraw: $createWithdraw,
            createWithdrawRequest: new CreateWithdrawRequest(),
            requestContextResolver: new HttpRequestContextResolver($request),
            routeParameterRequest: new RouteParameterRequest(),
            requestMapper: new CreateWithdrawRequestMapper(),
            responseMapper: new CreateWithdrawResponseMapper(),
            withdrawAcceptedResponse: new WithdrawAcceptedResponse(new SuccessResponseFactory($this->jsonResponseStub())),
        );

        $response = $controller->create('3bf8a6e8-f7d2-4fd9-b8fc-10b31d4d7d37');

        self::assertSame(202, $response->getStatusCode());
        self::assertSame('generated-corr', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
        self::assertSame('/account/3bf8a6e8-f7d2-4fd9-b8fc-10b31d4d7d37/balance/withdraw/wd_999', $response->getHeaderLine(ApiHeader::LOCATION));
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'data' => [
                    'withdraw_id' => 'wd_999',
                    'account_id' => '3bf8a6e8-f7d2-4fd9-b8fc-10b31d4d7d37',
                    'status' => 'scheduled',
                    'scheduled' => true,
                    'scheduled_for' => '2026-04-01T12:00:00+00:00',
                    'status_url' => '/account/3bf8a6e8-f7d2-4fd9-b8fc-10b31d4d7d37/balance/withdraw/wd_999',
                ],
                'meta' => [
                    'correlation_id' => 'generated-corr',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
        self::assertSame('2026-04-01T12:00:00+00:00', $createWithdraw->receivedCommand()?->scheduleAt());
    }

    public function testCreateRejectsPayloadWithoutPixObject(): void
    {
        $request = $this->createConfiguredMock(RequestInterface::class, [
            'post' => [
                'amount' => '150.25',
                'method' => 'PIX',
            ],
            'getAttribute' => 'corr-header-123',
            'header' => 'corr-header-123',
            'getMethod' => 'POST',
            'getPathInfo' => '/account/acc_123/balance/withdraw',
        ]);

        $controller = new WithdrawController(
            request: $request,
            createWithdraw: new InMemoryCreateWithdraw(
                new CreateWithdrawData(
                    withdrawId: 'wd_123',
                    correlationId: 'corr-header-123',
                    status: 'QUEUED',
                ),
            ),
            createWithdrawRequest: new CreateWithdrawRequest(),
            requestContextResolver: new HttpRequestContextResolver($request),
            routeParameterRequest: new RouteParameterRequest(),
            requestMapper: new CreateWithdrawRequestMapper(),
            responseMapper: new CreateWithdrawResponseMapper(),
            withdrawAcceptedResponse: new WithdrawAcceptedResponse(new SuccessResponseFactory($this->jsonResponseStub())),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The withdraw request payload must contain a pix object.');

        $controller->create('7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e');
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

final class InMemoryCreateWithdraw implements CreateWithdraw
{
    private ?CreateWithdrawCommand $receivedCommand = null;

    public function __construct(
        private readonly CreateWithdrawData $output,
    ) {
    }

    public function execute(CreateWithdrawCommand $command): CreateWithdrawData
    {
        $this->receivedCommand = $command;

        return $this->output;
    }

    public function receivedCommand(): ?CreateWithdrawCommand
    {
        return $this->receivedCommand;
    }
}
