<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Seeds;

use DateTimeImmutable;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountTransaction;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionDirection;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionReferenceType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\TransactionManager;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountTransactionRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawPixRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawRepository;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;

final readonly class PixWithdrawalCaseSeeder
{
    private const string SEED_NAME = 'pix-withdrawal-case';

    public function __construct(
        private TransactionManager $transactionManager,
        private SeedExecutionTracker $seedExecutionTracker,
        private AccountRepository $accountRepository,
        private WithdrawRepository $withdrawRepository,
        private WithdrawPixRepository $withdrawPixRepository,
        private AccountTransactionRepository $accountTransactionRepository,
        private Clock $clock,
    ) {
    }

    public function seed(): SeedExecutionResult
    {
        return $this->transactionManager->run(function (): SeedExecutionResult {
            $seededAt = $this->clock->now();

            if (! $this->seedExecutionTracker->markAsStarted(self::SEED_NAME, $seededAt)) {
                return SeedExecutionResult::skipped(self::SEED_NAME, $seededAt);
            }

            foreach ($this->accounts($seededAt) as $account) {
                $this->accountRepository->save($account);
            }

            foreach ($this->withdraws($seededAt) as $withdraw) {
                $this->withdrawRepository->save($withdraw);
            }

            foreach ($this->withdrawPixPayloads($seededAt) as $withdrawPix) {
                $this->withdrawPixRepository->save($withdrawPix);
            }

            foreach ($this->accountTransactions($seededAt) as $accountTransaction) {
                if (! $this->accountTransactionRepository->existsByWithdrawId($accountTransaction->referenceId())) {
                    $this->accountTransactionRepository->save($accountTransaction);
                }
            }

            return SeedExecutionResult::applied(self::SEED_NAME, $seededAt);
        });
    }

    /**
     * @return list<Account>
     */
    private function accounts(DateTimeImmutable $seededAt): array
    {
        $immediateProcessedAt = $seededAt->modify('-113 minutes');
        $failedProcessedAt = $seededAt->modify('-42 minutes');

        return [
            Account::reconstitute(
                id: '11111111-1111-4111-8111-111111111111',
                name: 'Seed Immediate Success Wallet',
                balance: Money::fromDecimal('849.25'),
                createdAt: $seededAt->modify('-120 minutes'),
                updatedAt: $immediateProcessedAt,
            ),
            Account::reconstitute(
                id: '22222222-2222-4222-8222-222222222222',
                name: 'Seed Scheduled Pending Wallet',
                balance: Money::fromDecimal('250.50'),
                createdAt: $seededAt->modify('-1 hour'),
                updatedAt: $seededAt->modify('-1 hour'),
            ),
            Account::reconstitute(
                id: '33333333-3333-4333-8333-333333333333',
                name: 'Seed Scheduled Insufficient Wallet',
                balance: Money::fromDecimal('40.00'),
                createdAt: $seededAt->modify('-180 minutes'),
                updatedAt: $failedProcessedAt,
            ),
            Account::reconstitute(
                id: '44444444-4444-4444-8444-444444444444',
                name: 'Seed Empty Balance Wallet',
                balance: Money::fromDecimal('0.00'),
                createdAt: $seededAt->modify('-30 minutes'),
                updatedAt: $seededAt->modify('-30 minutes'),
            ),
        ];
    }

    /**
     * @return list<AccountWithdraw>
     */
    private function withdraws(DateTimeImmutable $seededAt): array
    {
        $scheduledFutureAt = ScheduleAt::fromDateTime($seededAt->modify('+2 hours'), $this->clock);
        $scheduledPastAt = ScheduleAt::fromDateTime(
            $seededAt->modify('-50 minutes'),
            new SeedScenarioClock($seededAt->modify('-90 minutes'))
        );

        return [
            AccountWithdraw::reconstitute(
                id: 'aaaaaaa1-1111-4111-8111-111111111111',
                accountId: '11111111-1111-4111-8111-111111111111',
                method: WithdrawMethod::PIX,
                amount: Money::fromDecimal('150.75'),
                status: WithdrawStatus::DONE,
                correlationId: 'seed-correlation-immediate-success',
                idempotencyKey: 'seed-immediate-success',
                retryCount: 0,
                createdAt: $seededAt->modify('-120 minutes'),
                updatedAt: $seededAt->modify('-113 minutes'),
                scheduledFor: null,
                queuedAt: $seededAt->modify('-118 minutes'),
                processingStartedAt: $seededAt->modify('-116 minutes'),
                processedAt: $seededAt->modify('-113 minutes'),
                errorReason: null,
                lastRetryAt: null,
            ),
            AccountWithdraw::createScheduled(
                id: 'bbbbbbb2-2222-4222-8222-222222222222',
                accountId: '22222222-2222-4222-8222-222222222222',
                method: WithdrawMethod::PIX,
                amount: Money::fromDecimal('200.00'),
                scheduledFor: $scheduledFutureAt,
                correlationId: 'seed-correlation-scheduled-pending',
                idempotencyKey: 'seed-scheduled-pending',
                createdAt: $seededAt->modify('-20 minutes'),
            ),
            AccountWithdraw::reconstitute(
                id: 'ccccccc3-3333-4333-8333-333333333333',
                accountId: '33333333-3333-4333-8333-333333333333',
                method: WithdrawMethod::PIX,
                amount: Money::fromDecimal('75.00'),
                status: WithdrawStatus::FAILED_INSUFFICIENT_FUNDS,
                correlationId: 'seed-correlation-scheduled-failed',
                idempotencyKey: 'seed-scheduled-failed',
                retryCount: 0,
                createdAt: $seededAt->modify('-90 minutes'),
                updatedAt: $seededAt->modify('-42 minutes'),
                scheduledFor: $scheduledPastAt,
                queuedAt: $seededAt->modify('-48 minutes'),
                processingStartedAt: $seededAt->modify('-45 minutes'),
                processedAt: $seededAt->modify('-42 minutes'),
                errorReason: 'Insufficient account balance for scheduled PIX withdraw.',
                lastRetryAt: null,
            ),
        ];
    }

    /**
     * @return list<AccountWithdrawPix>
     */
    private function withdrawPixPayloads(DateTimeImmutable $seededAt): array
    {
        return [
            AccountWithdrawPix::create(
                withdrawId: 'aaaaaaa1-1111-4111-8111-111111111111',
                method: WithdrawMethod::PIX,
                pixKey: PixKey::from(PixKeyType::EMAIL, 'pix.immediate@example.com'),
                createdAt: $seededAt->modify('-2 hours'),
            ),
            AccountWithdrawPix::create(
                withdrawId: 'bbbbbbb2-2222-4222-8222-222222222222',
                method: WithdrawMethod::PIX,
                pixKey: PixKey::from(PixKeyType::EMAIL, 'pix.scheduled@example.com'),
                createdAt: $seededAt->modify('-20 minutes'),
            ),
            AccountWithdrawPix::create(
                withdrawId: 'ccccccc3-3333-4333-8333-333333333333',
                method: WithdrawMethod::PIX,
                pixKey: PixKey::from(PixKeyType::EMAIL, 'pix.failed@example.com'),
                createdAt: $seededAt->modify('-90 minutes'),
            ),
        ];
    }

    /**
     * @return list<AccountTransaction>
     */
    private function accountTransactions(DateTimeImmutable $seededAt): array
    {
        return [
            AccountTransaction::create(
                accountId: '11111111-1111-4111-8111-111111111111',
                referenceType: AccountTransactionReferenceType::WITHDRAW,
                referenceId: 'aaaaaaa1-1111-4111-8111-111111111111',
                direction: AccountTransactionDirection::DEBIT,
                amount: Money::fromDecimal('150.75'),
                balanceBefore: Money::fromDecimal('1000.00'),
                balanceAfter: Money::fromDecimal('849.25'),
                createdAt: $seededAt->modify('-113 minutes'),
            ),
        ];
    }
}
