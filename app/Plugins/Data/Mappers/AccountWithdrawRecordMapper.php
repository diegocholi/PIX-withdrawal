<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Mappers;

use DateTimeImmutable;
use ReflectionClass;
use ReflectionProperty;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;

final readonly class AccountWithdrawRecordMapper implements RecordMapper
{
    public function __construct(
        private MoneyRecordMapper $moneyRecordMapper = new MoneyRecordMapper(),
        private DateTimeRecordMapper $dateTimeRecordMapper = new DateTimeRecordMapper(),
    ) {
    }

    public function toRecord(object $entity): array
    {
        if (! $entity instanceof AccountWithdraw) {
            throw new \InvalidArgumentException('AccountWithdrawRecordMapper expects an AccountWithdraw entity.');
        }

        $createdAt = $this->dateTimeRecordMapper->toRecord($entity->createdAt());

        return [
            'id' => $entity->id(),
            'account_id' => $entity->accountId(),
            'method' => $entity->method()->value,
            'amount' => $this->moneyRecordMapper->toRecord($entity->amount()),
            'scheduled' => $entity->isScheduled() ? 1 : 0,
            'scheduled_for' => $entity->scheduledFor() !== null
                ? $this->dateTimeRecordMapper->toRecord($entity->scheduledFor()->value())
                : null,
            'status' => $entity->status()->value,
            'error_reason' => $entity->errorReason(),
            'requested_at' => $createdAt,
            'queued_at' => $this->dateTimeRecordMapper->toNullableRecord($entity->queuedAt()),
            'processing_started_at' => $this->dateTimeRecordMapper->toNullableRecord($entity->processingStartedAt()),
            'processed_at' => $this->dateTimeRecordMapper->toNullableRecord($entity->processedAt()),
            'correlation_id' => $entity->correlationId(),
            'idempotency_key' => $entity->idempotencyKey(),
            'duplicate_guard_fingerprint' => $entity->duplicateGuardFingerprint(),
            'retry_count' => $entity->retryCount(),
            'last_retry_at' => $this->dateTimeRecordMapper->toNullableRecord($entity->lastRetryAt()),
            'created_at' => $createdAt,
            'updated_at' => $this->dateTimeRecordMapper->toRecord($entity->updatedAt()),
        ];
    }

    public function toDomain(array $record): object
    {
        return AccountWithdraw::reconstitute(
            id: trim((string) ($record['id'] ?? '')),
            accountId: trim((string) ($record['account_id'] ?? '')),
            method: WithdrawMethod::from(trim((string) ($record['method'] ?? ''))),
            amount: $this->moneyRecordMapper->toDomain($record['amount'] ?? null, 'amount'),
            status: WithdrawStatus::from(trim((string) ($record['status'] ?? ''))),
            correlationId: trim((string) ($record['correlation_id'] ?? '')),
            idempotencyKey: trim((string) ($record['idempotency_key'] ?? '')),
            duplicateGuardFingerprint: $this->normalizeDuplicateGuardFingerprint($record),
            retryCount: (int) ($record['retry_count'] ?? 0),
            createdAt: $this->dateTimeRecordMapper->toDomain(
                $record['created_at'] ?? $record['requested_at'] ?? null,
                'created_at'
            ),
            updatedAt: $this->dateTimeRecordMapper->toDomain($record['updated_at'] ?? null, 'updated_at'),
            scheduledFor: $this->hydrateScheduleAt($record['scheduled_for'] ?? null),
            queuedAt: $this->dateTimeRecordMapper->toNullableDomain($record['queued_at'] ?? null, 'queued_at'),
            processingStartedAt: $this->dateTimeRecordMapper->toNullableDomain(
                $record['processing_started_at'] ?? null,
                'processing_started_at'
            ),
            processedAt: $this->dateTimeRecordMapper->toNullableDomain($record['processed_at'] ?? null, 'processed_at'),
            errorReason: ($errorReason = trim((string) ($record['error_reason'] ?? ''))) === '' ? null : $errorReason,
            lastRetryAt: $this->dateTimeRecordMapper->toNullableDomain($record['last_retry_at'] ?? null, 'last_retry_at'),
        );
    }

    private function hydrateScheduleAt(mixed $value): ?ScheduleAt
    {
        $dateTime = $this->dateTimeRecordMapper->toNullableDomain($value, 'scheduled_for');

        if ($dateTime === null) {
            return null;
        }

        $reflection = new ReflectionClass(ScheduleAt::class);
        /** @var ScheduleAt $instance */
        $instance = $reflection->newInstanceWithoutConstructor();
        $valueProperty = new ReflectionProperty(ScheduleAt::class, 'value');
        $valueProperty->setValue($instance, DateTimeImmutable::createFromInterface($dateTime));

        return $instance;
    }

    private function normalizeDuplicateGuardFingerprint(array $record): string
    {
        $fingerprint = trim((string) ($record['duplicate_guard_fingerprint'] ?? ''));

        if ($fingerprint !== '') {
            return $fingerprint;
        }

        return trim((string) ($record['idempotency_key'] ?? ''));
    }
}
