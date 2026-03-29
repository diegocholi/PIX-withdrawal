<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Exception;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Exception\PublicErrorSanitizer;

final class PublicErrorSanitizerTest extends TestCase
{
    public function testSanitizeAlwaysReturnsGenericPayloadForServerErrors(): void
    {
        $sanitizer = new PublicErrorSanitizer();

        self::assertSame(
            [
                'message' => 'Internal server error.',
                'details' => [],
            ],
            $sanitizer->sanitize(
                500,
                'SQLSTATE[HY000] Kafka provider failed.',
                ['sql' => 'select * from withdraws']
            )
        );
    }

    public function testSanitizeRemovesSensitiveDetailsAndUnsafeValidationMessage(): void
    {
        $sanitizer = new PublicErrorSanitizer();

        self::assertSame(
            [
                'message' => 'Request validation failed.',
                'details' => [
                    'field' => 'amount',
                ],
            ],
            $sanitizer->sanitize(
                422,
                'SQL validation failed in adapter repository.',
                [
                    'field' => 'amount',
                    'sql' => 'select * from accounts',
                    'message' => 'mysql syntax error',
                ]
            )
        );
    }

    public function testSanitizePreservesSafeBusinessPayload(): void
    {
        $sanitizer = new PublicErrorSanitizer();

        self::assertSame(
            [
                'message' => 'Withdraw was not found for processing.',
                'details' => [
                    'withdraw_id' => 'wd_123',
                ],
            ],
            $sanitizer->sanitize(
                404,
                'Withdraw was not found for processing.',
                ['withdraw_id' => 'wd_123']
            )
        );
    }
}
