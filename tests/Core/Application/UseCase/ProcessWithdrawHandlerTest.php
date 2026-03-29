<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Application\UseCase;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Command\ProcessWithdrawInput;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotFound;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotProcessable;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\ProcessWithdrawHandler;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountTransaction;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;
use Tecnofit\PixWithdrawal\Core\Domain\Event\WithdrawFailed;
use Tecnofit\PixWithdrawal\Core\Domain\Event\WithdrawProcessed;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;
use Tecnofit\PixWithdrawal\Core\Domain\Service\WithdrawDebitTransactionFactory;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AtomicAccountDebit;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\ProcessableWithdraw;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\ProcessableWithdrawQuery;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\TransactionManager;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountTransactionRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawRepository;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\FailureClassifier;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderSensitiveDataMasker;

final class ProcessWithdrawHandlerTest extends TestCase
{
    public function testHandlerProcessesQueuedWithdrawAndPersistsDebitTransaction(): void
    {
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('25.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T09:00:00-03:00'),
        );
        $withdraw->markAsQueued(new DateTimeImmutable('2026-03-28T09:05:00-03:00'));

        $withdrawRepository = new ProcessInMemoryWithdrawRepository($withdraw);
        $processableWithdrawQuery = new ProcessInMemoryProcessableWithdrawQuery(
            $withdrawRepository,
            AccountWithdrawPix::create('wd-1', WithdrawMethod::PIX, PixKey::from(PixKeyType::EMAIL, 'user@example.com'), new DateTimeImmutable('2026-03-28T09:00:00-03:00'))
        );
        $accountRepository = new ProcessInMemoryAccountRepository(
            Account::create('acc-1', 'Main Account', Money::fromDecimal('100.00'), new DateTimeImmutable('2026-03-28T08:00:00-03:00'))
        );
        $accountTransactionRepository = new ProcessInMemoryAccountTransactionRepository();
        $domainEventDispatcher = new ProcessSpyDomainEventDispatcher();

        $output = $this->handler(
            $withdrawRepository,
            $processableWithdrawQuery,
            $accountRepository,
            $accountTransactionRepository,
            $domainEventDispatcher,
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        )->execute(
            new ProcessWithdrawInput('wd-1', 'corr-1', 1)
        );

        self::assertSame('DONE', $output->status());
        self::assertSame('75.00', $accountRepository->account->balance()->toDecimal());
        self::assertCount(1, $accountTransactionRepository->saved);
        self::assertSame('DONE', $withdrawRepository->withdraw?->status()->value);
        self::assertNull($withdrawRepository->withdraw?->errorReason());
        self::assertCount(1, $domainEventDispatcher->events);
        self::assertInstanceOf(WithdrawProcessed::class, $domainEventDispatcher->events[0]);
        self::assertSame('withdraw.processed', $domainEventDispatcher->events[0]->eventName());
        self::assertSame('u***@example.com', $domainEventDispatcher->events[0]->toArray()['payload']['pix_key_masked']);
    }

    public function testHandlerMarksWithdrawAsFailedWhenBalanceIsInsufficient(): void
    {
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('25.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T09:00:00-03:00'),
        );
        $withdraw->markAsQueued(new DateTimeImmutable('2026-03-28T09:05:00-03:00'));

        $withdrawRepository = new ProcessInMemoryWithdrawRepository($withdraw);
        $processableWithdrawQuery = new ProcessInMemoryProcessableWithdrawQuery(
            $withdrawRepository,
            AccountWithdrawPix::create('wd-1', WithdrawMethod::PIX, PixKey::from(PixKeyType::EMAIL, 'user@example.com'), new DateTimeImmutable('2026-03-28T09:00:00-03:00'))
        );
        $accountRepository = new ProcessInMemoryAccountRepository(
            Account::create('acc-1', 'Main Account', Money::fromDecimal('10.00'), new DateTimeImmutable('2026-03-28T08:00:00-03:00'))
        );
        $accountTransactionRepository = new ProcessInMemoryAccountTransactionRepository();
        $domainEventDispatcher = new ProcessSpyDomainEventDispatcher();

        $output = $this->handler(
            $withdrawRepository,
            $processableWithdrawQuery,
            $accountRepository,
            $accountTransactionRepository,
            $domainEventDispatcher,
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        )->execute(
            new ProcessWithdrawInput('wd-1', 'corr-1', 1)
        );

        self::assertSame('FAILED_INSUFFICIENT_FUNDS', $output->status());
        self::assertSame('10.00', $accountRepository->account->balance()->toDecimal());
        self::assertCount(0, $accountTransactionRepository->saved);
        self::assertSame('insufficient_balance', $withdrawRepository->withdraw?->errorReason());
        self::assertInstanceOf(WithdrawFailed::class, $domainEventDispatcher->events[0]);
        self::assertSame('withdraw.failed', $domainEventDispatcher->events[0]->eventName());
        self::assertSame('insufficient_balance', $domainEventDispatcher->events[0]->toArray()['payload']['error_reason']);
    }

    public function testHandlerRejectsUnknownWithdraw(): void
    {
        $this->expectException(WithdrawNotFound::class);

        $this->handler(
            new ProcessInMemoryWithdrawRepository(null),
            new ProcessInMemoryProcessableWithdrawQuery(new ProcessInMemoryWithdrawRepository(null), null),
            new ProcessInMemoryAccountRepository(
                Account::create('acc-1', 'Main Account', Money::fromDecimal('10.00'), new DateTimeImmutable('2026-03-28T08:00:00-03:00'))
            ),
            new ProcessInMemoryAccountTransactionRepository(),
            new ProcessSpyDomainEventDispatcher(),
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        )->execute(
            new ProcessWithdrawInput('wd-404', 'corr-1', 1)
        );
    }

    public function testHandlerRejectsWithdrawOutsideProcessingFlow(): void
    {
        $this->expectException(WithdrawNotProcessable::class);

        $withdraw = AccountWithdraw::createPending(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('25.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T09:00:00-03:00'),
        );

        $this->handler(
            new ProcessInMemoryWithdrawRepository($withdraw),
            new ProcessInMemoryProcessableWithdrawQuery(new ProcessInMemoryWithdrawRepository($withdraw), null),
            new ProcessInMemoryAccountRepository(
                Account::create('acc-1', 'Main Account', Money::fromDecimal('100.00'), new DateTimeImmutable('2026-03-28T08:00:00-03:00'))
            ),
            new ProcessInMemoryAccountTransactionRepository(),
            new ProcessSpyDomainEventDispatcher(),
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        )->execute(
            new ProcessWithdrawInput('wd-1', 'corr-1', 1)
        );
    }

    public function testHandlerReturnsIdempotentResultForTerminalDoneWithdraw(): void
    {
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('25.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T09:00:00-03:00'),
        );
        $withdraw->markAsQueued(new DateTimeImmutable('2026-03-28T09:05:00-03:00'));
        $withdraw->markAsProcessing(new DateTimeImmutable('2026-03-28T09:06:00-03:00'));
        $withdraw->markAsDone(new DateTimeImmutable('2026-03-28T09:07:00-03:00'));

        $withdrawRepository = new ProcessInMemoryWithdrawRepository($withdraw);
        $accountRepository = new ProcessInMemoryAccountRepository(
            Account::create('acc-1', 'Main Account', Money::fromDecimal('100.00'), new DateTimeImmutable('2026-03-28T08:00:00-03:00'))
        );
        $accountTransactionRepository = new ProcessInMemoryAccountTransactionRepository();
        $domainEventDispatcher = new ProcessSpyDomainEventDispatcher();

        $output = $this->handler(
            $withdrawRepository,
            new ProcessInMemoryProcessableWithdrawQuery($withdrawRepository, null),
            $accountRepository,
            $accountTransactionRepository,
            $domainEventDispatcher,
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        )->execute(
            new ProcessWithdrawInput('wd-1', 'corr-retry', 2)
        );

        self::assertSame('DONE', $output->status());
        self::assertSame('100.00', $accountRepository->account->balance()->toDecimal());
        self::assertCount(0, $accountTransactionRepository->saved);
        self::assertCount(0, $domainEventDispatcher->events);
        self::assertSame('DONE', $withdrawRepository->withdraw?->status()->value);
    }

    public function testHandlerReturnsIdempotentResultForTerminalFailedWithdraw(): void
    {
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-2',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('25.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T09:00:00-03:00'),
        );
        $withdraw->markAsQueued(new DateTimeImmutable('2026-03-28T09:05:00-03:00'));
        $withdraw->markAsProcessing(new DateTimeImmutable('2026-03-28T09:06:00-03:00'));
        $withdraw->markAsFailedInsufficientFunds(new DateTimeImmutable('2026-03-28T09:07:00-03:00'), 'insufficient_balance');

        $withdrawRepository = new ProcessInMemoryWithdrawRepository($withdraw);
        $accountRepository = new ProcessInMemoryAccountRepository(
            Account::create('acc-1', 'Main Account', Money::fromDecimal('100.00'), new DateTimeImmutable('2026-03-28T08:00:00-03:00'))
        );
        $accountTransactionRepository = new ProcessInMemoryAccountTransactionRepository();
        $domainEventDispatcher = new ProcessSpyDomainEventDispatcher();

        $output = $this->handler(
            $withdrawRepository,
            new ProcessInMemoryProcessableWithdrawQuery($withdrawRepository, null),
            $accountRepository,
            $accountTransactionRepository,
            $domainEventDispatcher,
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        )->execute(
            new ProcessWithdrawInput('wd-2', 'corr-retry', 3)
        );

        self::assertSame('FAILED_INSUFFICIENT_FUNDS', $output->status());
        self::assertSame('100.00', $accountRepository->account->balance()->toDecimal());
        self::assertCount(0, $accountTransactionRepository->saved);
        self::assertCount(0, $domainEventDispatcher->events);
        self::assertSame('FAILED_INSUFFICIENT_FUNDS', $withdrawRepository->withdraw?->status()->value);
    }

    public function testHandlerDoesNotAdvanceAlreadyProcessingWithdraw(): void
    {
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-3',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('25.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T09:00:00-03:00'),
        );
        $withdraw->markAsQueued(new DateTimeImmutable('2026-03-28T09:05:00-03:00'));
        $withdraw->markAsProcessing(new DateTimeImmutable('2026-03-28T09:06:00-03:00'));

        $withdrawRepository = new ProcessInMemoryWithdrawRepository($withdraw);
        $accountRepository = new ProcessInMemoryAccountRepository(
            Account::create('acc-1', 'Main Account', Money::fromDecimal('100.00'), new DateTimeImmutable('2026-03-28T08:00:00-03:00'))
        );
        $accountTransactionRepository = new ProcessInMemoryAccountTransactionRepository();
        $domainEventDispatcher = new ProcessSpyDomainEventDispatcher();

        $output = $this->handler(
            $withdrawRepository,
            new ProcessInMemoryProcessableWithdrawQuery($withdrawRepository, null),
            $accountRepository,
            $accountTransactionRepository,
            $domainEventDispatcher,
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        )->execute(
            new ProcessWithdrawInput('wd-3', 'corr-concurrent', 2)
        );

        self::assertSame('PROCESSING', $output->status());
        self::assertSame('100.00', $accountRepository->account->balance()->toDecimal());
        self::assertCount(0, $accountTransactionRepository->saved);
        self::assertCount(0, $domainEventDispatcher->events);
        self::assertSame('PROCESSING', $withdrawRepository->withdraw?->status()->value);
    }

    public function testHandlerMarksWithdrawAsFailedInternalWhenRetryLimitIsExceeded(): void
    {
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-4',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('25.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T09:00:00-03:00'),
        );
        $withdraw->markAsQueued(new DateTimeImmutable('2026-03-28T09:05:00-03:00'));

        $withdrawRepository = new ProcessInMemoryWithdrawRepository($withdraw);
        $domainEventDispatcher = new ProcessSpyDomainEventDispatcher();

        $output = $this->handler(
            $withdrawRepository,
            new ProcessInMemoryProcessableWithdrawQuery($withdrawRepository, null),
            new ProcessInMemoryAccountRepository(
                Account::create('acc-1', 'Main Account', Money::fromDecimal('100.00'), new DateTimeImmutable('2026-03-28T08:00:00-03:00'))
            ),
            new ProcessInMemoryAccountTransactionRepository(),
            $domainEventDispatcher,
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        )->execute(
            new ProcessWithdrawInput('wd-4', 'corr-1', 4)
        );

        self::assertSame('FAILED_INTERNAL', $output->status());
        self::assertSame('retry_limit_exceeded', $withdrawRepository->withdraw?->errorReason());
        self::assertInstanceOf(WithdrawFailed::class, $domainEventDispatcher->events[0]);
    }

    public function testHandlerMarksWithdrawAsFailedInternalForNonRecoverableTechnicalFailure(): void
    {
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-5',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('25.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T09:00:00-03:00'),
        );
        $withdraw->markAsQueued(new DateTimeImmutable('2026-03-28T09:05:00-03:00'));

        $withdrawRepository = new ProcessInMemoryWithdrawRepository($withdraw);
        $domainEventDispatcher = new ProcessSpyDomainEventDispatcher();

        $output = $this->handler(
            $withdrawRepository,
            new ProcessInMemoryProcessableWithdrawQuery($withdrawRepository, null),
            new ProcessInMemoryAccountRepository(
                Account::create('acc-1', 'Main Account', Money::fromDecimal('100.00'), new DateTimeImmutable('2026-03-28T08:00:00-03:00'))
            ),
            new ProcessInMemoryAccountTransactionRepository(),
            $domainEventDispatcher,
            $this->clockAt('2026-03-28T10:00:00-03:00'),
            new ProcessThrowingAtomicAccountDebit(),
        )->execute(
            new ProcessWithdrawInput('wd-5', 'corr-1', 1)
        );

        self::assertSame('FAILED_INTERNAL', $output->status());
        self::assertSame('internal_processing_failure', $withdrawRepository->withdraw?->errorReason());
        self::assertInstanceOf(WithdrawFailed::class, $domainEventDispatcher->events[0]);
    }

    private function handler(
        WithdrawRepository $withdrawRepository,
        ProcessableWithdrawQuery $processableWithdrawQuery,
        AccountRepository $accountRepository,
        AccountTransactionRepository $accountTransactionRepository,
        DomainEventDispatcher $domainEventDispatcher,
        Clock $clock,
        ?AtomicAccountDebit $atomicAccountDebit = null,
    ): ProcessWithdrawHandler {
        return new ProcessWithdrawHandler(
            $withdrawRepository,
            $processableWithdrawQuery,
            $accountRepository,
            $accountTransactionRepository,
            $atomicAccountDebit ?? new ProcessAtomicAccountDebit(),
            new WithdrawDebitTransactionFactory(),
            $domainEventDispatcher,
            new ProcessSpyTransactionManager(),
            $clock,
            new ProcessFailureClassifier(),
            new ProviderSensitiveDataMasker(),
        );
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
}

final class ProcessInMemoryWithdrawRepository implements WithdrawRepository
{
    public function __construct(public ?AccountWithdraw $withdraw)
    {
    }

    public function findById(string $withdrawId): ?AccountWithdraw
    {
        return $this->withdraw !== null && $this->withdraw->id() === $withdrawId ? $this->withdraw : null;
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?AccountWithdraw
    {
        return $this->withdraw !== null && $this->withdraw->idempotencyKey() === $idempotencyKey
            ? $this->withdraw
            : null;
    }

    public function findMostRecentEquivalentSince(
        string $accountId,
        WithdrawMethod $method,
        Money $amount,
        PixKey $pixKey,
        ?ScheduleAt $scheduleAt,
        DateTimeImmutable $since,
    ): ?AccountWithdraw {
        return $this->withdraw !== null
            && $this->withdraw->accountId() === $accountId
            && $this->withdraw->method() === $method
            && $this->withdraw->amount()->equals($amount)
            && $this->withdraw->scheduledFor()?->value() == $scheduleAt?->value()
            && $this->withdraw->createdAt() >= $since
            ? $this->withdraw
            : null;
    }

    public function lockById(string $withdrawId): ?AccountWithdraw
    {
        return $this->findById($withdrawId);
    }

    public function save(AccountWithdraw $withdraw): void
    {
        $this->withdraw = $withdraw;
    }
}

final class ProcessInMemoryProcessableWithdrawQuery implements ProcessableWithdrawQuery
{
    public function __construct(
        private ProcessInMemoryWithdrawRepository $withdrawRepository,
        private ?AccountWithdrawPix $withdrawPix,
    )
    {
    }

    public function lockById(string $withdrawId): ?ProcessableWithdraw
    {
        $withdraw = $this->withdrawRepository->lockById($withdrawId);

        if ($withdraw === null) {
            return null;
        }

        return new ProcessableWithdraw($withdraw, $this->withdrawPix);
    }
}

final class ProcessInMemoryAccountRepository implements AccountRepository
{
    public function __construct(public Account $account)
    {
    }

    public function findById(string $accountId): ?Account
    {
        return $this->account->id() === $accountId ? $this->account : null;
    }

    public function lockById(string $accountId): ?Account
    {
        return $this->findById($accountId);
    }

    public function save(Account $account): void
    {
        $this->account = $account;
    }
}

final class ProcessInMemoryAccountTransactionRepository implements AccountTransactionRepository
{
    /** @var list<AccountTransaction> */
    public array $saved = [];

    public function findByWithdrawId(string $withdrawId): ?AccountTransaction
    {
        foreach ($this->saved as $transaction) {
            if ($transaction->referenceId() === $withdrawId) {
                return $transaction;
            }
        }

        return null;
    }

    public function existsByWithdrawId(string $withdrawId): bool
    {
        return $this->findByWithdrawId($withdrawId) !== null;
    }

    public function save(AccountTransaction $accountTransaction): void
    {
        $this->saved[] = $accountTransaction;
    }
}

final class ProcessAtomicAccountDebit implements AtomicAccountDebit
{
    public function execute(Account $account, Money $amount, DateTimeImmutable $processedAt): \Tecnofit\PixWithdrawal\Core\Shared\Result
    {
        return $account->debit($amount, $processedAt);
    }
}

final class ProcessThrowingAtomicAccountDebit implements AtomicAccountDebit
{
    public function execute(Account $account, Money $amount, DateTimeImmutable $processedAt): \Tecnofit\PixWithdrawal\Core\Shared\Result
    {
        throw new \Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException('temporary gateway failure');
    }
}

final class ProcessFailureClassifier implements FailureClassifier
{
    public function classify(\Throwable $throwable): \Tecnofit\PixWithdrawal\Core\Domain\Enum\FailureCategory
    {
        if ($throwable instanceof \Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException) {
            return \Tecnofit\PixWithdrawal\Core\Domain\Enum\FailureCategory::INTERNAL;
        }

        return \Tecnofit\PixWithdrawal\Core\Domain\Enum\FailureCategory::BUSINESS;
    }
}

final class ProcessSpyTransactionManager implements TransactionManager
{
    public function run(callable $operation): mixed
    {
        return $operation();
    }
}

final class ProcessSpyDomainEventDispatcher implements DomainEventDispatcher
{
    /** @var list<DomainEvent> */
    public array $events = [];

    public function dispatch(DomainEvent $event): void
    {
        $this->events[] = $event;
    }
}
