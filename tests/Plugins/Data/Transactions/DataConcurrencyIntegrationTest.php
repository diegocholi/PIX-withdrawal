<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Transactions;

use DateTimeImmutable;
use Hyperf\DbConnection\Db;
use RuntimeException;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountTransaction;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionDirection;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionReferenceType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AtomicAccountDebit;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\TransactionManager;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountTransactionRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawRepository;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;
use Tecnofit\PixWithdrawal\Plugins\Data\Transactions\MySqlAtomicAccountDebit;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

final class DataConcurrencyIntegrationTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testAtomicDebitKeepsBalanceNonNegativeUnderConcurrentAttempts(): void
    {
        $this->skipWhenForkIsUnavailable();
        $this->insertAccount('acc-concurrency-100', '100.00');

        $accountSnapshot = Account::reconstitute(
            id: 'acc-concurrency-100',
            name: 'Concurrency Account',
            balance: Money::fromDecimal('100.00'),
            createdAt: new DateTimeImmutable('2026-03-29T12:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-03-29T12:00:00+00:00'),
        );

        $results = $this->runConcurrentWorkers([
            static function (float $startAt) use ($accountSnapshot): array {
                self::waitFor($startAt);

                $container = (new HyperfContainerFactory())->create();
                $atomicDebit = new MySqlAtomicAccountDebit($container->get(MySqlConnectionConfig::class));
                $result = $atomicDebit->execute(
                    $accountSnapshot,
                    Money::fromDecimal('80.00'),
                    new DateTimeImmutable('2026-03-29T12:05:00+00:00'),
                );

                return ['success' => $result->isSuccess()];
            },
            static function (float $startAt) use ($accountSnapshot): array {
                self::waitFor($startAt);

                $container = (new HyperfContainerFactory())->create();
                $atomicDebit = new MySqlAtomicAccountDebit($container->get(MySqlConnectionConfig::class));
                $result = $atomicDebit->execute(
                    $accountSnapshot,
                    Money::fromDecimal('80.00'),
                    new DateTimeImmutable('2026-03-29T12:05:00+00:00'),
                );

                return ['success' => $result->isSuccess()];
            },
        ]);

        self::assertSame([true, false], $this->sortedBooleanResults($results, 'success'));
        self::assertSame('20.00', (string) Db::table('account')->where('id', 'acc-concurrency-100')->value('balance'));
    }

    public function testConcurrentWorkersDoNotCaptureTheSameWithdrawTwice(): void
    {
        $this->skipWhenForkIsUnavailable();
        $this->insertAccount('acc-concurrency-200', '500.00');
        $this->persistQueuedWithdraw('wd-concurrency-200', 'acc-concurrency-200');

        $results = $this->runConcurrentWorkers([
            static function (float $startAt): array {
                self::waitFor($startAt);

                $container = (new HyperfContainerFactory())->create();
                $repository = $container->get(WithdrawRepository::class);
                $processingStartedAt = new DateTimeImmutable('2026-03-29T12:17:00+00:00');

                $repository->save(self::processingWithdrawSnapshot($processingStartedAt));

                return [
                    'captured' => self::persistedProcessingTimestamp('wd-concurrency-200') === '2026-03-29 12:17:00.000000',
                ];
            },
            static function (float $startAt): array {
                self::waitFor($startAt);

                $container = (new HyperfContainerFactory())->create();
                $repository = $container->get(WithdrawRepository::class);
                $processingStartedAt = new DateTimeImmutable('2026-03-29T12:17:01+00:00');

                $repository->save(self::processingWithdrawSnapshot($processingStartedAt));

                return [
                    'captured' => self::persistedProcessingTimestamp('wd-concurrency-200') === '2026-03-29 12:17:01.000000',
                ];
            },
        ]);

        $record = Db::table('account_withdraw')->where('id', 'wd-concurrency-200')->first();

        self::assertSame([true, false], $this->sortedBooleanResults($results, 'captured'));
        self::assertSame(WithdrawStatus::PROCESSING->value, $record->status ?? null);
        self::assertContains(
            $record->processing_started_at ?? null,
            ['2026-03-29 12:17:00.000000', '2026-03-29 12:17:01.000000'],
        );
    }

    public function testWithdrawProcessingPersistsConsistentlyInsideSingleTransaction(): void
    {
        $transactionManager = $this->container->get(TransactionManager::class);
        $atomicDebit = $this->container->get(AtomicAccountDebit::class);
        $accountRepository = $this->container->get(AccountRepository::class);
        $transactionRepository = $this->container->get(AccountTransactionRepository::class);
        $withdrawRepository = $this->container->get(WithdrawRepository::class);

        $this->insertAccount('acc-transaction-100', '120.00');
        $this->persistQueuedWithdraw('wd-transaction-100', 'acc-transaction-100');

        $transactionManager->run(function () use (
            $atomicDebit,
            $accountRepository,
            $transactionRepository,
            $withdrawRepository
        ): void {
            $lockedAccount = $accountRepository->lockById('acc-transaction-100');
            $lockedWithdraw = $withdrawRepository->lockById('wd-transaction-100');

            self::assertNotNull($lockedAccount);
            self::assertNotNull($lockedWithdraw);

            $processedAt = new DateTimeImmutable('2026-03-29T12:20:00+00:00');
            $debitResult = $atomicDebit->execute($lockedAccount, Money::fromDecimal('45.00'), $processedAt);

            self::assertTrue($debitResult->isSuccess());

            $transactionRepository->save(
                AccountTransaction::create(
                    accountId: $lockedAccount->id(),
                    referenceType: AccountTransactionReferenceType::WITHDRAW,
                    referenceId: $lockedWithdraw->id(),
                    direction: AccountTransactionDirection::DEBIT,
                    amount: Money::fromDecimal('45.00'),
                    balanceBefore: $lockedAccount->balance(),
                    balanceAfter: Money::fromDecimal('75.00'),
                    createdAt: $processedAt,
                ),
            );

            $lockedWithdraw->markAsProcessing(new DateTimeImmutable('2026-03-29T12:19:00+00:00'));
            $withdrawRepository->save($lockedWithdraw);

            $lockedWithdraw->markAsDone($processedAt);
            $withdrawRepository->save($lockedWithdraw);
        });

        self::assertSame('75.00', (string) Db::table('account')->where('id', 'acc-transaction-100')->value('balance'));
        self::assertSame(1, Db::table('account_transaction')->where('reference_id', 'wd-transaction-100')->count());
        self::assertSame(
            WithdrawStatus::DONE->value,
            Db::table('account_withdraw')->where('id', 'wd-transaction-100')->value('status'),
        );
    }

    public function testWithdrawProcessingRollsBackDebitTransactionAndStatusTogether(): void
    {
        $transactionManager = $this->container->get(TransactionManager::class);
        $atomicDebit = $this->container->get(AtomicAccountDebit::class);
        $accountRepository = $this->container->get(AccountRepository::class);
        $transactionRepository = $this->container->get(AccountTransactionRepository::class);
        $withdrawRepository = $this->container->get(WithdrawRepository::class);

        $this->insertAccount('acc-transaction-200', '120.00');
        $this->persistQueuedWithdraw('wd-transaction-200', 'acc-transaction-200');

        try {
            $transactionManager->run(function () use (
                $atomicDebit,
                $accountRepository,
                $transactionRepository,
                $withdrawRepository
            ): void {
                $lockedAccount = $accountRepository->lockById('acc-transaction-200');
                $lockedWithdraw = $withdrawRepository->lockById('wd-transaction-200');

                self::assertNotNull($lockedAccount);
                self::assertNotNull($lockedWithdraw);

                $processedAt = new DateTimeImmutable('2026-03-29T12:30:00+00:00');
                $debitResult = $atomicDebit->execute($lockedAccount, Money::fromDecimal('45.00'), $processedAt);

                self::assertTrue($debitResult->isSuccess());

                $transactionRepository->save(
                    AccountTransaction::create(
                        accountId: $lockedAccount->id(),
                        referenceType: AccountTransactionReferenceType::WITHDRAW,
                        referenceId: $lockedWithdraw->id(),
                        direction: AccountTransactionDirection::DEBIT,
                        amount: Money::fromDecimal('45.00'),
                        balanceBefore: $lockedAccount->balance(),
                        balanceAfter: Money::fromDecimal('75.00'),
                        createdAt: $processedAt,
                    ),
                );

                $lockedWithdraw->markAsProcessing(new DateTimeImmutable('2026-03-29T12:29:00+00:00'));
                $withdrawRepository->save($lockedWithdraw);

                throw new RuntimeException('rollback-on-purpose');
            });

            self::fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            self::assertSame('rollback-on-purpose', $exception->getMessage());
        }

        self::assertSame('120.00', (string) Db::table('account')->where('id', 'acc-transaction-200')->value('balance'));
        self::assertSame(0, Db::table('account_transaction')->where('reference_id', 'wd-transaction-200')->count());
        self::assertSame(
            WithdrawStatus::QUEUED->value,
            Db::table('account_withdraw')->where('id', 'wd-transaction-200')->value('status'),
        );
        self::assertNull(
            Db::table('account_withdraw')->where('id', 'wd-transaction-200')->value('processing_started_at'),
        );
    }

    /**
     * @param list<callable(float): array<string, bool>>> $workers
     * @return list<array<string, bool>>
     */
    private function runConcurrentWorkers(array $workers): array
    {
        $startAt = microtime(true) + 0.4;
        $children = [];

        foreach ($workers as $worker) {
            $outputPath = (string) tempnam(sys_get_temp_dir(), 'data-concurrency-');
            $pid = pcntl_fork();

            if ($pid === -1) {
                self::fail('Could not fork worker process for concurrency test.');
            }

            if ($pid === 0) {
                try {
                    file_put_contents($outputPath, json_encode($worker($startAt), JSON_THROW_ON_ERROR));
                    exit(0);
                } catch (\Throwable $throwable) {
                    file_put_contents($outputPath, json_encode(['error' => $throwable->getMessage()], JSON_THROW_ON_ERROR));
                    exit(1);
                }
            }

            $children[] = ['pid' => $pid, 'output' => $outputPath];
        }

        $results = [];

        foreach ($children as $child) {
            $status = 0;
            pcntl_waitpid($child['pid'], $status);

            if (pcntl_wexitstatus($status) !== 0) {
                $payload = json_decode((string) file_get_contents($child['output']), true);
                self::fail('Concurrent worker failed: ' . (string) ($payload['error'] ?? 'unknown error'));
            }

            $results[] = json_decode((string) file_get_contents($child['output']), true, flags: JSON_THROW_ON_ERROR);
            @unlink($child['output']);
        }

        return $results;
    }

    /**
     * @param list<array<string, bool>> $results
     * @return list<bool>
     */
    private function sortedBooleanResults(array $results, string $key): array
    {
        $values = array_map(
            static fn (array $result): bool => (bool) ($result[$key] ?? false),
            $results,
        );

        sort($values);

        return array_reverse($values);
    }

    private function insertAccount(string $accountId, string $balance): void
    {
        Db::table('account')->insert([
            'id' => $accountId,
            'name' => 'Concurrency Account',
            'balance' => $balance,
            'created_at' => '2026-03-29 12:00:00.000000',
            'updated_at' => '2026-03-29 12:00:00.000000',
        ]);
    }

    private function persistQueuedWithdraw(string $withdrawId, string $accountId): void
    {
        $withdraw = AccountWithdraw::createPending(
            id: $withdrawId,
            accountId: $accountId,
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('45.00'),
            correlationId: 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            idempotencyKey: 'withdraw:create:v1:' . $withdrawId,
            createdAt: new DateTimeImmutable('2026-03-29T12:15:00+00:00'),
        );
        $withdraw->markAsQueued(new DateTimeImmutable('2026-03-29T12:16:00+00:00'));

        Db::table('account_withdraw')->insert([
            'id' => $withdraw->id(),
            'account_id' => $withdraw->accountId(),
            'method' => $withdraw->method()->value,
            'amount' => $withdraw->amount()->toDecimal(),
            'scheduled' => 0,
            'scheduled_for' => null,
            'status' => $withdraw->status()->value,
            'error_reason' => null,
            'requested_at' => $withdraw->createdAt()->format('Y-m-d H:i:s.u'),
            'queued_at' => $withdraw->queuedAt()?->format('Y-m-d H:i:s.u'),
            'processing_started_at' => null,
            'processed_at' => null,
            'correlation_id' => $withdraw->correlationId(),
            'idempotency_key' => $withdraw->idempotencyKey(),
            'retry_count' => 0,
            'last_retry_at' => null,
            'created_at' => $withdraw->createdAt()->format('Y-m-d H:i:s.u'),
            'updated_at' => $withdraw->updatedAt()->format('Y-m-d H:i:s.u'),
        ]);
    }

    private static function processingWithdrawSnapshot(DateTimeImmutable $processingStartedAt): AccountWithdraw
    {
        return AccountWithdraw::reconstitute(
            id: 'wd-concurrency-200',
            accountId: 'acc-concurrency-200',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('45.00'),
            status: WithdrawStatus::PROCESSING,
            correlationId: 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            idempotencyKey: 'withdraw:create:v1:wd-concurrency-200',
            retryCount: 0,
            createdAt: new DateTimeImmutable('2026-03-29T12:15:00+00:00'),
            updatedAt: $processingStartedAt,
            scheduledFor: null,
            queuedAt: new DateTimeImmutable('2026-03-29T12:16:00+00:00'),
            processingStartedAt: $processingStartedAt,
            processedAt: null,
            errorReason: null,
            lastRetryAt: null,
        );
    }

    private static function persistedProcessingTimestamp(string $withdrawId): ?string
    {
        $container = (new HyperfContainerFactory())->create();
        $container->get(MySqlConnectionConfig::class);

        $value = Db::table('account_withdraw')->where('id', $withdrawId)->value('processing_started_at');

        return $value !== null ? (string) $value : null;
    }

    private function skipWhenForkIsUnavailable(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl extension is required for concurrency integration tests.');
        }
    }

    private static function waitFor(float $startAt): void
    {
        while (microtime(true) < $startAt) {
            usleep(1_000);
        }
    }
}
