<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use Hyperf\Contract\ApplicationInterface;
use Hyperf\DbConnection\Db;
use Symfony\Component\Console\Tester\CommandTester;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

final class AppSchemaMigrateCommandTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testSchemaMigrateCommandAppliesCanonicalSchemaPlan(): void
    {
        $container = (new HyperfContainerFactory())->create();
        $application = $container->get(ApplicationInterface::class);
        $command = $application->find('app:schema:migrate');
        $tester = new CommandTester($command);
        $schema = Db::connection()->getSchemaBuilder();

        Db::statement('SET FOREIGN_KEY_CHECKS=0');
        $schema->dropIfExists('account_transaction');
        $schema->dropIfExists('account_withdraw_pix');
        $schema->dropIfExists('account_withdraw');
        $schema->dropIfExists('account');
        Db::statement('SET FOREIGN_KEY_CHECKS=1');

        $exitCode = $tester->execute([]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $exitCode);
        self::assertSame('app:schema:migrate', $payload['command']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('current', $payload['schema']);
        self::assertTrue($schema->hasTable('account'));
        self::assertTrue($schema->hasTable('account_withdraw'));
        self::assertTrue($schema->hasTable('account_withdraw_pix'));
        self::assertTrue($schema->hasTable('account_transaction'));
    }
}
