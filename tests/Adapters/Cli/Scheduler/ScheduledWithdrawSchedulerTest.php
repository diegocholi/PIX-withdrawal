<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli\Scheduler;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Cli\Scheduler\ScheduledWithdrawScheduler;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DueScheduledWithdrawQuery;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\ScheduledWithdrawQueuePromotion;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawPixRepository;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;

final class ScheduledWithdrawSchedulerTest extends TestCase
{
    public function testRunPromotesAndPublishesAllDueWithdrawsWhenNotDryRun(): void
    {
        $clock = $this->fixedClock();
        $query = $this->queryWith($this->scheduledWithdraw('wd-1'), $this->scheduledWithdraw('wd-2'));
        $state = (object) ['promotedIds' => []];
        $promotion = new class ($state) implements ScheduledWithdrawQueuePromotion {
            public function __construct(private object $state)
            {
            }

            public function promote(string $withdrawId, DateTimeImmutable $queuedAt): bool
            {
                $this->state->promotedIds[] = $withdrawId;

                return true;
            }
        };
        $dispatcher = new SpySchedulerDomainEventDispatcher();

        $result = (new ScheduledWithdrawScheduler(
            $query,
            $promotion,
            new InMemoryWithdrawPixRepository(),
            $dispatcher,
            $clock,
        ))->run(batchSize: 10, dryRun: false, executionCorrelationId: 'corr-cli-scheduler');

        self::assertFalse($result->dryRun());
        self::assertSame(['wd-1', 'wd-2'], $result->dueWithdrawIds());
        self::assertSame(['wd-1', 'wd-2'], $result->promotedWithdrawIds());
        self::assertSame(['wd-1', 'wd-2'], $result->publishedWithdrawIds());
        self::assertSame([], $result->skippedWithdrawIds());
        self::assertSame([], $result->publicationFailedWithdrawIds());
        self::assertFalse($result->hasPartialFailure());
        self::assertSame(['wd-1', 'wd-2'], $state->promotedIds);
        self::assertSame(['withdraw.queued', 'withdraw.queued'], array_map(
            static fn (DomainEvent $event): string => $event->eventName(),
            $dispatcher->events,
        ));
        self::assertSame('corr-cli-scheduler', $dispatcher->events[0]->traceMetadata()['cli_correlation_id']);
        self::assertSame('cli.withdraw_scheduler', $dispatcher->events[0]->traceMetadata()['origin']);
    }

    public function testRunDoesNotPromoteOrPublishWhenDryRunIsEnabled(): void
    {
        $query = $this->queryWith($this->scheduledWithdraw('wd-dry-run'));
        $state = (object) ['promotionCalls' => 0];
        $promotion = new class ($state) implements ScheduledWithdrawQueuePromotion {
            public function __construct(private object $state)
            {
            }

            public function promote(string $withdrawId, DateTimeImmutable $queuedAt): bool
            {
                $this->state->promotionCalls++;

                return true;
            }
        };
        $dispatcher = new SpySchedulerDomainEventDispatcher();

        $result = (new ScheduledWithdrawScheduler(
            $query,
            $promotion,
            new InMemoryWithdrawPixRepository(),
            $dispatcher,
            $this->fixedClock(),
        ))->run(batchSize: 10, dryRun: true);

        self::assertTrue($result->dryRun());
        self::assertSame(['wd-dry-run'], $result->dueWithdrawIds());
        self::assertSame([], $result->promotedWithdrawIds());
        self::assertSame([], $result->publishedWithdrawIds());
        self::assertSame([], $result->skippedWithdrawIds());
        self::assertSame([], $result->publicationFailedWithdrawIds());
        self::assertSame(0, $state->promotionCalls);
        self::assertCount(0, $dispatcher->events);
    }

    public function testRunCollectsSkippedWithdrawsWhenPromotionDoesNotSucceed(): void
    {
        $query = $this->queryWith($this->scheduledWithdraw('wd-ok'), $this->scheduledWithdraw('wd-skip'));
        $promotion = new class () implements ScheduledWithdrawQueuePromotion {
            public function promote(string $withdrawId, DateTimeImmutable $queuedAt): bool
            {
                return $withdrawId === 'wd-ok';
            }
        };

        $result = (new ScheduledWithdrawScheduler(
            $query,
            $promotion,
            new InMemoryWithdrawPixRepository(),
            new SpySchedulerDomainEventDispatcher(),
            $this->fixedClock(),
        ))->run(batchSize: 10, dryRun: false);

        self::assertSame(['wd-ok'], $result->promotedWithdrawIds());
        self::assertSame(['wd-ok'], $result->publishedWithdrawIds());
        self::assertSame(['wd-skip'], $result->skippedWithdrawIds());
        self::assertSame([], $result->publicationFailedWithdrawIds());
    }

    public function testRunStopsBatchAndReportsPublicationFailureExplicitly(): void
    {
        $query = $this->queryWith($this->scheduledWithdraw('wd-fail'), $this->scheduledWithdraw('wd-next'));
        $state = (object) ['promotedIds' => []];
        $promotion = new class ($state) implements ScheduledWithdrawQueuePromotion {
            public function __construct(private object $state)
            {
            }

            public function promote(string $withdrawId, DateTimeImmutable $queuedAt): bool
            {
                $this->state->promotedIds[] = $withdrawId;

                return true;
            }
        };
        $dispatcher = new class () implements DomainEventDispatcher {
            /** @var list<string> */
            public array $receivedIds = [];

            public function dispatch(DomainEvent $event): void
            {
                $this->receivedIds[] = $event->aggregateId();

                throw new \RuntimeException('Kafka broker unavailable.');
            }
        };

        $result = (new ScheduledWithdrawScheduler(
            $query,
            $promotion,
            new InMemoryWithdrawPixRepository([
                'wd-fail' => AccountWithdrawPix::create(
                    withdrawId: 'wd-fail',
                    method: WithdrawMethod::PIX,
                    pixKey: PixKey::from(PixKeyType::EMAIL, 'queue@example.com'),
                    createdAt: new DateTimeImmutable('2026-03-28T10:00:00+00:00'),
                ),
            ]),
            $dispatcher,
            $this->fixedClock(),
        ))->run(batchSize: 10, dryRun: false);

        self::assertSame(['wd-fail'], $state->promotedIds);
        self::assertSame(['wd-fail'], $result->promotedWithdrawIds());
        self::assertSame([], $result->publishedWithdrawIds());
        self::assertSame(['wd-next'], $result->skippedWithdrawIds());
        self::assertSame(['wd-fail'], $result->publicationFailedWithdrawIds());
        self::assertTrue($result->hasPartialFailure());
        self::assertSame(['wd-fail'], $dispatcher->receivedIds);
    }

    private function fixedClock(): Clock
    {
        return new class () implements Clock {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-03-29T10:00:00+00:00');
            }
        };
    }

    private function queryWith(AccountWithdraw ...$withdraws): DueScheduledWithdrawQuery
    {
        return new class ($withdraws) implements DueScheduledWithdrawQuery {
            /**
             * @param list<AccountWithdraw> $withdraws
             */
            public function __construct(private array $withdraws)
            {
            }

            public function findDue(DateTimeImmutable $scheduledUntil, int $limit): array
            {
                return array_slice($this->withdraws, 0, $limit);
            }
        };
    }

    private function scheduledWithdraw(string $withdrawId): AccountWithdraw
    {
        return AccountWithdraw::createScheduled(
            id: $withdrawId,
            accountId: 'acc-' . $withdrawId,
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('10.00'),
            scheduledFor: ScheduleAt::fromDateTime(
                new DateTimeImmutable('2026-03-29T09:00:00+00:00'),
                new class () implements Clock {
                    public function now(): DateTimeImmutable
                    {
                        return new DateTimeImmutable('2026-03-28T09:00:00+00:00');
                    }
                },
            ),
            correlationId: 'corr-' . $withdrawId,
            idempotencyKey: 'idem-' . $withdrawId,
            createdAt: new DateTimeImmutable('2026-03-28T09:00:00+00:00'),
        );
    }
}

final class InMemoryWithdrawPixRepository implements WithdrawPixRepository
{
    /**
     * @param array<string, AccountWithdrawPix> $withdrawPixById
     */
    public function __construct(private array $withdrawPixById = [])
    {
    }

    public function findByWithdrawId(string $withdrawId): ?AccountWithdrawPix
    {
        return $this->withdrawPixById[$withdrawId] ?? null;
    }

    public function save(AccountWithdrawPix $withdrawPix): void
    {
        $this->withdrawPixById[$withdrawPix->withdrawId()] = $withdrawPix;
    }
}

final class SpySchedulerDomainEventDispatcher implements DomainEventDispatcher
{
    /** @var list<DomainEvent> */
    public array $events = [];

    public function dispatch(DomainEvent $event): void
    {
        $this->events[] = $event;
    }
}
