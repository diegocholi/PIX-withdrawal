<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\CreateWithdrawRequestPayload;
use Tecnofit\PixWithdrawal\Adapters\Http\Mapper\CreateWithdrawRequestMapper;
use Tecnofit\PixWithdrawal\Adapters\Http\Mapper\CreateWithdrawResponseMapper;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\CreateWithdrawRequest;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\HttpRequestContextResolver;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\RouteParameterRequest;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\WithdrawAcceptedResponse;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\CreateWithdraw;

final readonly class WithdrawController
{
    public function __construct(
        private RequestInterface $request,
        private CreateWithdraw $createWithdraw,
        private CreateWithdrawRequest $createWithdrawRequest,
        private HttpRequestContextResolver $requestContextResolver,
        private RouteParameterRequest $routeParameterRequest,
        private CreateWithdrawRequestMapper $requestMapper,
        private CreateWithdrawResponseMapper $responseMapper,
        private WithdrawAcceptedResponse $withdrawAcceptedResponse,
    ) {
    }

    public function create(string $accountId): PsrResponseInterface
    {
        $normalizedAccountId = $this->routeParameterRequest->requireUuid('accountId', $accountId);

        $requestPayload = $this->createWithdrawRequest->validate($this->request->post());
        $correlationId = $this->requestContextResolver->correlationId() ?? '';
        $scheduledFor = $this->extractScheduledFor($requestPayload);

        $command = $this->requestMapper->map(
            accountId: $normalizedAccountId,
            correlationId: $correlationId,
            payload: $requestPayload,
            traceMetadata: [
                'http_method' => $this->request->getMethod(),
                'route' => $this->requestContextResolver->routePath(),
            ],
        );

        $output = $this->createWithdraw->execute($command);
        $payload = $this->responseMapper->map(
            accountId: $normalizedAccountId,
            output: $output,
            scheduledFor: $scheduledFor,
        )->toArray();

        return $this->withdrawAcceptedResponse->create($payload);
    }

    private function extractScheduledFor(CreateWithdrawRequestPayload $payload): ?string
    {
        $schedulePayload = $payload->toArray()['schedule'];

        if (! is_array($schedulePayload)) {
            return null;
        }

        return (string) ($schedulePayload['at'] ?? '');
    }
}
