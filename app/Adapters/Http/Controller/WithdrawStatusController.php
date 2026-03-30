<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Tecnofit\PixWithdrawal\Adapters\Http\Mapper\FindWithdrawStatusRequestMapper;
use Tecnofit\PixWithdrawal\Adapters\Http\Mapper\FindWithdrawStatusResponseMapper;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\HttpRequestContextResolver;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\RouteParameterRequest;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\SuccessResponseFactory;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\FindWithdrawStatus;

final readonly class WithdrawStatusController
{
    public function __construct(
        private RequestInterface $request,
        private SuccessResponseFactory $successResponseFactory,
        private FindWithdrawStatus $findWithdrawStatus,
        private HttpRequestContextResolver $requestContextResolver,
        private RouteParameterRequest $routeParameterRequest,
        private FindWithdrawStatusRequestMapper $requestMapper,
        private FindWithdrawStatusResponseMapper $responseMapper,
    ) {
    }

    public function show(string $accountId, string $withdrawId): PsrResponseInterface
    {
        $normalizedAccountId = $this->routeParameterRequest->requireUuid('accountId', $accountId);
        $normalizedWithdrawId = $this->routeParameterRequest->requireUuid('withdrawId', $withdrawId);

        $correlationId = $this->requestContextResolver->correlationId() ?? '';
        $query = $this->requestMapper->map(
            accountId: $normalizedAccountId,
            withdrawId: $normalizedWithdrawId,
            correlationId: $correlationId,
            traceMetadata: [
                'http_method' => $this->request->getMethod(),
                'route' => $this->requestContextResolver->routePath(),
                'account_id' => $normalizedAccountId,
            ],
        );

        $payload = $this->responseMapper->map(
            accountId: $normalizedAccountId,
            output: $this->findWithdrawStatus->execute($query),
        )->toArray();

        return $this->successResponseFactory->create($payload);
    }
}
