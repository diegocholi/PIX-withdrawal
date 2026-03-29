<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Hyperf\Bootstrap;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfRuntimeBootstrap;

final class HyperfRuntimeBootstrapTest extends TestCase
{
    public function testBootApplicationBuildsSharedRuntimeForCliAndHttpEntrypoints(): void
    {
        $application = (new HyperfRuntimeBootstrap())->bootApplication();

        self::assertInstanceOf(Application::class, $application);
        self::assertNotNull($application->find('app:seed:case'));
        self::assertNotNull($application->find('app:schema:migrate'));
        self::assertNotNull($application->find('start'));
        self::assertNotNull($application->find('app:sanity-check'));
        self::assertNotNull($application->find('app:config:validate'));
        self::assertNotNull($application->find('system:check:mysql'));
        self::assertNotNull($application->find('system:check:kafka'));
        self::assertNotNull($application->find('system:check:mail'));
        self::assertNotNull($application->find('system:check:all'));
        self::assertNotNull($application->find('withdraw:worker:process'));
        self::assertNotNull($application->find('withdraw:worker:notify'));
    }
}
