<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Runtime;

use DateInterval;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;
use Tecnofit\PixWithdrawal\Plugins\Time\TestClock;

final class TestClockProviderTest extends TestCase
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

    public function testContainerResolvesControllableClockInTestEnvironment(): void
    {
        putenv('APP_ENV=test');
        putenv('APP_TIMEZONE=America/Sao_Paulo');

        $container = (new HyperfContainerFactory())->create();
        $clock = $container->get(Clock::class);

        self::assertInstanceOf(TestClock::class, $clock);

        $initialNow = $clock->now();
        $clock->advance(new DateInterval('PT5M'));

        self::assertSame(
            $initialNow->add(new DateInterval('PT5M'))->format(DATE_ATOM),
            $clock->now()->format(DATE_ATOM)
        );
        self::assertSame('America/Sao_Paulo', $clock->now()->getTimezone()->getName());
    }
}
