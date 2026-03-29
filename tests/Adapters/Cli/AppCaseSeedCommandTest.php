<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use Hyperf\Contract\ApplicationInterface;
use Hyperf\DbConnection\Db;
use Symfony\Component\Console\Tester\CommandTester;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

final class AppCaseSeedCommandTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testCaseSeedCommandAppliesCaseDataOnceAndSkipsOnSecondRun(): void
    {
        $container = (new HyperfContainerFactory())->create();
        $application = $container->get(ApplicationInterface::class);
        $command = $application->find('app:seed:case');
        $firstTester = new CommandTester($command);
        $secondTester = new CommandTester($command);

        $firstExitCode = $firstTester->execute([]);
        $firstPayload = json_decode($firstTester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        $secondExitCode = $secondTester->execute([]);
        $secondPayload = json_decode($secondTester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $firstExitCode);
        self::assertSame(0, $secondExitCode);
        self::assertSame('app:seed:case', $firstPayload['command']);
        self::assertSame('pix-withdrawal-case', $firstPayload['seed']);
        self::assertSame('applied', $firstPayload['status']);
        self::assertSame('skipped', $secondPayload['status']);
        self::assertSame(1, Db::table('seed_execution')->count());
    }
}
