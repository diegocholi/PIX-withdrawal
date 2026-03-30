<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Exception\Handler;

use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpServer\Contract\RequestInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tecnofit\PixWithdrawal\Adapters\Http\Exception\PublicErrorSanitizer;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\HttpRequestContextResolver;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ErrorResponseFactory;
use Tecnofit\PixWithdrawal\Core\Application\Exception\AccountNotFound;
use Tecnofit\PixWithdrawal\Core\Application\Exception\DuplicateWithdrawRequestBlocked;
use Tecnofit\PixWithdrawal\Core\Application\Exception\InsufficientWithdrawBalance;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotFound;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotProcessable;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\FailureCategoryClassifier;
use Tecnofit\PixWithdrawal\Adapters\Http\Exception\Handler\UnexpectedThrowableHandler;

final class UnexpectedThrowableHandlerTest extends TestCase
{
    public function testHandlerReturnsJsonInternalServerErrorPayload(): void
    {
        $handler = $this->handlerWithCorrelationId('corr_500');
        $response = $handler->handle(new RuntimeException('boom'), new Response());

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('content-type'));
        self::assertSame('corr_500', $response->getHeaderLine('X-Correlation-Id'));
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'error' => [
                    'code' => 'http.internal_server_error',
                    'message' => 'Internal server error.',
                    'details' => [],
                ],
                'meta' => [
                    'correlation_id' => 'corr_500',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
    }

    public function testHandlerMapsValidationExceptionsToHttp422(): void
    {
        $handler = $this->handlerWithCorrelationId('corr_422');
        $response = $handler->handle(new InvalidArgumentException('The "amount" field must be greater than zero.'), new Response());

        self::assertSame(422, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'error' => [
                    'code' => 'http.validation_error',
                    'message' => 'Request validation failed.',
                    'details' => [
                        'message' => 'The "amount" field must be greater than zero.',
                    ],
                ],
                'meta' => [
                    'correlation_id' => 'corr_422',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
    }

    public function testHandlerSanitizesInternalValidationDetailsFromPublicPayload(): void
    {
        $handler = $this->handlerWithCorrelationId('corr_hidden_422');
        $response = $handler->handle(
            new InvalidArgumentException('SQLSTATE[HY000] invalid amount on mysql repository.'),
            new Response()
        );

        self::assertSame(422, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'error' => [
                    'code' => 'http.validation_error',
                    'message' => 'Request validation failed.',
                    'details' => [],
                ],
                'meta' => [
                    'correlation_id' => 'corr_hidden_422',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
    }

    public function testHandlerMapsCoreNotFoundExceptionsToHttp404(): void
    {
        $handler = $this->handlerWithCorrelationId('corr_404');
        $response = $handler->handle(WithdrawNotFound::withId('wd_404'), new Response());

        self::assertSame(404, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'error' => [
                    'code' => 'withdraw.not_found',
                    'message' => 'Withdraw was not found for processing.',
                    'details' => [
                        'withdraw_id' => 'wd_404',
                    ],
                ],
                'meta' => [
                    'correlation_id' => 'corr_404',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
    }

    public function testHandlerMapsAccountNotFoundToHttp404(): void
    {
        $handler = $this->handlerWithCorrelationId('corr_account_404');
        $response = $handler->handle(AccountNotFound::withId('acc_404'), new Response());

        self::assertSame(404, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'error' => [
                    'code' => 'account.not_found',
                    'message' => 'Account was not found for withdraw creation.',
                    'details' => [
                        'account_id' => 'acc_404',
                    ],
                ],
                'meta' => [
                    'correlation_id' => 'corr_account_404',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
    }

    public function testHandlerMapsWithdrawNotProcessableToHttp409(): void
    {
        $handler = $this->handlerWithCorrelationId('corr_409');
        $response = $handler->handle(
            WithdrawNotProcessable::withStatus('wd_409', WithdrawStatus::DONE),
            new Response()
        );

        self::assertSame(409, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'error' => [
                    'code' => 'withdraw.not_processable',
                    'message' => 'Withdraw is not eligible for processing.',
                    'details' => [
                        'withdraw_id' => 'wd_409',
                        'status' => 'DONE',
                    ],
                ],
                'meta' => [
                    'correlation_id' => 'corr_409',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
    }

    public function testHandlerMapsDuplicateWithdrawRequestBlockedToHttp409(): void
    {
        $handler = $this->handlerWithCorrelationId('corr_duplicate_409');
        $response = $handler->handle(
            DuplicateWithdrawRequestBlocked::becauseRecentEquivalentRequestExists(
                AccountWithdraw::createQueued(
                    id: 'wd_duplicate_409',
                    accountId: 'acc-1',
                    method: WithdrawMethod::PIX,
                    amount: Money::fromDecimal('20.00'),
                    correlationId: 'corr-existing',
                    idempotencyKey: 'idem-existing',
                    queuedAt: new \DateTimeImmutable('2026-03-28T10:00:00+00:00'),
                    duplicateGuardFingerprint: 'guard-existing',
                ),
                47,
            ),
            new Response()
        );

        self::assertSame(409, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'error' => [
                    'code' => 'withdraw.duplicate_request_blocked',
                    'message' => 'An equivalent withdraw request was already accepted recently.',
                    'details' => [
                        'withdraw_id' => 'wd_duplicate_409',
                        'retry_after_seconds' => 47,
                    ],
                ],
                'meta' => [
                    'correlation_id' => 'corr_duplicate_409',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
    }

    public function testHandlerMapsInsufficientWithdrawBalanceToHttp409(): void
    {
        $handler = $this->handlerWithCorrelationId('corr_balance_409');
        $response = $handler->handle(
            InsufficientWithdrawBalance::forImmediateWithdraw(
                \Tecnofit\PixWithdrawal\Core\Domain\Entity\Account::create(
                    'acc-1',
                    'Main Account',
                    Money::fromDecimal('10.00'),
                    new \DateTimeImmutable('2026-03-28T10:00:00+00:00'),
                ),
                Money::fromDecimal('20.00'),
            ),
            new Response()
        );

        self::assertSame(409, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'error' => [
                    'code' => 'withdraw.insufficient_funds',
                    'message' => 'Account balance is insufficient for immediate withdraw creation.',
                    'details' => [
                        'account_id' => 'acc-1',
                        'requested_amount' => '20.00',
                    ],
                ],
                'meta' => [
                    'correlation_id' => 'corr_balance_409',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
    }

    private function handlerWithCorrelationId(?string $correlationId): UnexpectedThrowableHandler
    {
        $request = $this->createConfiguredMock(RequestInterface::class, [
            'getAttribute' => null,
            'header' => $correlationId,
        ]);

        return new UnexpectedThrowableHandler(
            new ErrorResponseFactory($this->jsonResponseStub()),
            $request,
            new HttpRequestContextResolver($request),
            new FailureCategoryClassifier(),
            new PublicErrorSanitizer(),
            $this->createMock(StructuredLogger::class),
        );
    }

    private function jsonResponseStub(): \Hyperf\HttpServer\Contract\ResponseInterface
    {
        $response = $this->createMock(\Hyperf\HttpServer\Contract\ResponseInterface::class);
        $response
            ->method('json')
            ->willReturnCallback(static function (array $payload): Response {
                return (new Response())
                    ->withHeader('content-type', 'application/json; charset=utf-8')
                    ->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream(json_encode($payload, JSON_THROW_ON_ERROR)));
            });

        return $response;
    }
}
