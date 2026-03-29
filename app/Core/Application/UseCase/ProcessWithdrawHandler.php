<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\UseCase;

use Throwable;
use Tecnofit\PixWithdrawal\Core\Application\Command\ProcessWithdrawCommand;
use Tecnofit\PixWithdrawal\Core\Application\Dto\ProcessWithdrawData;
use Tecnofit\PixWithdrawal\Core\Application\Dto\ProcessWithdrawOutput;
use Tecnofit\PixWithdrawal\Core\Application\Exception\AccountNotFound;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotFound;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotProcessable;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\FailureCategory;
use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;
use Tecnofit\PixWithdrawal\Core\Domain\Event\WithdrawFailed;
use Tecnofit\PixWithdrawal\Core\Domain\Event\WithdrawProcessed;
use Tecnofit\PixWithdrawal\Core\Domain\Service\WithdrawDebitTransactionFactory;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AtomicAccountDebit;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\ProcessableWithdrawQuery;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\TransactionManager;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountTransactionRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawRepository;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\FailureClassifier;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SensitiveDataMasker;

final class ProcessWithdrawHandler implements ProcessWithdraw
{
    private const MAX_PROCESS_ATTEMPTS = 3;

    public function __construct(
        private readonly WithdrawRepository $withdrawRepository,
        private readonly ProcessableWithdrawQuery $processableWithdrawQuery,
        private readonly AccountRepository $accountRepository,
        private readonly AccountTransactionRepository $accountTransactionRepository,
        private readonly AtomicAccountDebit $atomicAccountDebit,
        private readonly WithdrawDebitTransactionFactory $transactionFactory,
        private readonly DomainEventDispatcher $domainEventDispatcher,
        private readonly TransactionManager $transactionManager,
        private readonly Clock $clock,
        private readonly FailureClassifier $failureClassifier,
        private readonly SensitiveDataMasker $sensitiveDataMasker,
    ) {
    }

    public function execute(ProcessWithdrawCommand $command): ProcessWithdrawOutput
    {
        [$output, $event] = $this->transactionManager->run(function () use ($command): array {
            $processableWithdraw = $this->processableWithdrawQuery->lockById(trim($command->withdrawId()));

            if ($processableWithdraw === null) {
                throw WithdrawNotFound::withId($command->withdrawId());
            }

            $withdraw = $processableWithdraw->withdraw();
            $withdrawPix = $processableWithdraw->withdrawPix();

            if ($withdraw->isFinal()) {
                return [
                    new ProcessWithdrawData(
                        withdrawId: $withdraw->id(),
                        correlationId: $command->correlationId(),
                        status: $withdraw->status()->value,
                        traceMetadata: $command->traceMetadata(),
                    ),
                    null,
                ];
            }

            if (! $withdraw->status()->canBeProcessed()) {
                throw WithdrawNotProcessable::withStatus($withdraw->id(), $withdraw->status());
            }

            if ($withdraw->isProcessing()) {
                return [
                    new ProcessWithdrawData(
                        withdrawId: $withdraw->id(),
                        correlationId: $command->correlationId(),
                        status: $withdraw->status()->value,
                        traceMetadata: $command->traceMetadata(),
                    ),
                    null,
                ];
            }

            $processedAt = $this->clock->now();

            $withdraw->markAsProcessing($processedAt);
            $this->withdrawRepository->save($withdraw);

            if ($command->attempt() > self::MAX_PROCESS_ATTEMPTS) {
                $withdraw->markAsFailedInternal($processedAt, 'retry_limit_exceeded');
                $this->withdrawRepository->save($withdraw);

                return [
                    new ProcessWithdrawData(
                        withdrawId: $withdraw->id(),
                        correlationId: $command->correlationId(),
                        status: $withdraw->status()->value,
                        traceMetadata: $command->traceMetadata(),
                    ),
                    $this->buildProcessingEvent($withdraw, $command->correlationId(), $processedAt, $withdrawPix?->pixKey()->type()->value, $withdrawPix?->pixKey()->value(), $command->traceMetadata()),
                ];
            }

            try {
                $account = $this->accountRepository->lockById($withdraw->accountId());

                if ($account === null) {
                    throw AccountNotFound::withId($withdraw->accountId());
                }

                $balanceBefore = $account->balance();
                $debitResult = $this->atomicAccountDebit->execute($account, $withdraw->amount(), $processedAt);

                if ($debitResult->isFailure()) {
                    $withdraw->markAsFailedInsufficientFunds($processedAt, 'insufficient_balance');
                    $this->withdrawRepository->save($withdraw);

                    return [
                        new ProcessWithdrawData(
                            withdrawId: $withdraw->id(),
                            correlationId: $command->correlationId(),
                            status: $withdraw->status()->value,
                            traceMetadata: $command->traceMetadata(),
                        ),
                        $this->buildProcessingEvent($withdraw, $command->correlationId(), $processedAt, $withdrawPix?->pixKey()->type()->value, $withdrawPix?->pixKey()->value(), $command->traceMetadata()),
                    ];
                }

                /** @var \Tecnofit\PixWithdrawal\Core\Domain\Entity\Account $debitedAccount */
                $debitedAccount = $debitResult->value();

                $transaction = $this->transactionFactory->create(
                    withdraw: $withdraw,
                    balanceBefore: $balanceBefore,
                    balanceAfter: $debitedAccount->balance(),
                    createdAt: $processedAt,
                );

                $withdraw->markAsDone($processedAt);

                $this->accountRepository->save($debitedAccount);
                $this->accountTransactionRepository->save($transaction);
                $this->withdrawRepository->save($withdraw);

                return [
                    new ProcessWithdrawData(
                        withdrawId: $withdraw->id(),
                        correlationId: $command->correlationId(),
                        status: $withdraw->status()->value,
                        traceMetadata: $command->traceMetadata(),
                    ),
                    $this->buildProcessingEvent($withdraw, $command->correlationId(), $processedAt, $withdrawPix?->pixKey()->type()->value, $withdrawPix?->pixKey()->value(), $command->traceMetadata()),
                ];
            } catch (Throwable $throwable) {
                if ($this->failureClassifier->classify($throwable) !== FailureCategory::INTERNAL) {
                    throw $throwable;
                }

                $failedAt = $this->clock->now();
                $withdraw->markAsFailedInternal($failedAt, 'internal_processing_failure');
                $this->withdrawRepository->save($withdraw);

                return [
                    new ProcessWithdrawData(
                        withdrawId: $withdraw->id(),
                        correlationId: $command->correlationId(),
                        status: $withdraw->status()->value,
                        traceMetadata: $command->traceMetadata(),
                    ),
                    $this->buildProcessingEvent($withdraw, $command->correlationId(), $failedAt, $withdrawPix?->pixKey()->type()->value, $withdrawPix?->pixKey()->value(), $command->traceMetadata()),
                ];
            }
        });

        if ($event !== null) {
            $this->domainEventDispatcher->dispatch($event);
        }

        return $output;
    }

    private function buildProcessingEvent(
        \Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw $withdraw,
        string $correlationId,
        \DateTimeImmutable $occurredAt,
        ?string $pixKeyType,
        ?string $pixKeyValue,
        array $traceMetadata,
    ): DomainEvent {
        $pixKeyMasked = $this->sensitiveDataMasker->maskPixKey($pixKeyValue);

        if ($withdraw->isDone()) {
            return WithdrawProcessed::fromAggregate(
                withdraw: $withdraw,
                correlationId: $correlationId,
                occurredAt: $occurredAt,
                pixKeyType: $pixKeyType,
                pixKeyMasked: $pixKeyMasked,
                traceMetadata: $traceMetadata,
            );
        }

        return WithdrawFailed::fromAggregate(
            withdraw: $withdraw,
            correlationId: $correlationId,
            occurredAt: $occurredAt,
            pixKeyType: $pixKeyType,
            pixKeyMasked: $pixKeyMasked,
            traceMetadata: $traceMetadata,
        );
    }
}
