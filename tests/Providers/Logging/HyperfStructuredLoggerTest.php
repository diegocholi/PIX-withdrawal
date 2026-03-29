<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Logging;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Logging\HyperfStructuredLogger;
use Tecnofit\PixWithdrawal\Providers\Logging\ProviderLogContextEnricher;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderPayloadNormalizer;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderSensitiveDataMasker;
use Tecnofit\PixWithdrawal\Providers\Serialization\SafeLogPayloadSerializer;

final class HyperfStructuredLoggerTest extends TestCase
{
    public function testInfoSerializesMaskedStructuredContextBeforeDelegatingToLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                'http.request.received',
                [
                    'correlation_id' => 'corr_123',
                    'withdraw_id' => null,
                    'account_id' => 'acc_123',
                    'status' => null,
                    'error_code' => null,
                    'trace_metadata' => [],
                    'context' => [
                        'provider' => 'providers',
                        'operation' => 'http.request.received',
                        'pix_key' => 'u***@example.com',
                        'path' => '/account/acc_123/balance/withdraw',
                    ],
                ]
            );

        $structuredLogger = new HyperfStructuredLogger(
            $logger,
            new SafeLogPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer()),
            new ProviderLogContextEnricher()
        );

        $structuredLogger->info(
            'http.request.received',
            new LogContext(
                correlationId: 'corr_123',
                accountId: 'acc_123',
                context: [
                    'pix_key' => 'user@example.com',
                    'path' => '/account/acc_123/balance/withdraw',
                ]
            )
        );
    }

    public function testWarningMasksNestedHttpContextBeforeDelegatingToLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'http.request.rejected',
                [
                    'correlation_id' => 'corr_http',
                    'withdraw_id' => null,
                    'account_id' => null,
                    'status' => null,
                    'error_code' => null,
                    'trace_metadata' => [],
                    'context' => [
                        'provider' => 'providers',
                        'operation' => 'http.request.rejected',
                        'pix' => [
                            'key' => 'n***@example.com',
                            'key_type' => 'EMAIL',
                        ],
                        'authorization' => '***',
                    ],
                ]
            );

        $structuredLogger = new HyperfStructuredLogger(
            $logger,
            new SafeLogPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer()),
            new ProviderLogContextEnricher()
        );

        $structuredLogger->warning(
            'http.request.rejected',
            new LogContext(
                correlationId: 'corr_http',
                context: [
                    'pix' => [
                        'key' => 'nested@example.com',
                        'key_type' => 'EMAIL',
                    ],
                    'authorization' => 'Bearer secret-token',
                ]
            )
        );
    }
}
