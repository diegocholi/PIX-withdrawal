<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Exception\Handler;

use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpServer\Contract\RequestInterface;
use InvalidArgumentException;
use Swow\Psr7\Message\ResponsePlusInterface;
use Throwable;
use Tecnofit\PixWithdrawal\Adapters\Http\Exception\PublicErrorSanitizer;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\HttpRequestContextResolver;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ErrorResponseFactory;
use Tecnofit\PixWithdrawal\Core\Application\Exception\DuplicateWithdrawRequestBlocked;
use Tecnofit\PixWithdrawal\Core\Application\Exception\InsufficientWithdrawBalance;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotProcessable;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\FailureCategory;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\FailureClassifier;
use Tecnofit\PixWithdrawal\Core\Shared\Exception\CoreException;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

final class UnexpectedThrowableHandler extends ExceptionHandler
{
    public function __construct(
        private readonly ErrorResponseFactory $errorResponseFactory,
        private readonly RequestInterface $request,
        private readonly HttpRequestContextResolver $requestContextResolver,
        private readonly FailureClassifier $failureClassifier,
        private readonly PublicErrorSanitizer $publicErrorSanitizer,
        private readonly StructuredLogger $structuredLogger,
    ) {
    }

    public function handle(Throwable $throwable, ResponsePlusInterface $response)
    {
        $this->stopPropagation();

        $this->logThrowable($throwable);

        [$status, $code, $message, $details] = $this->mapThrowable($throwable);
        $publicError = $this->publicErrorSanitizer->sanitize($status, $message, $details);
        $errorResponse = $this->errorResponseFactory->create(
            code: $code,
            message: $publicError['message'],
            status: $status,
            details: $publicError['details'],
            correlationId: $this->resolveCorrelationId(),
        );

        $handledResponse = $response->setStatus($errorResponse->getStatusCode());

        foreach ($errorResponse->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $handledResponse = $handledResponse->withHeader($name, $value);
            }
        }

        return $handledResponse->setBody($errorResponse->getBody());
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }

    private function logThrowable(Throwable $throwable): void
    {
        $context = new LogContext(
            correlationId: $this->resolveCorrelationId() ?? 'http-error',
            errorCode: 'http.unexpected_throwable',
            traceMetadata: [
                'transport' => 'http',
                'direction' => 'error',
            ],
            context: [
                'provider' => 'providers',
                'operation' => 'http.exception.unexpected',
                'method' => $this->request->getMethod(),
                'path' => $this->request->getPathInfo(),
                'exception_class' => $throwable::class,
                'message' => $throwable->getMessage(),
            ],
        );

        $this->structuredLogger->error('http.exception.unexpected', $context);
    }

    /**
     * @return array{0: int, 1: string, 2: string, 3: array<string, mixed>}
     */
    private function mapThrowable(Throwable $throwable): array
    {
        if ($throwable instanceof InvalidArgumentException) {
            return [
                422,
                'http.validation_error',
                'Request validation failed.',
                [
                    'message' => $throwable->getMessage(),
                ],
            ];
        }

        if ($throwable instanceof CoreException) {
            return [
                $this->mapCoreExceptionStatus($throwable),
                $throwable->errorCode(),
                $throwable->getMessage(),
                $throwable->context(),
            ];
        }

        return [
            500,
            'http.internal_server_error',
            'Internal server error.',
            [],
        ];
    }

    private function mapCoreExceptionStatus(CoreException $throwable): int
    {
        if (
            $throwable instanceof WithdrawNotProcessable
            || $throwable instanceof DuplicateWithdrawRequestBlocked
            || $throwable instanceof InsufficientWithdrawBalance
        ) {
            return 409;
        }

        return match ($this->failureClassifier->classify($throwable)) {
            FailureCategory::VALIDATION => 422,
            FailureCategory::BUSINESS => 404,
            FailureCategory::INFRASTRUCTURE_TRANSIENT,
            FailureCategory::INTERNAL => 500,
        };
    }

    private function resolveCorrelationId(): ?string
    {
        return $this->requestContextResolver->correlationId();
    }
}
