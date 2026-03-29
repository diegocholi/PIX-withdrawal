<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Tecnofit\PixWithdrawal\Adapters\Cli\Bootstrap\CliRuntimeBootstrap;

final class CliRuntimeBootstrapTest extends TestCase
{
    public function testCliRuntimeBootstrapBuildsConsoleApplicationThroughAdapterBoundary(): void
    {
        $application = (new CliRuntimeBootstrap())->bootApplication();

        self::assertInstanceOf(Application::class, $application);
        self::assertNotNull($application->find('app:seed:case'));
        self::assertNotNull($application->find('app:schema:migrate'));
        self::assertNotNull($application->find('app:sanity-check'));
        self::assertNotNull($application->find('app:config:validate'));
        self::assertNotNull($application->find('system:check:mysql'));
        self::assertNotNull($application->find('system:check:kafka'));
        self::assertNotNull($application->find('system:check:mail'));
        self::assertNotNull($application->find('system:check:all'));
        self::assertNotNull($application->find('withdraw:worker:process'));
        self::assertNotNull($application->find('withdraw:worker:notify'));
        self::assertNotNull($application->find('start'));
        self::assertTrue($application->getDefinition()->hasOption('correlation-id'));
    }
}
