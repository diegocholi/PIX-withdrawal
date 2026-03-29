<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Repositories;

use DateTimeImmutable;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountRepository;
use Tecnofit\PixWithdrawal\Plugins\Data\Repositories\MySqlAccountRepository;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

final class MySqlAccountRepositoryTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testSaveCreatesAndFindByIdReconstitutesAccount(): void
    {
        $repository = $this->container->get(AccountRepository::class);
        $createdAt = new DateTimeImmutable('2026-03-28 13:00:00+00:00');
        $account = Account::create(
            'acc-100',
            'Primary Wallet',
            Money::fromDecimal('1250.45'),
            $createdAt,
        );

        $repository->save($account);

        $persisted = $repository->findById('acc-100');

        self::assertInstanceOf(MySqlAccountRepository::class, $repository);
        self::assertNotNull($persisted);
        self::assertSame('acc-100', $persisted->id());
        self::assertSame('Primary Wallet', $persisted->name());
        self::assertSame('1250.45', $persisted->balance()->toDecimal());
        self::assertSame('2026-03-28T13:00:00+00:00', $persisted->createdAt()->format(DATE_ATOM));
        self::assertSame('2026-03-28T13:00:00+00:00', $persisted->updatedAt()->format(DATE_ATOM));
    }

    public function testSaveUpdatesExistingAccountState(): void
    {
        $repository = $this->container->get(AccountRepository::class);
        $createdAt = new DateTimeImmutable('2026-03-28 13:00:00+00:00');
        $account = Account::create(
            'acc-200',
            'Operations',
            Money::fromDecimal('300.00'),
            $createdAt,
        );

        $repository->save($account);

        $loaded = $repository->lockById('acc-200');
        self::assertNotNull($loaded);

        $loaded->debit(Money::fromDecimal('25.10'), new DateTimeImmutable('2026-03-28 13:05:00+00:00'));
        $repository->save($loaded);

        $record = Db::table('account')->where('id', 'acc-200')->first();

        self::assertSame('274.90', $record->balance ?? null);
        self::assertSame('2026-03-28 13:05:00.000000', $record->updated_at ?? null);
    }

    public function testFindByIdReturnsNullWhenAccountDoesNotExist(): void
    {
        $repository = $this->container->get(AccountRepository::class);

        self::assertNull($repository->findById('missing-account'));
    }
}
