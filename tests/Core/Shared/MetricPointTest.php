<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Shared;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tecnofit\PixWithdrawal\Core\Shared\MetricName;
use Tecnofit\PixWithdrawal\Core\Shared\MetricPoint;

final class MetricPointTest extends TestCase
{
    public function testMetricNameCatalogCoversProcessingLifecycle(): void
    {
        self::assertSame(
            [
                'withdraw.queued',
                'withdraw.processing.started',
                'withdraw.processed',
                'withdraw.failed.insufficient_funds',
                'withdraw.failed.internal',
                'withdraw.retried',
            ],
            array_map(
                static fn (MetricName $metricName): string => $metricName->value,
                MetricName::cases()
            )
        );
    }

    public function testMetricPointIsReadonlyAndSerializable(): void
    {
        $metricPoint = new MetricPoint(
            name: MetricName::WITHDRAW_FAILED_INTERNAL,
            value: 2,
            tags: [
                'status' => 'FAILED_INTERNAL',
                'attempt' => 3,
            ]
        );

        self::assertTrue((new ReflectionClass($metricPoint))->isReadOnly());
        self::assertSame(
            [
                'name' => 'withdraw.failed.internal',
                'value' => 2,
                'tags' => [
                    'status' => 'FAILED_INTERNAL',
                    'attempt' => 3,
                ],
            ],
            $metricPoint->toArray()
        );
    }
}
