<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Shared;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

final class LogContextTest extends TestCase
{
    public function testSerializesStructuredLoggerFieldsWithTraceData(): void
    {
        $context = new LogContext(
            correlationId: ' corr-1 ',
            withdrawId: ' wd-1 ',
            accountId: ' acc-1 ',
            status: ' DONE ',
            errorCode: ' withdraw.failed ',
            traceMetadata: ['trace_id' => 'trace-1'],
            context: ['attempt' => 2]
        );

        self::assertTrue((new ReflectionClass($context))->isReadOnly());
        self::assertSame(
            [
                'correlation_id' => 'corr-1',
                'withdraw_id' => 'wd-1',
                'account_id' => 'acc-1',
                'status' => 'DONE',
                'error_code' => 'withdraw.failed',
                'trace_metadata' => ['trace_id' => 'trace-1'],
                'context' => ['attempt' => 2],
            ],
            $context->toArray()
        );
    }

    public function testBlankOptionalFieldsBecomeNull(): void
    {
        $context = new LogContext(
            correlationId: 'corr-2',
            withdrawId: '   ',
            accountId: '',
            status: ' ',
            errorCode: "\n",
        );

        self::assertSame(
            [
                'correlation_id' => 'corr-2',
                'withdraw_id' => null,
                'account_id' => null,
                'status' => null,
                'error_code' => null,
                'trace_metadata' => [],
                'context' => [],
            ],
            $context->toArray()
        );
    }
}
