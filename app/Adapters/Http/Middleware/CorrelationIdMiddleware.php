<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\CorrelationIdGenerator;

final readonly class CorrelationIdMiddleware implements MiddlewareInterface
{
    public const ATTRIBUTE = 'correlation_id';

    public function __construct(
        private CorrelationIdGenerator $correlationIdGenerator,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $correlationId = $this->resolveCorrelationId($request);
        $requestWithCorrelation = $request->withAttribute(self::ATTRIBUTE, $correlationId);
        $response = $handler->handle($requestWithCorrelation);

        if ($response->hasHeader(ApiHeader::CORRELATION_ID)) {
            return $response;
        }

        return $response->withHeader(ApiHeader::CORRELATION_ID, $correlationId);
    }

    private function resolveCorrelationId(ServerRequestInterface $request): string
    {
        $header = $request->getHeaderLine(ApiHeader::CORRELATION_ID);
        $correlationId = trim($header);

        if ($correlationId !== '') {
            return $correlationId;
        }

        return $this->correlationIdGenerator->generate();
    }
}
