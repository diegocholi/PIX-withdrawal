<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;

final class HttpLogContextResolver
{
    public function correlationId(ServerRequestInterface $request): string
    {
        $attribute = $request->getAttribute(CorrelationIdMiddleware::ATTRIBUTE);

        if (is_string($attribute) && trim($attribute) !== '') {
            return trim($attribute);
        }

        $header = $this->normalizeHeader($request->getHeaderLine(ApiHeader::CORRELATION_ID));

        return $header ?? 'missing-http-correlation-id';
    }

    /**
     * @return array{account_id?: string, withdraw_id?: string}
     */
    public function resourceIdentifiers(string $path): array
    {
        if (preg_match('#^/account/(?P<accountId>[^/]+)/balance/withdraw/(?P<withdrawId>[^/]+)$#', $path, $matches) === 1) {
            return [
                'account_id' => trim((string) $matches['accountId']),
                'withdraw_id' => trim((string) $matches['withdrawId']),
            ];
        }

        if (preg_match('#^/account/(?P<accountId>[^/]+)/balance/withdraw$#', $path, $matches) === 1) {
            return [
                'account_id' => trim((string) $matches['accountId']),
            ];
        }

        return [];
    }

    public function expectedStatusCode(string $method, string $path): ?int
    {
        if ($method === 'POST' && preg_match('#^/account/[^/]+/balance/withdraw$#', $path) === 1) {
            return 202;
        }

        if ($method === 'GET' && preg_match('#^/account/[^/]+/balance/withdraw/[^/]+$#', $path) === 1) {
            return 200;
        }

        if ($method === 'GET' && in_array($path, ['/health', '/ready', '/bootstrap'], true)) {
            return 200;
        }

        return null;
    }

    public function normalizeHeader(string $value): ?string
    {
        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    public function normalizeServerValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
