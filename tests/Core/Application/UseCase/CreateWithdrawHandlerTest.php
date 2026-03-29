<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Application\UseCase;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawCommandValidator;
use Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawInput;
use Tecnofit\PixWithdrawal\Core\Application\Exception\AccountNotFound;
use Tecnofit\PixWithdrawal\Core\Application\Exception\DuplicateWithdrawRequestBlocked;
use Tecnofit\PixWithdrawal\Core\Application\Exception\InsufficientWithdrawBalance;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\CreateWithdrawHandler;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;
use Tecnofit\PixWithdrawal\Core\Domain\Event\WithdrawQueued;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\TransactionManager;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawPixRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawRepository;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\UuidGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawDuplicateGuardFingerprintGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawDuplicateGuardWindow;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawIdempotencyKeyGenerator;

final class CreateWithdrawHandlerTest extends TestCase
{
    public function testHandlerCreatesQueuedWithdrawAndPersistsPixDataInsideTransaction(): void
    {
        $accountRepository = new InMemoryAccountRepository([
            Account::create('acc-1', 'Main Account', Money::fromDecimal('100.00'), new DateTimeImmutable('2026-03-28T09:00:00-03:00')),
        ]);
        $withdrawRepository = new InMemoryWithdrawRepository();
        $withdrawPixRepository = new InMemoryWithdrawPixRepository();
        $domainEventDispatcher = new SpyDomainEventDispatcher();
        $transactionManager = new SpyTransactionManager();

        $output = $this->handler(
            $accountRepository,
            $withdrawRepository,
            $withdrawPixRepository,
            $domainEventDispatcher,
            $transactionManager,
            new FixedUuidGenerator('wd-1'),
            new FixedWithdrawIdempotencyKeyGenerator('idem-fixed'),
            new FixedWithdrawDuplicateGuardFingerprintGenerator('guard-fixed'),
            new FixedWithdrawDuplicateGuardWindow(60),
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        )->execute(
            new CreateWithdrawInput('acc-1', 'corr-1', 'PIX', 'EMAIL', 'user@example.com', '25.00')
        );

        self::assertSame('wd-1', $output->withdrawId());
        self::assertSame('corr-1', $output->correlationId());
        self::assertSame('QUEUED', $output->status());
        self::assertCount(1, $withdrawRepository->saved);
        self::assertCount(1, $withdrawPixRepository->saved);
        self::assertSame(1, $transactionManager->runs);
        self::assertSame('QUEUED', $withdrawRepository->saved[0]->status()->value);
        self::assertSame('idem-fixed', $withdrawRepository->saved[0]->idempotencyKey());
        self::assertSame('guard-fixed', $withdrawRepository->saved[0]->duplicateGuardFingerprint());
        self::assertTrue($withdrawRepository->saved[0]->isImmediate());
        self::assertCount(1, $domainEventDispatcher->events);
        self::assertInstanceOf(WithdrawQueued::class, $domainEventDispatcher->events[0]);
        self::assertSame('withdraw.queued', $domainEventDispatcher->events[0]->eventName());
    }

    public function testHandlerCreatesScheduledWithdrawWhenScheduleIsPresent(): void
    {
        $withdrawRepository = new InMemoryWithdrawRepository();
        $domainEventDispatcher = new SpyDomainEventDispatcher();

        $output = $this->handler(
            new InMemoryAccountRepository([
                Account::create('acc-1', 'Main Account', Money::fromDecimal('100.00'), new DateTimeImmutable('2026-03-28T09:00:00-03:00')),
            ]),
            $withdrawRepository,
            new InMemoryWithdrawPixRepository(),
            $domainEventDispatcher,
            new SpyTransactionManager(),
            new FixedUuidGenerator('wd-2'),
            new FixedWithdrawIdempotencyKeyGenerator('idem-fixed-scheduled'),
            new FixedWithdrawDuplicateGuardFingerprintGenerator('guard-fixed-scheduled'),
            new FixedWithdrawDuplicateGuardWindow(60),
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        )->execute(
            new CreateWithdrawInput('acc-1', 'corr-2', 'PIX', 'EMAIL', 'user@example.com', '25.00', '2026-03-29T10:00:00-03:00')
        );

        self::assertSame('SCHEDULED', $output->status());
        self::assertTrue($withdrawRepository->saved[0]->isScheduled());
        self::assertSame('guard-fixed-scheduled', $withdrawRepository->saved[0]->duplicateGuardFingerprint());
        self::assertSame('2026-03-29T10:00:00-03:00', $withdrawRepository->saved[0]->scheduledFor()?->value()->format(DATE_ATOM));
        self::assertCount(0, $domainEventDispatcher->events);
    }

    public function testHandlerRejectsImmediateWithdrawWithoutAvailableBalance(): void
    {
        $withdrawRepository = new InMemoryWithdrawRepository();
        $withdrawPixRepository = new InMemoryWithdrawPixRepository();
        $domainEventDispatcher = new SpyDomainEventDispatcher();
        $transactionManager = new SpyTransactionManager();

        $this->expectException(InsufficientWithdrawBalance::class);

        try {
            $this->handler(
                new InMemoryAccountRepository([
                    Account::create('acc-1', 'Main Account', Money::fromDecimal('10.00'), new DateTimeImmutable('2026-03-28T09:00:00-03:00')),
                ]),
                $withdrawRepository,
                $withdrawPixRepository,
                $domainEventDispatcher,
                $transactionManager,
                new FixedUuidGenerator('wd-insufficient'),
                new FixedWithdrawIdempotencyKeyGenerator('idem-insufficient'),
                new FixedWithdrawDuplicateGuardFingerprintGenerator('guard-insufficient'),
                new FixedWithdrawDuplicateGuardWindow(60),
                $this->clockAt('2026-03-28T10:00:00-03:00'),
            )->execute(
                new CreateWithdrawInput('acc-1', 'corr-insufficient', 'PIX', 'EMAIL', 'user@example.com', '25.00')
            );
        } finally {
            self::assertCount(0, $withdrawRepository->saved);
            self::assertCount(0, $withdrawPixRepository->saved);
            self::assertSame(0, $transactionManager->runs);
            self::assertCount(0, $domainEventDispatcher->events);
        }
    }

    public function testHandlerAllowsScheduledWithdrawWithoutCurrentAvailableBalance(): void
    {
        $withdrawRepository = new InMemoryWithdrawRepository();
        $withdrawPixRepository = new InMemoryWithdrawPixRepository();
        $domainEventDispatcher = new SpyDomainEventDispatcher();
        $transactionManager = new SpyTransactionManager();

        $output = $this->handler(
            new InMemoryAccountRepository([
                Account::create('acc-1', 'Main Account', Money::fromDecimal('10.00'), new DateTimeImmutable('2026-03-28T09:00:00-03:00')),
            ]),
            $withdrawRepository,
            $withdrawPixRepository,
            $domainEventDispatcher,
            $transactionManager,
            new FixedUuidGenerator('wd-scheduled-low-balance'),
            new FixedWithdrawIdempotencyKeyGenerator('idem-scheduled-low-balance'),
            new FixedWithdrawDuplicateGuardFingerprintGenerator('guard-scheduled-low-balance'),
            new FixedWithdrawDuplicateGuardWindow(60),
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        )->execute(
            new CreateWithdrawInput('acc-1', 'corr-scheduled-low-balance', 'PIX', 'EMAIL', 'user@example.com', '25.00', '2026-03-29T10:00:00-03:00')
        );

        self::assertSame('SCHEDULED', $output->status());
        self::assertCount(1, $withdrawRepository->saved);
        self::assertCount(1, $withdrawPixRepository->saved);
        self::assertSame(1, $transactionManager->runs);
        self::assertCount(0, $domainEventDispatcher->events);
    }

    public function testHandlerRejectsMissingAccount(): void
    {
        $this->expectException(AccountNotFound::class);

        $this->handler(
            new InMemoryAccountRepository([]),
            new InMemoryWithdrawRepository(),
            new InMemoryWithdrawPixRepository(),
            new SpyDomainEventDispatcher(),
            new SpyTransactionManager(),
            new FixedUuidGenerator('wd-3'),
            new FixedWithdrawIdempotencyKeyGenerator('idem-fixed-missing'),
            new FixedWithdrawDuplicateGuardFingerprintGenerator('guard-fixed-missing'),
            new FixedWithdrawDuplicateGuardWindow(60),
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        )->execute(
            new CreateWithdrawInput('acc-404', 'corr-3', 'PIX', 'EMAIL', 'user@example.com', '25.00')
        );
    }

    public function testHandlerBlocksEquivalentWithdrawInsideDuplicateGuardWindow(): void
    {
        $existingWithdraw = AccountWithdraw::createQueued(
            id: 'wd-existing',
            accountId: 'acc-1',
            method: \Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod::PIX,
            amount: Money::fromDecimal('25.00'),
            correlationId: 'corr-existing',
            idempotencyKey: 'idem-existing-request',
            queuedAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            duplicateGuardFingerprint: 'guard-existing',
        );
        $withdrawRepository = new InMemoryWithdrawRepository([$existingWithdraw]);

        $this->expectException(DuplicateWithdrawRequestBlocked::class);

        $this->handler(
            new InMemoryAccountRepository([
                Account::create('acc-1', 'Main Account', Money::fromDecimal('100.00'), new DateTimeImmutable('2026-03-28T09:00:00-03:00')),
            ]),
            $withdrawRepository,
            new InMemoryWithdrawPixRepository(),
            new SpyDomainEventDispatcher(),
            new SpyTransactionManager(),
            new FixedUuidGenerator('wd-new'),
            new FixedWithdrawIdempotencyKeyGenerator('idem-new-request'),
            new FixedWithdrawDuplicateGuardFingerprintGenerator('guard-existing'),
            new FixedWithdrawDuplicateGuardWindow(60),
            $this->clockAt('2026-03-28T10:00:30-03:00'),
        )->execute(
            new CreateWithdrawInput('acc-1', 'corr-retry', 'PIX', 'EMAIL', 'user@example.com', '25.00')
        );
    }

    public function testHandlerCreatesAnotherWithdrawAfterDuplicateGuardWindowExpires(): void
    {
        $existingWithdraw = AccountWithdraw::createQueued(
            id: 'wd-existing',
            accountId: 'acc-1',
            method: \Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod::PIX,
            amount: Money::fromDecimal('25.00'),
            correlationId: 'corr-existing',
            idempotencyKey: 'idem-existing-request',
            queuedAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            duplicateGuardFingerprint: 'guard-existing',
        );
        $withdrawRepository = new InMemoryWithdrawRepository([$existingWithdraw]);
        $withdrawPixRepository = new InMemoryWithdrawPixRepository();
        $domainEventDispatcher = new SpyDomainEventDispatcher();
        $transactionManager = new SpyTransactionManager();

        $output = $this->handler(
            new InMemoryAccountRepository([
                Account::create('acc-1', 'Main Account', Money::fromDecimal('100.00'), new DateTimeImmutable('2026-03-28T09:00:00-03:00')),
            ]),
            $withdrawRepository,
            $withdrawPixRepository,
            $domainEventDispatcher,
            $transactionManager,
            new FixedUuidGenerator('wd-new'),
            new FixedWithdrawIdempotencyKeyGenerator('idem-new-request'),
            new FixedWithdrawDuplicateGuardFingerprintGenerator('guard-existing'),
            new FixedWithdrawDuplicateGuardWindow(60),
            $this->clockAt('2026-03-28T10:01:30-03:00'),
        )->execute(
            new CreateWithdrawInput('acc-1', 'corr-retry', 'PIX', 'EMAIL', 'user@example.com', '25.00')
        );

        self::assertSame('wd-new', $output->withdrawId());
        self::assertSame('corr-retry', $output->correlationId());
        self::assertSame('QUEUED', $output->status());
        self::assertCount(1, $withdrawRepository->saved);
        self::assertSame('idem-new-request', $withdrawRepository->saved[0]->idempotencyKey());
    }

    private function handler(
        AccountRepository $accountRepository,
        WithdrawRepository $withdrawRepository,
        WithdrawPixRepository $withdrawPixRepository,
        DomainEventDispatcher $domainEventDispatcher,
        TransactionManager $transactionManager,
        UuidGenerator $uuidGenerator,
        WithdrawIdempotencyKeyGenerator $withdrawIdempotencyKeyGenerator,
        WithdrawDuplicateGuardFingerprintGenerator $withdrawDuplicateGuardFingerprintGenerator,
        WithdrawDuplicateGuardWindow $withdrawDuplicateGuardWindow,
        Clock $clock,
    ): CreateWithdrawHandler {
        return new CreateWithdrawHandler(
            new CreateWithdrawCommandValidator(),
            $accountRepository,
            $withdrawRepository,
            $withdrawPixRepository,
            $domainEventDispatcher,
            $transactionManager,
            $uuidGenerator,
            $withdrawIdempotencyKeyGenerator,
            $withdrawDuplicateGuardFingerprintGenerator,
            $withdrawDuplicateGuardWindow,
            $clock,
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

final class InMemoryAccountRepository implements AccountRepository
{
    /**
     * @param list<Account> $accounts
     */
    public function __construct(private array $accounts)
    {
    }

    public function findById(string $accountId): ?Account
    {
        foreach ($this->accounts as $account) {
            if ($account->id() === $accountId) {
                return $account;
            }
        }

        return null;
    }

    public function lockById(string $accountId): ?Account
    {
        return $this->findById($accountId);
    }

    public function save(Account $account): void
    {
    }
}

final class InMemoryWithdrawRepository implements WithdrawRepository
{
    /** @var list<AccountWithdraw> */
    public array $saved = [];

    /**
     * @param list<AccountWithdraw> $existing
     */
    public function __construct(private array $existing = [])
    {
    }

    public function findById(string $withdrawId): ?AccountWithdraw
    {
        foreach (array_merge($this->existing, $this->saved) as $withdraw) {
            if ($withdraw->id() === $withdrawId) {
                return $withdraw;
            }
        }

        return null;
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?AccountWithdraw
    {
        foreach (array_merge($this->existing, $this->saved) as $withdraw) {
            if ($withdraw->idempotencyKey() === $idempotencyKey) {
                return $withdraw;
            }
        }

        return null;
    }

    public function findMostRecentEquivalentSince(
        string $accountId,
        WithdrawMethod $method,
        Money $amount,
        PixKey $pixKey,
        ?ScheduleAt $scheduleAt,
        DateTimeImmutable $since,
    ): ?AccountWithdraw {
        $matches = array_values(array_filter(
            array_merge($this->existing, $this->saved),
            static fn (AccountWithdraw $withdraw): bool => $withdraw->accountId() === $accountId
                && $withdraw->method() === $method
                && $withdraw->amount()->equals($amount)
                && $withdraw->scheduledFor()?->value() == $scheduleAt?->value()
                && $withdraw->createdAt() >= $since,
        ));

        usort(
            $matches,
            static fn (AccountWithdraw $left, AccountWithdraw $right): int => $right->createdAt() <=> $left->createdAt(),
        );

        return $matches[0] ?? null;
    }

    public function lockById(string $withdrawId): ?AccountWithdraw
    {
        return $this->findById($withdrawId);
    }

    public function save(AccountWithdraw $withdraw): void
    {
        $this->saved[] = $withdraw;
    }
}

final class InMemoryWithdrawPixRepository implements WithdrawPixRepository
{
    /** @var list<AccountWithdrawPix> */
    public array $saved = [];

    public function findByWithdrawId(string $withdrawId): ?AccountWithdrawPix
    {
        return null;
    }

    public function save(AccountWithdrawPix $withdrawPix): void
    {
        $this->saved[] = $withdrawPix;
    }
}

final class SpyTransactionManager implements TransactionManager
{
    public int $runs = 0;

    public function run(callable $operation): mixed
    {
        $this->runs++;

        return $operation();
    }
}

final class SpyDomainEventDispatcher implements DomainEventDispatcher
{
    /** @var list<DomainEvent> */
    public array $events = [];

    public function dispatch(DomainEvent $event): void
    {
        $this->events[] = $event;
    }
}

final class FixedUuidGenerator implements UuidGenerator
{
    /** @var list<string> */
    private array $values;

    public function __construct(string ...$values)
    {
        $this->values = $values;
    }

    public function generate(): string
    {
        return array_shift($this->values) ?? 'uuid-fallback';
    }
}

final class FixedWithdrawIdempotencyKeyGenerator implements WithdrawIdempotencyKeyGenerator
{
    public function __construct(private readonly string $value)
    {
    }

    public function generate(\Tecnofit\PixWithdrawal\Core\Application\Dto\ValidatedCreateWithdrawData $data): string
    {
        return $this->value;
    }

    public function isValid(string $idempotencyKey): bool
    {
        return trim($idempotencyKey) === $this->value;
    }
}

final class FixedWithdrawDuplicateGuardFingerprintGenerator implements WithdrawDuplicateGuardFingerprintGenerator
{
    public function __construct(private readonly string $value)
    {
    }

    public function generate(\Tecnofit\PixWithdrawal\Core\Application\Dto\ValidatedCreateWithdrawData $data): string
    {
        return $this->value;
    }
}

final class FixedWithdrawDuplicateGuardWindow implements WithdrawDuplicateGuardWindow
{
    public function __construct(private readonly int $seconds)
    {
    }

    public function seconds(): int
    {
        return $this->seconds;
    }
}
