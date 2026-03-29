<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Transactions;

use DateTimeImmutable;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InsufficientAccountBalance;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AtomicAccountDebit;
use Tecnofit\PixWithdrawal\Core\Shared\Result;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;

final readonly class MySqlAtomicAccountDebit implements AtomicAccountDebit
{
    public function __construct(private MySqlConnectionConfig $connectionConfig)
    {
    }

    public function execute(Account $account, Money $amount, DateTimeImmutable $processedAt): Result
    {
        $affectedRows = $this->query()
            ->where('id', $account->id())
            ->where('balance', '>=', $amount->toDecimal())
            ->update([
                'balance' => Db::raw(sprintf('balance - %s', $amount->toDecimal())),
                'updated_at' => $processedAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            ]);

        if ($affectedRows !== 1) {
            return Result::failure(
                InsufficientAccountBalance::forDebit($account->id(), $account->balance(), $amount)
            );
        }

        return Result::success(
            Account::reconstitute(
                id: $account->id(),
                name: $account->name(),
                balance: $account->balance()->subtract($amount),
                createdAt: $account->createdAt(),
                updatedAt: $processedAt,
            )
        );
    }

    private function query(): \Hyperf\Database\Query\Builder
    {
        return Db::connection($this->connectionConfig->name())->table('account');
    }
}
