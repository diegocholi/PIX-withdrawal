<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Bootstrap;

use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\TransactionManager;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AtomicAccountDebit;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AccountScopedWithdrawQuery;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DueScheduledWithdrawQuery;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\ProcessableWithdrawQuery;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\ScheduledWithdrawQueuePromotion;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\WithdrawNotificationRecipientQuery;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountTransactionRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawPixRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\WithdrawStatusViewQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfigProvider;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfigValidator;
use Tecnofit\PixWithdrawal\Plugins\Data\Repositories\MySqlAccountRepository;
use Tecnofit\PixWithdrawal\Plugins\Data\Repositories\MySqlAccountTransactionRepository;
use Tecnofit\PixWithdrawal\Plugins\Data\Repositories\MySqlAccountWithdrawRepository;
use Tecnofit\PixWithdrawal\Plugins\Data\Repositories\MySqlAccountWithdrawPixRepository;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\AccountScopedWithdrawQuery as DataAccountScopedWithdrawQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\DueScheduledWithdrawQuery as DataDueScheduledWithdrawQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\ProcessableWithdrawQuery as DataProcessableWithdrawQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\WithdrawNotificationRecipientQuery as DataWithdrawNotificationRecipientQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\WithdrawStatusViewQuery as DataWithdrawStatusViewQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Transactions\ScheduledWithdrawQueuePromotion as DataScheduledWithdrawQueuePromotion;
use Tecnofit\PixWithdrawal\Plugins\Data\Transactions\MySqlAtomicAccountDebit;
use Tecnofit\PixWithdrawal\Plugins\Data\Transactions\MySqlTransactionManager;

final class DataBindingRegistry
{
    /**
     * @return array<class-string|string, class-string|\Closure>
     */
    public static function definitions(): array
    {
        return [
            MySqlConnectionConfig::class => static fn ($container) => $container->get(MySqlConnectionConfigProvider::class)->defaultConnection(),
            MySqlConnectionConfigProvider::class => MySqlConnectionConfigProvider::class,
            MySqlConnectionConfigValidator::class => MySqlConnectionConfigValidator::class,
            AccountScopedWithdrawQuery::class => DataAccountScopedWithdrawQuery::class,
            AtomicAccountDebit::class => MySqlAtomicAccountDebit::class,
            AccountRepository::class => MySqlAccountRepository::class,
            AccountTransactionRepository::class => MySqlAccountTransactionRepository::class,
            DueScheduledWithdrawQuery::class => DataDueScheduledWithdrawQuery::class,
            ProcessableWithdrawQuery::class => DataProcessableWithdrawQuery::class,
            ScheduledWithdrawQueuePromotion::class => DataScheduledWithdrawQueuePromotion::class,
            TransactionManager::class => MySqlTransactionManager::class,
            WithdrawNotificationRecipientQuery::class => DataWithdrawNotificationRecipientQuery::class,
            WithdrawRepository::class => MySqlAccountWithdrawRepository::class,
            WithdrawPixRepository::class => MySqlAccountWithdrawPixRepository::class,
            WithdrawStatusViewQuery::class => DataWithdrawStatusViewQuery::class,
        ];
    }
}
