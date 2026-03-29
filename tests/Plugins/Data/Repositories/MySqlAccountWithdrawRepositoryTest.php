<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Repositories;

use DateTimeImmutable;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawRepository;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Plugins\Data\Repositories\MySqlAccountWithdrawRepository;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

final class MySqlAccountWithdrawRepositoryTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testSaveCreatesAndFindByIdReconstitutesPendingWithdraw(): void
    {
        $repository = $this->container->get(WithdrawRepository::class);
        $createdAt = new DateTimeImmutable('2026-03-28 13:00:00+00:00');
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-100',
            accountId: 'acc-100',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('125.45'),
            correlationId: 'corr-100',
            idempotencyKey: 'idem-100',
            createdAt: $createdAt,
        );

        $this->insertAccount('acc-100');
        $repository->save($withdraw);

        $persisted = $repository->findById('wd-100');

        self::assertInstanceOf(MySqlAccountWithdrawRepository::class, $repository);
        self::assertNotNull($persisted);
        self::assertSame('wd-100', $persisted->id());
        self::assertSame('acc-100', $persisted->accountId());
        self::assertSame('PIX', $persisted->method()->value);
        self::assertSame('125.45', $persisted->amount()->toDecimal());
        self::assertSame('PENDING', $persisted->status()->value);
        self::assertFalse($persisted->isScheduled());
        self::assertNull($persisted->scheduledFor());
        self::assertNull($persisted->queuedAt());
        self::assertNull($persisted->processingStartedAt());
        self::assertNull($persisted->processedAt());
        self::assertNull($persisted->errorReason());
        self::assertSame('corr-100', $persisted->correlationId());
        self::assertSame('idem-100', $persisted->idempotencyKey());
        self::assertSame('idem-100', $persisted->duplicateGuardFingerprint());
        self::assertSame('2026-03-28T13:00:00+00:00', $persisted->createdAt()->format(DATE_ATOM));
        self::assertSame('2026-03-28T13:00:00+00:00', $persisted->updatedAt()->format(DATE_ATOM));
    }

    public function testSaveCreatesAndLockByIdReconstitutesScheduledWithdraw(): void
    {
        $repository = $this->container->get(WithdrawRepository::class);
        $createdAt = new DateTimeImmutable('2026-03-28 13:00:00+00:00');
        $withdraw = AccountWithdraw::createScheduled(
            id: 'wd-200',
            accountId: 'acc-200',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('99.90'),
            scheduledFor: ScheduleAt::fromDateTime(
                new DateTimeImmutable('2026-03-29T09:15:00+00:00'),
                $this->clockAt('2026-03-28T08:00:00+00:00'),
            ),
            correlationId: 'corr-200',
            idempotencyKey: 'idem-200',
            createdAt: $createdAt,
        );

        $this->insertAccount('acc-200');
        $repository->save($withdraw);

        $persisted = $repository->lockById('wd-200');

        self::assertNotNull($persisted);
        self::assertSame('SCHEDULED', $persisted->status()->value);
        self::assertTrue($persisted->isScheduled());
        self::assertSame('2026-03-29T09:15:00+00:00', $persisted->scheduledFor()?->value()->format(DATE_ATOM));
    }

    public function testSaveUpdatesWithdrawStatusAndOperationalMetadata(): void
    {
        $repository = $this->container->get(WithdrawRepository::class);
        $createdAt = new DateTimeImmutable('2026-03-28 13:00:00+00:00');
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-300',
            accountId: 'acc-300',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('50.00'),
            correlationId: 'corr-300',
            idempotencyKey: 'idem-300',
            createdAt: $createdAt,
        );

        $this->insertAccount('acc-300');
        $repository->save($withdraw);

        $loaded = $repository->lockById('wd-300');
        self::assertNotNull($loaded);

        $loaded->markAsQueued(new DateTimeImmutable('2026-03-28 13:05:00+00:00'));
        $repository->save($loaded);

        $loaded = $repository->lockById('wd-300');
        self::assertNotNull($loaded);

        $loaded->markAsProcessing(new DateTimeImmutable('2026-03-28 13:06:00+00:00'));
        $repository->save($loaded);

        $loaded = $repository->lockById('wd-300');
        self::assertNotNull($loaded);

        $loaded->registerRetry(new DateTimeImmutable('2026-03-28 13:07:00+00:00'));
        $loaded->markAsFailedInternal(new DateTimeImmutable('2026-03-28 13:08:00+00:00'), 'gateway_timeout');
        $repository->save($loaded);

        $record = Db::table('account_withdraw')->where('id', 'wd-300')->first();
        $persisted = $repository->findById('wd-300');

        self::assertSame('FAILED_INTERNAL', $record->status ?? null);
        self::assertSame('2026-03-28 13:05:00.000000', $record->queued_at ?? null);
        self::assertSame('2026-03-28 13:06:00.000000', $record->processing_started_at ?? null);
        self::assertSame('2026-03-28 13:08:00.000000', $record->processed_at ?? null);
        self::assertSame('gateway_timeout', $record->error_reason ?? null);
        self::assertSame(1, $record->retry_count ?? null);
        self::assertSame('2026-03-28 13:07:00.000000', $record->last_retry_at ?? null);
        self::assertSame('2026-03-28 13:08:00.000000', $record->updated_at ?? null);

        self::assertNotNull($persisted);
        self::assertSame('FAILED_INTERNAL', $persisted->status()->value);
        self::assertSame('gateway_timeout', $persisted->errorReason());
        self::assertSame(1, $persisted->retryCount());
        self::assertSame('2026-03-28T13:05:00+00:00', $persisted->queuedAt()?->format(DATE_ATOM));
        self::assertSame('2026-03-28T13:06:00+00:00', $persisted->processingStartedAt()?->format(DATE_ATOM));
        self::assertSame('2026-03-28T13:08:00+00:00', $persisted->processedAt()?->format(DATE_ATOM));
        self::assertSame('2026-03-28T13:07:00+00:00', $persisted->lastRetryAt()?->format(DATE_ATOM));
    }

    public function testFindByIdReturnsNullWhenWithdrawDoesNotExist(): void
    {
        $repository = $this->container->get(WithdrawRepository::class);

        self::assertNull($repository->findById('missing-withdraw'));
    }

    public function testFindByIdempotencyKeyReturnsPersistedWithdraw(): void
    {
        $repository = $this->container->get(WithdrawRepository::class);
        $queuedAt = new DateTimeImmutable('2026-03-28 13:00:00+00:00');
        $withdraw = AccountWithdraw::createQueued(
            id: 'wd-idem-100',
            accountId: 'acc-idem-100',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('40.00'),
            correlationId: 'corr-idem-100',
            idempotencyKey: 'idem-key-100',
            queuedAt: $queuedAt,
        );

        $this->insertAccount('acc-idem-100');
        $repository->save($withdraw);

        $persisted = $repository->findByIdempotencyKey('idem-key-100');

        self::assertNotNull($persisted);
        self::assertSame('wd-idem-100', $persisted->id());
        self::assertSame('QUEUED', $persisted->status()->value);
    }

    public function testFindMostRecentEquivalentSinceReturnsLatestEquivalentWithdraw(): void
    {
        $repository = $this->container->get(WithdrawRepository::class);
        $this->insertAccount('acc-guard-100');

        $older = AccountWithdraw::createQueued(
            id: 'wd-guard-older',
            accountId: 'acc-guard-100',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('40.00'),
            correlationId: 'corr-guard-older',
            idempotencyKey: 'idem-guard-older',
            queuedAt: new DateTimeImmutable('2026-03-28 13:00:00+00:00'),
            duplicateGuardFingerprint: 'guard-100',
        );
        $newer = AccountWithdraw::createQueued(
            id: 'wd-guard-newer',
            accountId: 'acc-guard-100',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('40.00'),
            correlationId: 'corr-guard-newer',
            idempotencyKey: 'idem-guard-newer',
            queuedAt: new DateTimeImmutable('2026-03-28 13:00:40+00:00'),
            duplicateGuardFingerprint: 'guard-100',
        );

        $repository->save($older);
        $repository->save($newer);
        $this->insertWithdrawPix('wd-guard-older', 'older@example.com');
        $this->insertWithdrawPix('wd-guard-newer', 'older@example.com');

        $persisted = $repository->findMostRecentEquivalentSince(
            'acc-guard-100',
            WithdrawMethod::PIX,
            Money::fromDecimal('40.00'),
            PixKey::email('older@example.com'),
            null,
            new DateTimeImmutable('2026-03-28 13:00:10+00:00'),
        );

        self::assertNotNull($persisted);
        self::assertSame('wd-guard-newer', $persisted->id());
    }

    public function testFindMostRecentEquivalentSinceNormalizesSinceToUtc(): void
    {
        $repository = $this->container->get(WithdrawRepository::class);
        $this->insertAccount('acc-guard-tz');

        $withdraw = AccountWithdraw::createQueued(
            id: 'wd-guard-tz',
            accountId: 'acc-guard-tz',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('150.25'),
            correlationId: 'corr-guard-tz',
            idempotencyKey: 'idem-guard-tz',
            queuedAt: new DateTimeImmutable('2026-03-29T17:17:27.749821-03:00'),
            duplicateGuardFingerprint: 'guard-tz',
        );

        $repository->save($withdraw);
        $this->insertWithdrawPix('wd-guard-tz', 'user@example.com');

        $persisted = $repository->findMostRecentEquivalentSince(
            'acc-guard-tz',
            WithdrawMethod::PIX,
            Money::fromDecimal('150.25'),
            PixKey::email('user@example.com'),
            null,
            new DateTimeImmutable('2026-03-29T17:18:26.599991-03:00'),
        );

        self::assertNull($persisted);
    }

    public function testSavePromotesQueuedWithdrawToProcessingOnlyOnce(): void
    {
        $repository = $this->container->get(WithdrawRepository::class);
        $this->insertAccount('acc-310');

        $queuedWithdraw = AccountWithdraw::createPending(
            id: 'wd-310',
            accountId: 'acc-310',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('80.00'),
            correlationId: 'corr-310',
            idempotencyKey: 'idem-310',
            createdAt: new DateTimeImmutable('2026-03-28 13:00:00+00:00'),
        );
        $queuedWithdraw->markAsQueued(new DateTimeImmutable('2026-03-28 13:01:00+00:00'));
        $repository->save($queuedWithdraw);

        $firstWorker = $this->processingWithdrawSnapshot(
            id: 'wd-310',
            accountId: 'acc-310',
            createdAt: '2026-03-28 13:00:00+00:00',
            queuedAt: '2026-03-28 13:01:00+00:00',
            processingStartedAt: '2026-03-28 13:02:00+00:00',
            updatedAt: '2026-03-28 13:02:00+00:00',
        );
        $secondWorker = $this->processingWithdrawSnapshot(
            id: 'wd-310',
            accountId: 'acc-310',
            createdAt: '2026-03-28 13:00:00+00:00',
            queuedAt: '2026-03-28 13:01:00+00:00',
            processingStartedAt: '2026-03-28 13:03:00+00:00',
            updatedAt: '2026-03-28 13:03:00+00:00',
        );

        $repository->save($firstWorker);
        $repository->save($secondWorker);

        $record = Db::table('account_withdraw')->where('id', 'wd-310')->first();

        self::assertSame('PROCESSING', $record->status ?? null);
        self::assertSame('2026-03-28 13:02:00.000000', $record->processing_started_at ?? null);
        self::assertSame('2026-03-28 13:02:00.000000', $record->updated_at ?? null);
    }

    public function testSaveDoesNotReprocessWithdrawAlreadyPersistedAsTerminal(): void
    {
        $repository = $this->container->get(WithdrawRepository::class);
        $this->insertAccount('acc-320');

        $queuedWithdraw = AccountWithdraw::createPending(
            id: 'wd-320',
            accountId: 'acc-320',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('90.00'),
            correlationId: 'corr-320',
            idempotencyKey: 'idem-320',
            createdAt: new DateTimeImmutable('2026-03-28 13:10:00+00:00'),
        );
        $queuedWithdraw->markAsQueued(new DateTimeImmutable('2026-03-28 13:11:00+00:00'));
        $repository->save($queuedWithdraw);

        $processingWithdraw = $this->processingWithdrawSnapshot(
            id: 'wd-320',
            accountId: 'acc-320',
            createdAt: '2026-03-28 13:10:00+00:00',
            queuedAt: '2026-03-28 13:11:00+00:00',
            processingStartedAt: '2026-03-28 13:12:00+00:00',
            updatedAt: '2026-03-28 13:12:00+00:00',
        );
        $repository->save($processingWithdraw);

        $doneWithdraw = $this->doneWithdrawSnapshot(
            id: 'wd-320',
            accountId: 'acc-320',
            createdAt: '2026-03-28 13:10:00+00:00',
            queuedAt: '2026-03-28 13:11:00+00:00',
            processingStartedAt: '2026-03-28 13:12:00+00:00',
            processedAt: '2026-03-28 13:13:00+00:00',
            updatedAt: '2026-03-28 13:13:00+00:00',
        );
        $repository->save($doneWithdraw);

        $staleProcessingAttempt = $this->processingWithdrawSnapshot(
            id: 'wd-320',
            accountId: 'acc-320',
            createdAt: '2026-03-28 13:10:00+00:00',
            queuedAt: '2026-03-28 13:11:00+00:00',
            processingStartedAt: '2026-03-28 13:14:00+00:00',
            updatedAt: '2026-03-28 13:14:00+00:00',
        );
        $repository->save($staleProcessingAttempt);

        $record = Db::table('account_withdraw')->where('id', 'wd-320')->first();

        self::assertSame('DONE', $record->status ?? null);
        self::assertSame('2026-03-28 13:13:00.000000', $record->processed_at ?? null);
        self::assertNull($record->error_reason ?? null);
    }

    public function testSaveDoesNotOverwriteTerminalStateWithCompetingFinalization(): void
    {
        $repository = $this->container->get(WithdrawRepository::class);
        $this->insertAccount('acc-330');

        $queuedWithdraw = AccountWithdraw::createPending(
            id: 'wd-330',
            accountId: 'acc-330',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('110.00'),
            correlationId: 'corr-330',
            idempotencyKey: 'idem-330',
            createdAt: new DateTimeImmutable('2026-03-28 13:20:00+00:00'),
        );
        $queuedWithdraw->markAsQueued(new DateTimeImmutable('2026-03-28 13:21:00+00:00'));
        $repository->save($queuedWithdraw);

        $processingWithdraw = $this->processingWithdrawSnapshot(
            id: 'wd-330',
            accountId: 'acc-330',
            createdAt: '2026-03-28 13:20:00+00:00',
            queuedAt: '2026-03-28 13:21:00+00:00',
            processingStartedAt: '2026-03-28 13:22:00+00:00',
            updatedAt: '2026-03-28 13:22:00+00:00',
        );
        $repository->save($processingWithdraw);

        $doneWithdraw = $this->doneWithdrawSnapshot(
            id: 'wd-330',
            accountId: 'acc-330',
            createdAt: '2026-03-28 13:20:00+00:00',
            queuedAt: '2026-03-28 13:21:00+00:00',
            processingStartedAt: '2026-03-28 13:22:00+00:00',
            processedAt: '2026-03-28 13:23:00+00:00',
            updatedAt: '2026-03-28 13:23:00+00:00',
        );
        $failedWithdraw = $this->failedInternalWithdrawSnapshot(
            id: 'wd-330',
            accountId: 'acc-330',
            createdAt: '2026-03-28 13:20:00+00:00',
            queuedAt: '2026-03-28 13:21:00+00:00',
            processingStartedAt: '2026-03-28 13:22:00+00:00',
            processedAt: '2026-03-28 13:24:00+00:00',
            updatedAt: '2026-03-28 13:24:00+00:00',
            errorReason: 'gateway_timeout',
        );

        $repository->save($doneWithdraw);
        $repository->save($failedWithdraw);

        $record = Db::table('account_withdraw')->where('id', 'wd-330')->first();
        $persisted = $repository->findById('wd-330');

        self::assertSame('DONE', $record->status ?? null);
        self::assertSame('2026-03-28 13:23:00.000000', $record->processed_at ?? null);
        self::assertNull($record->error_reason ?? null);
        self::assertNotNull($persisted);
        self::assertTrue($persisted->isDone());
        self::assertNull($persisted->errorReason());
    }

    private function insertAccount(string $accountId): void
    {
        Db::table('account')->insert([
            'id' => $accountId,
            'name' => 'Repository Test Account',
            'balance' => '1000.00',
            'created_at' => '2026-03-28 12:00:00.000000',
            'updated_at' => '2026-03-28 12:00:00.000000',
        ]);
    }

    private function insertWithdrawPix(string $withdrawId, string $pixKey): void
    {
        Db::table('account_withdraw_pix')->insert([
            'account_withdraw_id' => $withdrawId,
            'type' => 'EMAIL',
            'key' => $pixKey,
            'created_at' => '2026-03-28 12:00:00.000000',
            'updated_at' => '2026-03-28 12:00:00.000000',
        ]);
    }

    private function clockAt(string $dateTime): Clock
    {
        return new class ($dateTime) implements Clock {
            public function __construct(private string $dateTime)
            {
            }

            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable($this->dateTime);
            }
        };
    }

    private function processingWithdrawSnapshot(
        string $id,
        string $accountId,
        string $createdAt,
        string $queuedAt,
        string $processingStartedAt,
        string $updatedAt,
    ): AccountWithdraw {
        return AccountWithdraw::reconstitute(
            id: $id,
            accountId: $accountId,
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('80.00'),
            status: WithdrawStatus::PROCESSING,
            correlationId: sprintf('corr-%s', $id),
            idempotencyKey: sprintf('idem-%s', $id),
            retryCount: 0,
            createdAt: new DateTimeImmutable($createdAt),
            updatedAt: new DateTimeImmutable($updatedAt),
            queuedAt: new DateTimeImmutable($queuedAt),
            processingStartedAt: new DateTimeImmutable($processingStartedAt),
        );
    }

    private function doneWithdrawSnapshot(
        string $id,
        string $accountId,
        string $createdAt,
        string $queuedAt,
        string $processingStartedAt,
        string $processedAt,
        string $updatedAt,
    ): AccountWithdraw {
        return AccountWithdraw::reconstitute(
            id: $id,
            accountId: $accountId,
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('80.00'),
            status: WithdrawStatus::DONE,
            correlationId: sprintf('corr-%s', $id),
            idempotencyKey: sprintf('idem-%s', $id),
            retryCount: 0,
            createdAt: new DateTimeImmutable($createdAt),
            updatedAt: new DateTimeImmutable($updatedAt),
            queuedAt: new DateTimeImmutable($queuedAt),
            processingStartedAt: new DateTimeImmutable($processingStartedAt),
            processedAt: new DateTimeImmutable($processedAt),
        );
    }

    private function failedInternalWithdrawSnapshot(
        string $id,
        string $accountId,
        string $createdAt,
        string $queuedAt,
        string $processingStartedAt,
        string $processedAt,
        string $updatedAt,
        string $errorReason,
    ): AccountWithdraw {
        return AccountWithdraw::reconstitute(
            id: $id,
            accountId: $accountId,
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('80.00'),
            status: WithdrawStatus::FAILED_INTERNAL,
            correlationId: sprintf('corr-%s', $id),
            idempotencyKey: sprintf('idem-%s', $id),
            retryCount: 0,
            createdAt: new DateTimeImmutable($createdAt),
            updatedAt: new DateTimeImmutable($updatedAt),
            queuedAt: new DateTimeImmutable($queuedAt),
            processingStartedAt: new DateTimeImmutable($processingStartedAt),
            processedAt: new DateTimeImmutable($processedAt),
            errorReason: $errorReason,
        );
    }
}
