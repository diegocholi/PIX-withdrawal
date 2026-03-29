<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Tecnofit\PixWithdrawal\Adapters\Http\Mapper\FindWithdrawStatusRequestMapper;
use Tecnofit\PixWithdrawal\Adapters\Http\Mapper\FindWithdrawStatusResponseMapper;
use Tecnofit\PixWithdrawal\Adapters\Http\Middleware\CorrelationIdMiddleware;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\RouteParameterRequest;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\SuccessResponseFactory;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\FindWithdrawStatus;

final readonly class WithdrawStatusController
{
    public function __construct(
        private RequestInterface $request,
        private SuccessResponseFactory $successResponseFactory,
        private FindWithdrawStatus $findWithdrawStatus,
        private RouteParameterRequest $routeParameterRequest,
        private FindWithdrawStatusRequestMapper $requestMapper,
        private FindWithdrawStatusResponseMapper $responseMapper,
    ) {
    }

    public function show(string $accountId, string $withdrawId): PsrResponseInterface
    {
        $normalizedAccountId = $this->routeParameterRequest->requireUuid('accountId', $accountId);
        $normalizedWithdrawId = $this->routeParameterRequest->requireUuid('withdrawId', $withdrawId);

        $correlationId = $this->resolveCorrelationId();
        $query = $this->requestMapper->map(
            accountId: $normalizedAccountId,
            withdrawId: $normalizedWithdrawId,
            correlationId: $correlationId,
            traceMetadata: [
                'http_method' => $this->request->getMethod(),
                'route' => $this->resolveRoutePath(),
                'account_id' => $normalizedAccountId,
            ],
        );

        $payload = $this->responseMapper->map(
            accountId: $normalizedAccountId,
            output: $this->findWithdrawStatus->execute($query),
        )->toArray();

        return $this->successResponseFactory->create($payload);
    }

    private function resolveCorrelationId(): string
    {
        $attribute = $this->request->getAttribute(CorrelationIdMiddleware::ATTRIBUTE);

        if (is_string($attribute) && trim($attribute) !== '') {
            return trim($attribute);
        }

        $header = $this->request->header(ApiHeader::CORRELATION_ID);

        return is_string($header) ? trim($header) : '';
    }

    private function resolveRoutePath(): string
    {
        $path = trim($this->request->getPathInfo());

        if ($path !== '') {
            return $path;
        }

        $uri = $this->request->getUri();
        $path = trim($uri->getPath());

        return $path === '' ? '/' : $path;
    }
}
