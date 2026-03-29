<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Identifier;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\CorrelationIdGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\UuidGenerator;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;
use Tecnofit\PixWithdrawal\Plugins\Identifier\FakeUuidGenerator;
use Tecnofit\PixWithdrawal\Plugins\Identifier\RandomUuidGenerator;

final class UuidProviderResolutionTest extends TestCase
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

    public function testContainerResolvesRandomUuidProvidersFromConfiguredDrivers(): void
    {
        $container = (new HyperfContainerFactory())->create();

        self::assertInstanceOf(RandomUuidGenerator::class, $container->get(UuidGenerator::class));
        self::assertInstanceOf(RandomUuidGenerator::class, $container->get(CorrelationIdGenerator::class));
    }

    public function testContainerResolvesPredictableUuidProviderInTestEnvironment(): void
    {
        putenv('APP_ENV=test');

        $container = (new HyperfContainerFactory())->create();
        $uuidGenerator = $container->get(UuidGenerator::class);

        self::assertInstanceOf(FakeUuidGenerator::class, $uuidGenerator);
        self::assertSame('00000000-0000-4000-8000-000000000001', $uuidGenerator->generate());
        self::assertSame('00000000-0000-4000-8000-000000000002', $uuidGenerator->generate());
        self::assertInstanceOf(RandomUuidGenerator::class, $container->get(CorrelationIdGenerator::class));
    }
}
