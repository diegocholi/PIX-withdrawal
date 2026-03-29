<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli\Support;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliOutputSanitizer;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderSensitiveDataMasker;

final class CliOutputSanitizerTest extends TestCase
{
    public function testSanitizeMasksCliSensitiveFieldsAndNestedSecrets(): void
    {
        $sanitizer = new CliOutputSanitizer(new ProviderSensitiveDataMasker());

        $payload = $sanitizer->sanitize([
            'username' => 'pix_withdrawal',
            'from_address' => 'no-reply@pix.local',
            'checks' => [
                'mail' => [
                    'from_address' => 'support@pix.local',
                    'password' => 'super-secret',
                ],
            ],
        ]);

        self::assertSame('***', $payload['username']);
        self::assertSame('n***@pix.local', $payload['from_address']);
        self::assertSame('s***@pix.local', $payload['checks']['mail']['from_address']);
        self::assertSame('***', $payload['checks']['mail']['password']);
    }
}
