<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Runtime;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;
use Tecnofit\PixWithdrawal\Plugins\Time\SystemClock;

final class SystemClockProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('APP_ENV');
        putenv('APP_DEBUG');
        putenv('APP_CHARSET');
        putenv('APP_LOG_MAX_FILES');
        putenv('APP_LOG_LEVEL');
        putenv('APP_LOCALE');
        putenv('APP_FALLBACK_LOCALE');
        putenv('APP_NAME');
        putenv('APP_TIMEZONE');

        parent::tearDown();
    }

    public function testContainerResolvesSystemClockUsingConfiguredRuntimeTimezone(): void
    {
        putenv('APP_ENV=local');
        putenv('APP_TIMEZONE=America/Sao_Paulo');

        $container = (new HyperfContainerFactory())->create();
        $clock = $container->get(Clock::class);
        $now = $clock->now();

        self::assertInstanceOf(SystemClock::class, $clock);
        self::assertSame('America/Sao_Paulo', $now->getTimezone()->getName());
    }
}
