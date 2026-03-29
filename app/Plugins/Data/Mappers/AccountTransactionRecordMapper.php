<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Mappers;

use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountTransaction;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionDirection;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionReferenceType;

final readonly class AccountTransactionRecordMapper implements RecordMapper
{
    public function __construct(
        private MoneyRecordMapper $moneyRecordMapper = new MoneyRecordMapper(),
        private DateTimeRecordMapper $dateTimeRecordMapper = new DateTimeRecordMapper(),
    ) {
    }

    public function toRecord(object $entity): array
    {
        if (! $entity instanceof AccountTransaction) {
            throw new \InvalidArgumentException('AccountTransactionRecordMapper expects an AccountTransaction entity.');
        }

        return [
            'account_id' => $entity->accountId(),
            'reference_type' => $entity->referenceType()->value,
            'reference_id' => $entity->referenceId(),
            'direction' => $entity->direction()->value,
            'amount' => $this->moneyRecordMapper->toRecord($entity->amount()),
            'balance_before' => $this->moneyRecordMapper->toRecord($entity->balanceBefore()),
            'balance_after' => $this->moneyRecordMapper->toRecord($entity->balanceAfter()),
            'created_at' => $this->dateTimeRecordMapper->toRecord($entity->createdAt()),
        ];
    }

    public function toDomain(array $record): object
    {
        return AccountTransaction::create(
            accountId: trim((string) ($record['account_id'] ?? '')),
            referenceType: AccountTransactionReferenceType::from(trim((string) ($record['reference_type'] ?? ''))),
            referenceId: trim((string) ($record['reference_id'] ?? '')),
            direction: AccountTransactionDirection::from(trim((string) ($record['direction'] ?? ''))),
            amount: $this->moneyRecordMapper->toDomain($record['amount'] ?? null, 'amount'),
            balanceBefore: $this->moneyRecordMapper->toDomain($record['balance_before'] ?? null, 'balance_before'),
            balanceAfter: $this->moneyRecordMapper->toDomain($record['balance_after'] ?? null, 'balance_after'),
            createdAt: $this->dateTimeRecordMapper->toDomain($record['created_at'] ?? null, 'created_at'),
        );
    }
}
