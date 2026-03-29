<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Shared;

use PHPUnit\Framework\TestCase;
use stdClass;
use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;
use Tecnofit\PixWithdrawal\Core\Shared\Exception\InvalidTraceContext;
use Tecnofit\PixWithdrawal\Core\Shared\TraceContext;

final class TraceContextTest extends TestCase
{
    public function testNormalizesCorrelationIdAndMetadata(): void
    {
        $traceContext = new TraceContext(' corr-1 ', [
            'trace_id' => 'trace-1',
            'nested' => [
                'span_id' => 'span-1',
                'flags' => ['sampled', 'exported'],
            ],
        ]);

        self::assertSame('corr-1', $traceContext->correlationId());
        self::assertSame(
            [
                'trace_id' => 'trace-1',
                'nested' => [
                    'span_id' => 'span-1',
                    'flags' => ['sampled', 'exported'],
                ],
            ],
            $traceContext->traceMetadata()
        );
        self::assertSame(
            [
                'correlation_id' => 'corr-1',
                'trace_metadata' => [
                    'trace_id' => 'trace-1',
                    'nested' => [
                        'span_id' => 'span-1',
                        'flags' => ['sampled', 'exported'],
                    ],
                ],
            ],
            $traceContext->toArray()
        );
    }

    public function testRejectsEmptyCorrelationId(): void
    {
        try {
            new TraceContext('   ');
            self::fail('Expected exception was not thrown.');
        } catch (InvalidTraceContext $exception) {
            self::assertSame(ErrorCode::TRACE_EMPTY_CORRELATION_ID, $exception->errorCode());
        }
    }

    public function testRejectsEmptyMetadataKey(): void
    {
        try {
            new TraceContext('corr-1', ['   ' => 'value']);
            self::fail('Expected exception was not thrown.');
        } catch (InvalidTraceContext $exception) {
            self::assertSame(ErrorCode::TRACE_INVALID_METADATA_KEY, $exception->errorCode());
            self::assertSame(['path' => 'trace_metadata'], $exception->context());
        }
    }

    public function testRejectsUnsupportedMetadataValue(): void
    {
        try {
            new TraceContext('corr-1', ['trace' => new stdClass()]);
            self::fail('Expected exception was not thrown.');
        } catch (InvalidTraceContext $exception) {
            self::assertSame(ErrorCode::TRACE_UNSUPPORTED_METADATA_VALUE, $exception->errorCode());
            self::assertSame(
                [
                    'path' => 'trace_metadata.trace',
                    'type' => stdClass::class,
                ],
                $exception->context()
            );
        }
    }
}
