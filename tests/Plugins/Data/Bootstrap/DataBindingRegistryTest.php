<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Bootstrap;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AccountScopedWithdrawQuery;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AtomicAccountDebit;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DueScheduledWithdrawQuery;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\ProcessableWithdrawQuery;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\ScheduledWithdrawQueuePromotion;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\TransactionManager;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\WithdrawStatusViewQuery;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountTransactionRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawPixRepository;
use Tecnofit\PixWithdrawal\Plugins\Data\Bootstrap\DataBindingRegistry;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfigProvider;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfigValidator;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\AccountScopedWithdrawQuery as DataAccountScopedWithdrawQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\DueScheduledWithdrawQuery as DataDueScheduledWithdrawQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\ProcessableWithdrawQuery as DataProcessableWithdrawQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\WithdrawStatusViewQuery as DataWithdrawStatusViewQuery;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;
use Tecnofit\PixWithdrawal\Plugins\Data\Repositories\MySqlAccountRepository;
use Tecnofit\PixWithdrawal\Plugins\Data\Repositories\MySqlAccountTransactionRepository;
use Tecnofit\PixWithdrawal\Plugins\Data\Repositories\MySqlAccountWithdrawRepository;
use Tecnofit\PixWithdrawal\Plugins\Data\Repositories\MySqlAccountWithdrawPixRepository;
use Tecnofit\PixWithdrawal\Plugins\Data\Transactions\MySqlAtomicAccountDebit;
use Tecnofit\PixWithdrawal\Plugins\Data\Transactions\MySqlTransactionManager;
use Tecnofit\PixWithdrawal\Plugins\Data\Transactions\ScheduledWithdrawQueuePromotion as DataScheduledWithdrawQueuePromotion;

final class DataBindingRegistryTest extends TestCase
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

    public function testDefinitionsExposeDataPluginBindings(): void
    {
        $definitions = DataBindingRegistry::definitions();

        self::assertArrayHasKey(MySqlConnectionConfig::class, $definitions);
        self::assertArrayHasKey(MySqlConnectionConfigProvider::class, $definitions);
        self::assertArrayHasKey(MySqlConnectionConfigValidator::class, $definitions);
        self::assertArrayHasKey(AccountScopedWithdrawQuery::class, $definitions);
        self::assertArrayHasKey(AtomicAccountDebit::class, $definitions);
        self::assertArrayHasKey(AccountRepository::class, $definitions);
        self::assertArrayHasKey(AccountTransactionRepository::class, $definitions);
        self::assertArrayHasKey(DueScheduledWithdrawQuery::class, $definitions);
        self::assertArrayHasKey(ProcessableWithdrawQuery::class, $definitions);
        self::assertArrayHasKey(ScheduledWithdrawQueuePromotion::class, $definitions);
        self::assertArrayHasKey(TransactionManager::class, $definitions);
        self::assertArrayHasKey(WithdrawRepository::class, $definitions);
        self::assertArrayHasKey(WithdrawPixRepository::class, $definitions);
        self::assertArrayHasKey(WithdrawStatusViewQuery::class, $definitions);
        self::assertIsCallable($definitions[MySqlConnectionConfig::class]);
    }

    public function testContainerResolvesConcreteDataPluginConfiguration(): void
    {
        $container = (new HyperfContainerFactory())->create();

        self::assertInstanceOf(MySqlConnectionConfig::class, $container->get(MySqlConnectionConfig::class));
        self::assertInstanceOf(MySqlConnectionConfigProvider::class, $container->get(MySqlConnectionConfigProvider::class));
        self::assertInstanceOf(MySqlConnectionConfigValidator::class, $container->get(MySqlConnectionConfigValidator::class));
        self::assertInstanceOf(DataAccountScopedWithdrawQuery::class, $container->get(AccountScopedWithdrawQuery::class));
        self::assertInstanceOf(MySqlAtomicAccountDebit::class, $container->get(AtomicAccountDebit::class));
        self::assertInstanceOf(MySqlAccountRepository::class, $container->get(AccountRepository::class));
        self::assertInstanceOf(MySqlAccountTransactionRepository::class, $container->get(AccountTransactionRepository::class));
        self::assertInstanceOf(DataDueScheduledWithdrawQuery::class, $container->get(DueScheduledWithdrawQuery::class));
        self::assertInstanceOf(DataProcessableWithdrawQuery::class, $container->get(ProcessableWithdrawQuery::class));
        self::assertInstanceOf(DataScheduledWithdrawQueuePromotion::class, $container->get(ScheduledWithdrawQueuePromotion::class));
        self::assertInstanceOf(MySqlTransactionManager::class, $container->get(TransactionManager::class));
        self::assertInstanceOf(MySqlAccountWithdrawRepository::class, $container->get(WithdrawRepository::class));
        self::assertInstanceOf(MySqlAccountWithdrawPixRepository::class, $container->get(WithdrawPixRepository::class));
        self::assertInstanceOf(DataWithdrawStatusViewQuery::class, $container->get(WithdrawStatusViewQuery::class));
    }
}
