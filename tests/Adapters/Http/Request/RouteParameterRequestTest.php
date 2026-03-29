<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Request;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\RouteParameterRequest;

final class RouteParameterRequestTest extends TestCase
{
    public function testRequireUuidNormalizesValidUuidPathParameter(): void
    {
        $request = new RouteParameterRequest();

        self::assertSame(
            '7f4f4ff9-7b65-4d5d-8ec8-fc4f2eb71e4e',
            $request->requireUuid('accountId', ' 7F4F4FF9-7B65-4D5D-8EC8-FC4F2EB71E4E ')
        );
    }

    public function testRequireUuidRejectsEmptyPathParameter(): void
    {
        $request = new RouteParameterRequest();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The accountId path parameter is required.');

        $request->requireUuid('accountId', '   ');
    }

    public function testRequireUuidRejectsMalformedPathParameter(): void
    {
        $request = new RouteParameterRequest();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The withdrawId path parameter must be a valid UUID.');

        $request->requireUuid('withdrawId', 'wd_123');
    }
}
