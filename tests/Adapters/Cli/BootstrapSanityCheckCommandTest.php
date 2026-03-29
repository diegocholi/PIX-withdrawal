<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use Hyperf\Contract\ApplicationInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

final class BootstrapSanityCheckCommandTest extends TestCase
{
    public function testSanityCheckCommandConfirmsCliBootstrapReadiness(): void
    {
        $container = (new HyperfContainerFactory())->create();
        $application = $container->get(ApplicationInterface::class);
        $command = $application->find('app:sanity-check');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertJson($tester->getDisplay());
        self::assertStringContainsString('"name":"pix-withdrawal"', $tester->getDisplay());
        self::assertStringContainsString('"status":"ok"', $tester->getDisplay());
        self::assertStringContainsString('"runtime":"cli"', $tester->getDisplay());
    }
}
