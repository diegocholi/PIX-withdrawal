<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Mappers;

interface RecordMapper
{
    /**
     * @return array<string, mixed>
     */
    public function toRecord(object $entity): array;

    /**
     * @param array<string, mixed> $record
     */
    public function toDomain(array $record): object;
}
