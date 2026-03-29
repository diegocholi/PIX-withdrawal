<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Mappers;

use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;

final readonly class AccountRecordMapper implements RecordMapper
{
    public function __construct(
        private MoneyRecordMapper $moneyRecordMapper = new MoneyRecordMapper(),
        private DateTimeRecordMapper $dateTimeRecordMapper = new DateTimeRecordMapper(),
    ) {
    }

    public function toRecord(object $entity): array
    {
        if (! $entity instanceof Account) {
            throw new \InvalidArgumentException('AccountRecordMapper expects an Account entity.');
        }

        return [
            'id' => $entity->id(),
            'name' => $entity->name(),
            'balance' => $this->moneyRecordMapper->toRecord($entity->balance()),
            'created_at' => $this->dateTimeRecordMapper->toRecord($entity->createdAt()),
            'updated_at' => $this->dateTimeRecordMapper->toRecord($entity->updatedAt()),
        ];
    }

    public function toDomain(array $record): object
    {
        return Account::reconstitute(
            id: trim((string) ($record['id'] ?? '')),
            name: trim((string) ($record['name'] ?? '')),
            balance: $this->moneyRecordMapper->toDomain($record['balance'] ?? null, 'balance'),
            createdAt: $this->dateTimeRecordMapper->toDomain($record['created_at'] ?? null, 'created_at'),
            updatedAt: $this->dateTimeRecordMapper->toDomain($record['updated_at'] ?? null, 'updated_at'),
        );
    }
}
