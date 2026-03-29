<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Mapper;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Mapper\FindWithdrawStatusRequestMapper;

final class FindWithdrawStatusRequestMapperTest extends TestCase
{
    public function testMapBuildsFindWithdrawStatusInput(): void
    {
        $mapper = new FindWithdrawStatusRequestMapper();

        $query = $mapper->map(
            accountId: ' acc_123 ',
            withdrawId: ' wd_123 ',
            correlationId: ' corr_123 ',
            traceMetadata: [
                'http_method' => 'GET',
                'route' => '/account/acc_123/balance/withdraw/wd_123',
                'account_id' => 'acc_123',
            ],
        );

        self::assertSame(
            [
                'account_id' => 'acc_123',
                'withdraw_id' => 'wd_123',
                'correlation_id' => 'corr_123',
                'trace_metadata' => [
                    'http_method' => 'GET',
                    'route' => '/account/acc_123/balance/withdraw/wd_123',
                    'account_id' => 'acc_123',
                ],
            ],
            $query->toArray()
        );
    }
}
