<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

final readonly class RequestLoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Observability $observability,
        private HttpLogContextResolver $httpLogContextResolver,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper(trim($request->getMethod()));
        $path = $request->getUri()->getPath();
        $resourceIdentifiers = $this->httpLogContextResolver->resourceIdentifiers($path);

        $this->observability->info(
            'http.request.received',
            new LogContext(
                correlationId: $this->httpLogContextResolver->correlationId($request),
                withdrawId: $resourceIdentifiers['withdraw_id'] ?? null,
                accountId: $resourceIdentifiers['account_id'] ?? null,
                traceMetadata: [
                    'transport' => 'http',
                    'direction' => 'inbound',
                ],
                context: $this->buildContext($request, $method, $path)
            )
        );

        return $handler->handle($request);
    }

    /**
     * @return array<string, scalar|bool|null>
     */
    private function buildContext(ServerRequestInterface $request, string $method, string $path): array
    {
        $serverParams = $request->getServerParams();

        return $this->removeNullEntries([
            'method' => $method,
            'path' => $path,
            'expected_status_code' => $this->httpLogContextResolver->expectedStatusCode($method, $path),
            'content_type' => $this->httpLogContextResolver->normalizeHeader($request->getHeaderLine(ApiHeader::CONTENT_TYPE)),
            'accept' => $this->httpLogContextResolver->normalizeHeader($request->getHeaderLine(ApiHeader::ACCEPT)),
            'user_agent' => $this->httpLogContextResolver->normalizeHeader($request->getHeaderLine('User-Agent')),
            'remote_addr' => $this->httpLogContextResolver->normalizeServerValue($serverParams['remote_addr'] ?? null),
            'has_body' => $this->hasBody($request),
        ]);
    }

    private function hasBody(ServerRequestInterface $request): bool
    {
        $parsedBody = $request->getParsedBody();

        if (is_array($parsedBody)) {
            return $parsedBody !== [];
        }

        if (is_object($parsedBody)) {
            return true;
        }

        $contentLength = trim($request->getHeaderLine('Content-Length'));

        return ctype_digit($contentLength) && (int) $contentLength > 0;
    }

    /**
     * @param array<string, scalar|bool|null> $values
     * @return array<string, scalar|bool>
     */
    private function removeNullEntries(array $values): array
    {
        return array_filter(
            $values,
            static fn (mixed $value): bool => $value !== null
        );
    }
}
