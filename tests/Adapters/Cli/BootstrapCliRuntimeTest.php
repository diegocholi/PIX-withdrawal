<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use Hyperf\Contract\ApplicationInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

final class BootstrapCliRuntimeTest extends TestCase
{
    public function testCliBootstrapBuildsSymfonyConsoleApplication(): void
    {
        $container = (new HyperfContainerFactory())->create();
        $application = $container->get(ApplicationInterface::class);

        self::assertInstanceOf(Application::class, $application);
        self::assertNotNull($application->find('help'));
        self::assertNotNull($application->find('list'));
        self::assertNotNull($application->find('start'));
    }
}
