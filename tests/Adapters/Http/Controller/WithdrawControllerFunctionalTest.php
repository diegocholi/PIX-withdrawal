<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Controller;

use Hyperf\Di\Container;
use Hyperf\Testing\Http\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Swoole\Coroutine;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;
use Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawCommand;
use Tecnofit\PixWithdrawal\Core\Application\Dto\CreateWithdrawData;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\CreateWithdraw;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

final class WithdrawControllerFunctionalTest extends TestCase
{
    public function testCreateImmediateWithdrawReturnsAcceptedPayloadAndDispatchesUseCase(): void
    {
        /** @var Container $container */
        $container = (new HyperfContainerFactory())->create();
        $useCase = new RecordingCreateWithdrawUseCase(
            new CreateWithdrawData(
                withdrawId: 'wd_123',
                correlationId: 'corr-functional-123',
                status: 'QUEUED',
            ),
        );
        $container->set(CreateWithdraw::class, $useCase);

        $client = new Client($container);
        $response = null;
        $accountId = '7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e';

        Coroutine\run(static function () use ($client, $accountId, &$response): void {
            $response = $client->json(
                "/account/{$accountId}/balance/withdraw",
                [
                    'amount' => '150.25',
                    'method' => 'PIX',
                    'pix' => [
                        'key_type' => 'EMAIL',
                        'key' => 'user@example.com',
                    ],
                    'schedule' => null,
                ],
                [
                    ApiHeader::ACCEPT => 'application/json',
                    ApiHeader::CORRELATION_ID => 'corr-functional-123',
                ]
            );
        });

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(202, $response->getStatusCode());
        self::assertSame('corr-functional-123', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
        self::assertSame(
            "/account/{$accountId}/balance/withdraw/wd_123",
            $response->getHeaderLine(ApiHeader::LOCATION)
        );
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'data' => [
                    'withdraw_id' => 'wd_123',
                    'account_id' => $accountId,
                    'status' => 'queued',
                    'scheduled' => false,
                    'scheduled_for' => null,
                    'status_url' => "/account/{$accountId}/balance/withdraw/wd_123",
                ],
                'meta' => [
                    'correlation_id' => 'corr-functional-123',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
        self::assertSame(
            [
                'account_id' => $accountId,
                'correlation_id' => 'corr-functional-123',
                'method' => 'PIX',
                'pix_key_type' => 'EMAIL',
                'pix_key' => 'user@example.com',
                'amount' => '150.25',
                'schedule_at' => null,
                'trace_metadata' => [
                    'http_method' => 'POST',
                    'route' => "/account/{$accountId}/balance/withdraw",
                ],
            ],
            $useCase->receivedCommand()?->toArray()
        );
    }

    public function testCreateScheduledWithdrawReturnsAcceptedPayloadAndDispatchesUseCase(): void
    {
        /** @var Container $container */
        $container = (new HyperfContainerFactory())->create();
        $useCase = new RecordingCreateWithdrawUseCase(
            new CreateWithdrawData(
                withdrawId: 'wd_999',
                correlationId: 'corr-functional-999',
                status: 'SCHEDULED',
            ),
        );
        $container->set(CreateWithdraw::class, $useCase);

        $client = new Client($container);
        $response = null;
        $accountId = '3bf8a6e8-f7d2-4fd9-b8fc-10b31d4d7d37';
        $scheduledFor = '2026-04-01T12:00:00+00:00';

        Coroutine\run(static function () use ($client, $accountId, $scheduledFor, &$response): void {
            $response = $client->json(
                "/account/{$accountId}/balance/withdraw",
                [
                    'amount' => '200.00',
                    'method' => 'PIX',
                    'pix' => [
                        'key_type' => 'EMAIL',
                        'key' => 'scheduled@example.com',
                    ],
                    'schedule' => [
                        'at' => $scheduledFor,
                    ],
                ],
                [
                    ApiHeader::ACCEPT => 'application/json',
                    ApiHeader::CORRELATION_ID => 'corr-functional-999',
                ]
            );
        });

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(202, $response->getStatusCode());
        self::assertSame('corr-functional-999', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
        self::assertSame(
            "/account/{$accountId}/balance/withdraw/wd_999",
            $response->getHeaderLine(ApiHeader::LOCATION)
        );
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'data' => [
                    'withdraw_id' => 'wd_999',
                    'account_id' => $accountId,
                    'status' => 'scheduled',
                    'scheduled' => true,
                    'scheduled_for' => $scheduledFor,
                    'status_url' => "/account/{$accountId}/balance/withdraw/wd_999",
                ],
                'meta' => [
                    'correlation_id' => 'corr-functional-999',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
        self::assertSame(
            [
                'account_id' => $accountId,
                'correlation_id' => 'corr-functional-999',
                'method' => 'PIX',
                'pix_key_type' => 'EMAIL',
                'pix_key' => 'scheduled@example.com',
                'amount' => '200.00',
                'schedule_at' => $scheduledFor,
                'trace_metadata' => [
                    'http_method' => 'POST',
                    'route' => "/account/{$accountId}/balance/withdraw",
                ],
            ],
            $useCase->receivedCommand()?->toArray()
        );
    }

    public function testCreateGeneratesCorrelationIdWhenHeaderIsMissing(): void
    {
        /** @var Container $container */
        $container = (new HyperfContainerFactory())->create();
        $useCase = new EchoingCreateWithdrawUseCase('wd_generated');
        $container->set(CreateWithdraw::class, $useCase);

        $client = new Client($container);
        $response = null;
        $accountId = '7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e';

        Coroutine\run(static function () use ($client, $accountId, &$response): void {
            $response = $client->json(
                "/account/{$accountId}/balance/withdraw",
                [
                    'amount' => '150.25',
                    'method' => 'PIX',
                    'pix' => [
                        'key_type' => 'EMAIL',
                        'key' => 'user@example.com',
                    ],
                    'schedule' => null,
                ],
                [
                    ApiHeader::ACCEPT => 'application/json',
                ]
            );
        });

        $responseBody = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $responseCorrelationId = $response->getHeaderLine(ApiHeader::CORRELATION_ID);

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(202, $response->getStatusCode());
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $responseCorrelationId
        );
        self::assertSame($responseCorrelationId, $responseBody['meta']['correlation_id']);
        self::assertSame($responseCorrelationId, $useCase->receivedCommand()?->correlationId());
    }

    /**
     * @dataProvider invalidCreateWithdrawPayloadProvider
     *
     * @param array<string, mixed> $payload
     */
    public function testCreateRejectsInvalidPayloadBeforeCallingUseCase(
        array $payload,
        string $expectedDetailMessage,
    ): void {
        /** @var Container $container */
        $container = (new HyperfContainerFactory())->create();
        $useCase = new RecordingCreateWithdrawUseCase(
            new CreateWithdrawData(
                withdrawId: 'wd_unused',
                correlationId: 'corr-invalid-123',
                status: 'QUEUED',
            ),
        );
        $container->set(CreateWithdraw::class, $useCase);

        $client = new Client($container);
        $response = null;
        $accountId = '7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e';

        Coroutine\run(static function () use ($client, $accountId, $payload, &$response): void {
            $response = $client->json(
                "/account/{$accountId}/balance/withdraw",
                $payload,
                [
                    ApiHeader::ACCEPT => 'application/json',
                    ApiHeader::CORRELATION_ID => 'corr-invalid-123',
                ]
            );
        });

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(422, $response->getStatusCode());
        self::assertSame('corr-invalid-123', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'error' => [
                    'code' => 'http.validation_error',
                    'message' => 'Request validation failed.',
                    'details' => [
                        'message' => $expectedDetailMessage,
                    ],
                ],
                'meta' => [
                    'correlation_id' => 'corr-invalid-123',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
        self::assertNull($useCase->receivedCommand());
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidCreateWithdrawPayloadProvider(): iterable
    {
        yield 'missing pix object' => [
            [
                'amount' => '150.25',
                'method' => 'PIX',
            ],
            'The withdraw request payload must contain a pix object.',
        ];

        yield 'unsupported method' => [
            [
                'amount' => '150.25',
                'method' => 'TED',
                'pix' => [
                    'key_type' => 'EMAIL',
                    'key' => 'user@example.com',
                ],
            ],
            'The "method" field must be "PIX", "TED" given.',
        ];

        yield 'unsupported pix key type' => [
            [
                'amount' => '150.25',
                'method' => 'PIX',
                'pix' => [
                    'key_type' => 'PHONE',
                    'key' => '+5511999999999',
                ],
            ],
            'The "pix.key_type" field must be "EMAIL", "PHONE" given.',
        ];

        yield 'invalid amount' => [
            [
                'amount' => '150.2',
                'method' => 'PIX',
                'pix' => [
                    'key_type' => 'EMAIL',
                    'key' => 'user@example.com',
                ],
            ],
            'The "amount" field must be a positive decimal with two fraction digits, "150.2" given.',
        ];

        yield 'malformed schedule' => [
            [
                'amount' => '150.25',
                'method' => 'PIX',
                'pix' => [
                    'key_type' => 'EMAIL',
                    'key' => 'user@example.com',
                ],
                'schedule' => [
                    'at' => '2026-04-01 12:00:00',
                ],
            ],
            'The "schedule.at" field must use RFC 3339 format, "2026-04-01 12:00:00" given.',
        ];
    }

    public function testCreateRejectsInvalidAccountIdPathParameterBeforeCallingUseCase(): void
    {
        /** @var Container $container */
        $container = (new HyperfContainerFactory())->create();
        $useCase = new RecordingCreateWithdrawUseCase(
            new CreateWithdrawData(
                withdrawId: 'wd_unused',
                correlationId: 'corr-invalid-route-123',
                status: 'QUEUED',
            ),
        );
        $container->set(CreateWithdraw::class, $useCase);

        $client = new Client($container);
        $response = null;

        Coroutine\run(static function () use ($client, &$response): void {
            $response = $client->json(
                '/account/invalid-account-id/balance/withdraw',
                [
                    'amount' => '150.25',
                    'method' => 'PIX',
                    'pix' => [
                        'key_type' => 'EMAIL',
                        'key' => 'user@example.com',
                    ],
                ],
                [
                    ApiHeader::ACCEPT => 'application/json',
                    ApiHeader::CORRELATION_ID => 'corr-invalid-route-123',
                ]
            );
        });

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(422, $response->getStatusCode());
        self::assertSame('corr-invalid-route-123', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'error' => [
                    'code' => 'http.validation_error',
                    'message' => 'Request validation failed.',
                    'details' => [
                        'message' => 'The accountId path parameter must be a valid UUID.',
                    ],
                ],
                'meta' => [
                    'correlation_id' => 'corr-invalid-route-123',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
        self::assertNull($useCase->receivedCommand());
    }
}

final class RecordingCreateWithdrawUseCase implements CreateWithdraw
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

final class EchoingCreateWithdrawUseCase implements CreateWithdraw
{
    private ?CreateWithdrawCommand $receivedCommand = null;

    public function __construct(
        private readonly string $withdrawId,
    ) {
    }

    public function execute(CreateWithdrawCommand $command): CreateWithdrawData
    {
        $this->receivedCommand = $command;

        return new CreateWithdrawData(
            withdrawId: $this->withdrawId,
            correlationId: $command->correlationId(),
            status: 'QUEUED',
            traceMetadata: $command->traceMetadata(),
        );
    }

    public function receivedCommand(): ?CreateWithdrawCommand
    {
        return $this->receivedCommand;
    }
}
