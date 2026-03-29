<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Enum;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\FailureCategory;

final class FailureCategoryTest extends TestCase
{
    public function testFailureCategoryCasesRemainStable(): void
    {
        self::assertSame(
            ['BUSINESS', 'VALIDATION', 'INFRASTRUCTURE_TRANSIENT', 'INTERNAL'],
            array_map(static fn (FailureCategory $category) => $category->value, FailureCategory::cases())
        );
    }

    public function testOnlyTransientInfrastructureCategoryIsRetryable(): void
    {
        self::assertFalse(FailureCategory::BUSINESS->isRetryable());
        self::assertFalse(FailureCategory::VALIDATION->isRetryable());
        self::assertTrue(FailureCategory::INFRASTRUCTURE_TRANSIENT->isRetryable());
        self::assertFalse(FailureCategory::INTERNAL->isRetryable());
    }
}
