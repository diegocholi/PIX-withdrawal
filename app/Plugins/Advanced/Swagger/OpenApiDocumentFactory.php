<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Advanced\Swagger;

use Tecnofit\PixWithdrawal\Plugins\Advanced\Config\AdvancedPluginConfig;

final readonly class OpenApiDocumentFactory
{
    public function __construct(
        private AdvancedPluginConfig $config,
    ) {
    }

    public function create(): OpenApiDocument
    {
        return new OpenApiDocument([
            'openapi' => '3.0.3',
            'info' => [
                'title' => $this->config->openApiTitle(),
                'version' => $this->config->openApiVersion(),
                'description' => 'API HTTP da plataforma de saque PIX com fluxo assincrono de criacao e consulta de status.',
            ],
            'tags' => [
                [
                    'name' => 'withdraw',
                    'description' => 'Operacoes publicas de saque PIX.',
                ],
            ],
            'paths' => [
                '/account/{accountId}/balance/withdraw' => [
                    'post' => [
                        'tags' => ['withdraw'],
                        'summary' => 'Solicita a criacao de um saque PIX.',
                        'description' => 'Aceita a solicitacao para processamento assincrono. O resultado final deve ser acompanhado no endpoint de status.',
                        'operationId' => 'createWithdraw',
                        'parameters' => [
                            $this->accountIdParameter(),
                            $this->correlationIdHeaderParameter(),
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/CreateWithdrawRequest',
                                    ],
                                    'examples' => [
                                        'immediate_withdraw' => [
                                            'summary' => 'Saque imediato',
                                            'value' => [
                                                'amount' => '150.25',
                                                'method' => 'PIX',
                                                'pix' => [
                                                    'key_type' => 'EMAIL',
                                                    'key' => 'user@example.com',
                                                ],
                                                'schedule' => null,
                                            ],
                                        ],
                                        'scheduled_withdraw' => [
                                            'summary' => 'Saque agendado',
                                            'value' => [
                                                'amount' => '150.25',
                                                'method' => 'PIX',
                                                'pix' => [
                                                    'key_type' => 'EMAIL',
                                                    'key' => 'user@example.com',
                                                ],
                                                'schedule' => [
                                                    'at' => '2026-04-01T09:00:00-03:00',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '202' => [
                                'description' => 'Solicitacao aceita para processamento assincrono.',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/CreateWithdrawAcceptedResponse',
                                        ],
                                        'examples' => [
                                            'accepted_immediate' => [
                                                'summary' => 'Saque imediato aceito',
                                                'value' => [
                                                    'data' => [
                                                        'withdraw_id' => 'wd_123',
                                                        'account_id' => 'acc_123',
                                                        'status' => 'queued',
                                                        'scheduled' => false,
                                                        'scheduled_for' => null,
                                                        'status_url' => '/account/acc_123/balance/withdraw/wd_123',
                                                    ],
                                                    'meta' => [
                                                        'correlation_id' => 'corr_123',
                                                    ],
                                                ],
                                            ],
                                            'accepted_scheduled' => [
                                                'summary' => 'Saque agendado aceito',
                                                'value' => [
                                                    'data' => [
                                                        'withdraw_id' => 'wd_456',
                                                        'account_id' => 'acc_123',
                                                        'status' => 'scheduled',
                                                        'scheduled' => true,
                                                        'scheduled_for' => '2026-04-01T09:00:00-03:00',
                                                        'status_url' => '/account/acc_123/balance/withdraw/wd_456',
                                                    ],
                                                    'meta' => [
                                                        'correlation_id' => 'corr_456',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '409' => [
                                'description' => 'Conflito de negocio na criacao, como retry recente ou saldo insuficiente para saque imediato.',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/ErrorResponse',
                                        ],
                                        'examples' => [
                                            'insufficient_funds' => [
                                                'value' => [
                                                    'error' => [
                                                        'code' => 'withdraw.insufficient_funds',
                                                        'message' => 'Account balance is insufficient for immediate withdraw creation.',
                                                        'details' => [
                                                            'account_id' => 'acc_123',
                                                            'requested_amount' => '150.25',
                                                        ],
                                                    ],
                                                    'meta' => [
                                                        'correlation_id' => 'corr_409_balance',
                                                    ],
                                                ],
                                            ],
                                            'duplicate_request_blocked' => [
                                                'value' => [
                                                    'error' => [
                                                        'code' => 'withdraw.duplicate_request_blocked',
                                                        'message' => 'An equivalent withdraw request was already accepted recently.',
                                                        'details' => [
                                                            'withdraw_id' => 'wd_409',
                                                            'retry_after_seconds' => 42,
                                                        ],
                                                    ],
                                                    'meta' => [
                                                        'correlation_id' => 'corr_409_retry',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '422' => $this->errorResponse(
                                'Falha de validacao da borda HTTP.',
                                'http.validation_error',
                                'Request validation failed.',
                                ['message' => 'Field amount must be a decimal string with two fraction digits.'],
                                'corr_422'
                            ),
                            '500' => $this->errorResponse(
                                'Falha interna inesperada.',
                                'http.internal_server_error',
                                'Internal server error.',
                                [],
                                'corr_500'
                            ),
                        ],
                    ],
                ],
                '/account/{accountId}/balance/withdraw/{withdrawId}' => [
                    'get' => [
                        'tags' => ['withdraw'],
                        'summary' => 'Consulta o status publico de um saque PIX.',
                        'description' => 'Leitura do estado publico do fluxo assincrono de saque.',
                        'operationId' => 'findWithdrawStatus',
                        'parameters' => [
                            $this->accountIdParameter(),
                            [
                                'name' => 'withdrawId',
                                'in' => 'path',
                                'required' => true,
                                'description' => 'UUID da solicitacao de saque.',
                                'schema' => [
                                    'type' => 'string',
                                    'format' => 'uuid',
                                ],
                            ],
                            $this->correlationIdHeaderParameter(),
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Status publico do saque.',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/FindWithdrawStatusResponse',
                                        ],
                                        'examples' => [
                                            'completed_withdraw' => [
                                                'summary' => 'Saque concluido',
                                                'value' => [
                                                    'data' => [
                                                        'withdraw_id' => 'wd_123',
                                                        'account_id' => 'acc_123',
                                                        'status' => 'completed',
                                                        'amount' => '150.25',
                                                        'method' => 'PIX',
                                                        'pix' => [
                                                            'key_type' => 'EMAIL',
                                                            'key_masked' => 'u***@example.com',
                                                        ],
                                                        'scheduled' => false,
                                                        'scheduled_for' => null,
                                                        'processed_at' => '2026-04-01T12:05:00+00:00',
                                                        'error_reason' => null,
                                                        'failure_category' => null,
                                                    ],
                                                    'meta' => [
                                                        'correlation_id' => 'corr_123',
                                                    ],
                                                ],
                                            ],
                                            'failed_withdraw' => [
                                                'summary' => 'Saque com falha de saldo',
                                                'value' => [
                                                    'data' => [
                                                        'withdraw_id' => 'wd_456',
                                                        'account_id' => 'acc_123',
                                                        'status' => 'failed',
                                                        'amount' => '150.25',
                                                        'method' => 'PIX',
                                                        'pix' => [
                                                            'key_type' => 'EMAIL',
                                                            'key_masked' => 'u***@example.com',
                                                        ],
                                                        'scheduled' => false,
                                                        'scheduled_for' => null,
                                                        'processed_at' => '2026-04-01T12:05:00+00:00',
                                                        'error_reason' => 'Insufficient funds.',
                                                        'failure_category' => 'insufficient_funds',
                                                    ],
                                                    'meta' => [
                                                        'correlation_id' => 'corr_456',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '404' => $this->errorResponse(
                                'Recurso nao encontrado.',
                                'withdraw.not_found',
                                'Withdraw was not found for processing.',
                                ['withdraw_id' => 'wd_404'],
                                'corr_404'
                            ),
                            '500' => $this->errorResponse(
                                'Falha interna inesperada.',
                                'http.internal_server_error',
                                'Internal server error.',
                                [],
                                'corr_500'
                            ),
                        ],
                    ],
                ],
            ],
            'components' => [
                'parameters' => [
                    'XCorrelationId' => $this->correlationIdHeaderParameter(),
                ],
                'schemas' => [
                    'CorrelationMeta' => [
                        'type' => 'object',
                        'required' => ['correlation_id'],
                        'properties' => [
                            'correlation_id' => [
                                'type' => 'string',
                                'example' => 'corr_123',
                            ],
                        ],
                    ],
                    'CreateWithdrawPix' => [
                        'type' => 'object',
                        'required' => ['key_type', 'key'],
                        'properties' => [
                            'key_type' => [
                                'type' => 'string',
                                'enum' => ['EMAIL'],
                                'example' => 'EMAIL',
                            ],
                            'key' => [
                                'type' => 'string',
                                'example' => 'user@example.com',
                            ],
                        ],
                    ],
                    'CreateWithdrawSchedule' => [
                        'type' => 'object',
                        'required' => ['at'],
                        'properties' => [
                            'at' => [
                                'type' => 'string',
                                'format' => 'date-time',
                                'description' => 'Data futura no formato RFC 3339 para agendar o saque.',
                                'example' => '2026-04-01T09:00:00-03:00',
                            ],
                        ],
                        'example' => [
                            'at' => '2026-04-01T09:00:00-03:00',
                        ],
                    ],
                    'CreateWithdrawRequest' => [
                        'type' => 'object',
                        'required' => ['amount', 'method', 'pix'],
                        'properties' => [
                            'amount' => [
                                'type' => 'string',
                                'example' => '150.25',
                            ],
                            'method' => [
                                'type' => 'string',
                                'enum' => ['PIX'],
                                'example' => 'PIX',
                            ],
                            'pix' => [
                                '$ref' => '#/components/schemas/CreateWithdrawPix',
                            ],
                            'schedule' => [
                                'nullable' => true,
                                'allOf' => [
                                    [
                                        '$ref' => '#/components/schemas/CreateWithdrawSchedule',
                                    ],
                                ],
                            ],
                        ],
                        'example' => [
                            'amount' => '150.25',
                            'method' => 'PIX',
                            'pix' => [
                                'key_type' => 'EMAIL',
                                'key' => 'user@example.com',
                            ],
                            'schedule' => [
                                'at' => '2026-04-01T09:00:00-03:00',
                            ],
                        ],
                    ],
                    'CreateWithdrawAcceptedData' => [
                        'type' => 'object',
                        'required' => ['withdraw_id', 'account_id', 'status', 'scheduled', 'scheduled_for', 'status_url'],
                        'properties' => [
                            'withdraw_id' => [
                                'type' => 'string',
                                'example' => 'wd_123',
                            ],
                            'account_id' => [
                                'type' => 'string',
                                'example' => 'acc_123',
                            ],
                            'status' => [
                                'type' => 'string',
                                'enum' => ['queued', 'scheduled'],
                                'example' => 'queued',
                            ],
                            'scheduled' => [
                                'type' => 'boolean',
                                'example' => false,
                            ],
                            'scheduled_for' => [
                                'type' => 'string',
                                'nullable' => true,
                                'format' => 'date-time',
                                'example' => null,
                            ],
                            'status_url' => [
                                'type' => 'string',
                                'example' => '/account/acc_123/balance/withdraw/wd_123',
                            ],
                        ],
                    ],
                    'CreateWithdrawAcceptedResponse' => [
                        'type' => 'object',
                        'required' => ['data', 'meta'],
                        'properties' => [
                            'data' => [
                                '$ref' => '#/components/schemas/CreateWithdrawAcceptedData',
                            ],
                            'meta' => [
                                '$ref' => '#/components/schemas/CorrelationMeta',
                            ],
                        ],
                    ],
                    'FindWithdrawStatusPix' => [
                        'type' => 'object',
                        'required' => ['key_type', 'key_masked'],
                        'properties' => [
                            'key_type' => [
                                'type' => 'string',
                                'enum' => ['EMAIL'],
                                'example' => 'EMAIL',
                            ],
                            'key_masked' => [
                                'type' => 'string',
                                'example' => 'u***@example.com',
                            ],
                        ],
                    ],
                    'FindWithdrawStatusData' => [
                        'type' => 'object',
                        'required' => [
                            'withdraw_id',
                            'account_id',
                            'status',
                            'amount',
                            'method',
                            'pix',
                            'scheduled',
                            'scheduled_for',
                            'processed_at',
                            'error_reason',
                            'failure_category',
                        ],
                        'properties' => [
                            'withdraw_id' => [
                                'type' => 'string',
                                'example' => 'wd_123',
                            ],
                            'account_id' => [
                                'type' => 'string',
                                'example' => 'acc_123',
                            ],
                            'status' => [
                                'type' => 'string',
                                'enum' => ['pending', 'scheduled', 'queued', 'processing', 'completed', 'failed'],
                                'example' => 'processing',
                            ],
                            'amount' => [
                                'type' => 'string',
                                'example' => '150.25',
                            ],
                            'method' => [
                                'type' => 'string',
                                'enum' => ['PIX'],
                                'example' => 'PIX',
                            ],
                            'pix' => [
                                '$ref' => '#/components/schemas/FindWithdrawStatusPix',
                            ],
                            'scheduled' => [
                                'type' => 'boolean',
                                'example' => false,
                            ],
                            'scheduled_for' => [
                                'type' => 'string',
                                'nullable' => true,
                                'format' => 'date-time',
                                'example' => null,
                            ],
                            'processed_at' => [
                                'type' => 'string',
                                'nullable' => true,
                                'format' => 'date-time',
                                'example' => null,
                            ],
                            'error_reason' => [
                                'type' => 'string',
                                'nullable' => true,
                                'example' => 'Insufficient funds.',
                            ],
                            'failure_category' => [
                                'type' => 'string',
                                'nullable' => true,
                                'enum' => ['insufficient_funds', 'validation', 'internal'],
                                'example' => 'insufficient_funds',
                            ],
                        ],
                    ],
                    'FindWithdrawStatusResponse' => [
                        'type' => 'object',
                        'required' => ['data', 'meta'],
                        'properties' => [
                            'data' => [
                                '$ref' => '#/components/schemas/FindWithdrawStatusData',
                            ],
                            'meta' => [
                                '$ref' => '#/components/schemas/CorrelationMeta',
                            ],
                        ],
                    ],
                    'ErrorBody' => [
                        'type' => 'object',
                        'required' => ['code', 'message', 'details'],
                        'properties' => [
                            'code' => [
                                'type' => 'string',
                                'example' => 'http.validation_error',
                            ],
                            'message' => [
                                'type' => 'string',
                                'example' => 'Request validation failed.',
                            ],
                            'details' => [
                                'type' => 'object',
                                'additionalProperties' => true,
                            ],
                        ],
                    ],
                    'ErrorResponse' => [
                        'type' => 'object',
                        'required' => ['error', 'meta'],
                        'properties' => [
                            'error' => [
                                '$ref' => '#/components/schemas/ErrorBody',
                            ],
                            'meta' => [
                                '$ref' => '#/components/schemas/CorrelationMeta',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function accountIdParameter(): array
    {
        return [
            'name' => 'accountId',
            'in' => 'path',
            'required' => true,
            'description' => 'UUID da conta origem do saque.',
            'schema' => [
                'type' => 'string',
                'format' => 'uuid',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function correlationIdHeaderParameter(): array
    {
        return [
            'name' => 'X-Correlation-Id',
            'in' => 'header',
            'required' => false,
            'description' => 'Identificador opcional de rastreio. Quando ausente, a borda HTTP gera um valor automaticamente.',
            'schema' => [
                'type' => 'string',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $details
     * @return array<string, mixed>
     */
    private function errorResponse(
        string $description,
        string $code,
        string $message,
        array $details,
        string $correlationId,
    ): array {
        return [
            'description' => $description,
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/ErrorResponse',
                    ],
                    'examples' => [
                        'default' => [
                            'value' => [
                                'error' => [
                                    'code' => $code,
                                    'message' => $message,
                                    'details' => $details,
                                ],
                                'meta' => [
                                    'correlation_id' => $correlationId,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
