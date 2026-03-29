<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\UseCase;

use Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawCommand;
use Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawCommandValidator;
use Tecnofit\PixWithdrawal\Core\Application\Dto\CreateWithdrawData;
use Tecnofit\PixWithdrawal\Core\Application\Dto\CreateWithdrawOutput;
use Tecnofit\PixWithdrawal\Core\Application\Dto\ValidatedCreateWithdrawData;
use Tecnofit\PixWithdrawal\Core\Application\Exception\AccountNotFound;
use Tecnofit\PixWithdrawal\Core\Application\Exception\DuplicateWithdrawRequestBlocked;
use Tecnofit\PixWithdrawal\Core\Application\Exception\InsufficientWithdrawBalance;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Domain\Event\WithdrawQueued;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\TransactionManager;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawPixRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawRepository;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawDuplicateGuardFingerprintGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawDuplicateGuardWindow;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\UuidGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawIdempotencyKeyGenerator;

final class CreateWithdrawHandler implements CreateWithdraw
{
    public function __construct(
        private readonly CreateWithdrawCommandValidator $validator,
        private readonly AccountRepository $accountRepository,
        private readonly WithdrawRepository $withdrawRepository,
        private readonly WithdrawPixRepository $withdrawPixRepository,
        private readonly DomainEventDispatcher $domainEventDispatcher,
        private readonly TransactionManager $transactionManager,
        private readonly UuidGenerator $uuidGenerator,
        private readonly WithdrawIdempotencyKeyGenerator $withdrawIdempotencyKeyGenerator,
        private readonly WithdrawDuplicateGuardFingerprintGenerator $withdrawDuplicateGuardFingerprintGenerator,
        private readonly WithdrawDuplicateGuardWindow $withdrawDuplicateGuardWindow,
        private readonly Clock $clock,
    ) {
    }

    public function execute(CreateWithdrawCommand $command): CreateWithdrawOutput
    {
        $validated = $this->validator->validate($command, $this->clock);

        $account = $this->accountRepository->findById($validated->accountId());

        if ($account === null) {
            throw AccountNotFound::withId($validated->accountId());
        }

        $this->assertImmediateWithdrawHasAvailableBalance($account, $validated);

        $createdAt = $this->clock->now();
        $duplicateGuardFingerprint = $this->withdrawDuplicateGuardFingerprintGenerator->generate($validated);
        $this->assertRecentEquivalentWithdrawDoesNotExist($validated, $createdAt);

        $idempotencyKey = $this->withdrawIdempotencyKeyGenerator->generate($validated);
        $withdrawId = $this->uuidGenerator->generate();

        $withdraw = $validated->scheduleAt() === null
            ? AccountWithdraw::createQueued(
                id: $withdrawId,
                accountId: $validated->accountId(),
                method: $validated->method(),
                amount: $validated->amount(),
                correlationId: $validated->correlationId(),
                idempotencyKey: $idempotencyKey,
                duplicateGuardFingerprint: $duplicateGuardFingerprint,
                queuedAt: $createdAt,
            )
            : AccountWithdraw::createScheduled(
                id: $withdrawId,
                accountId: $validated->accountId(),
                method: $validated->method(),
                amount: $validated->amount(),
                scheduledFor: $validated->scheduleAt(),
                correlationId: $validated->correlationId(),
                idempotencyKey: $idempotencyKey,
                duplicateGuardFingerprint: $duplicateGuardFingerprint,
                createdAt: $createdAt,
            );

        $withdrawPix = AccountWithdrawPix::create(
            withdrawId: $withdrawId,
            method: $validated->method(),
            pixKey: $validated->pixKey(),
            createdAt: $createdAt,
        );

        $this->transactionManager->run(function () use ($withdraw, $withdrawPix): void {
            $this->withdrawRepository->save($withdraw);
            $this->withdrawPixRepository->save($withdrawPix);
        });

        if ($withdraw->isImmediate()) {
            $this->domainEventDispatcher->dispatch(
                WithdrawQueued::fromAggregate(
                    withdraw: $withdraw,
                    withdrawPix: $withdrawPix,
                    occurredAt: $createdAt,
                    traceMetadata: $validated->traceMetadata(),
                )
            );
        }

        return new CreateWithdrawData(
            withdrawId: $withdraw->id(),
            correlationId: $validated->correlationId(),
            status: $withdraw->status()->value,
            traceMetadata: $validated->traceMetadata(),
        );
    }

    private function assertRecentEquivalentWithdrawDoesNotExist(
        ValidatedCreateWithdrawData $validated,
        \DateTimeImmutable $createdAt,
    ): void {
        $windowSeconds = $this->withdrawDuplicateGuardWindow->seconds();
        $since = $createdAt->modify(sprintf('-%d seconds', $windowSeconds));
        $existingWithdraw = $this->withdrawRepository->findMostRecentEquivalentSince(
            $validated->accountId(),
            $validated->method(),
            $validated->amount(),
            $validated->pixKey(),
            $validated->scheduleAt(),
            $since,
        );

        if ($existingWithdraw === null) {
            return;
        }

        $retryAtTimestamp = $existingWithdraw->createdAt()->getTimestamp() + $windowSeconds;
        $retryAfterSeconds = max(1, $retryAtTimestamp - $createdAt->getTimestamp());

        throw DuplicateWithdrawRequestBlocked::becauseRecentEquivalentRequestExists(
            $existingWithdraw,
            $retryAfterSeconds,
        );
    }

    private function assertImmediateWithdrawHasAvailableBalance(
        Account $account,
        ValidatedCreateWithdrawData $validated,
    ): void {
        if ($validated->scheduleAt() !== null) {
            return;
        }

        if (! $account->balance()->lessThan($validated->amount())) {
            return;
        }

        throw InsufficientWithdrawBalance::forImmediateWithdraw($account, $validated->amount());
    }
}
