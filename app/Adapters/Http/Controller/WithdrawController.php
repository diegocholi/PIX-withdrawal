<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\CreateWithdrawRequestPayload;
use Tecnofit\PixWithdrawal\Adapters\Http\Mapper\CreateWithdrawRequestMapper;
use Tecnofit\PixWithdrawal\Adapters\Http\Mapper\CreateWithdrawResponseMapper;
use Tecnofit\PixWithdrawal\Adapters\Http\Middleware\CorrelationIdMiddleware;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\CreateWithdrawRequest;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\RouteParameterRequest;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\WithdrawAcceptedResponse;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\CreateWithdraw;

final readonly class WithdrawController
{
    public function __construct(
        private RequestInterface $request,
        private CreateWithdraw $createWithdraw,
        private CreateWithdrawRequest $createWithdrawRequest,
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
        $correlationId = $this->resolveCorrelationId();
        $scheduledFor = $this->extractScheduledFor($requestPayload);

        $command = $this->requestMapper->map(
            accountId: $normalizedAccountId,
            correlationId: $correlationId,
            payload: $requestPayload,
            traceMetadata: [
                'http_method' => $this->request->getMethod(),
                'route' => $this->resolveRoutePath(),
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

    private function resolveCorrelationId(): string
    {
        $attribute = $this->request->getAttribute(CorrelationIdMiddleware::ATTRIBUTE);

        if (is_string($attribute) && trim($attribute) !== '') {
            return trim($attribute);
        }

        $header = $this->request->header(ApiHeader::CORRELATION_ID);

        return is_string($header) ? trim($header) : '';
    }

    private function extractScheduledFor(CreateWithdrawRequestPayload $payload): ?string
    {
        $schedulePayload = $payload->toArray()['schedule'];

        if (! is_array($schedulePayload)) {
            return null;
        }

        return (string) ($schedulePayload['at'] ?? '');
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
