<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Mappers;

use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;

final readonly class AccountWithdrawPixRecordMapper implements RecordMapper
{
    public function __construct(private DateTimeRecordMapper $dateTimeRecordMapper = new DateTimeRecordMapper())
    {
    }

    public function toRecord(object $entity): array
    {
        if (! $entity instanceof AccountWithdrawPix) {
            throw new \InvalidArgumentException('AccountWithdrawPixRecordMapper expects an AccountWithdrawPix entity.');
        }

        return [
            'account_withdraw_id' => $entity->withdrawId(),
            'type' => $entity->pixKeyType()->value,
            'key' => $entity->pixKey()->value(),
            'created_at' => $this->dateTimeRecordMapper->toRecord($entity->createdAt()),
            'updated_at' => $this->dateTimeRecordMapper->toRecord($entity->updatedAt()),
        ];
    }

    public function toDomain(array $record): object
    {
        $pixKeyType = PixKeyType::from(trim((string) ($record['type'] ?? '')));

        return AccountWithdrawPix::reconstitute(
            withdrawId: trim((string) ($record['account_withdraw_id'] ?? '')),
            method: WithdrawMethod::PIX,
            pixKey: PixKey::from($pixKeyType, trim((string) ($record['key'] ?? ''))),
            createdAt: $this->dateTimeRecordMapper->toDomain($record['created_at'] ?? null, 'created_at'),
            updatedAt: $this->dateTimeRecordMapper->toDomain($record['updated_at'] ?? null, 'updated_at'),
        );
    }
}
