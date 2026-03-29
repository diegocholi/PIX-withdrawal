<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Query;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;
use Tecnofit\PixWithdrawal\Core\Shared\TraceContext;

final readonly class FindWithdrawStatusInput implements FindWithdrawStatusQuery, SerializableDto
{
    private TraceContext $traceContext;

    public function __construct(
        private string $accountId,
        private string $withdrawId,
        private string $correlationId,
        private array $traceMetadata = [],
    ) {
        $this->traceContext = new TraceContext($this->correlationId, $this->traceMetadata);
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function withdrawId(): string
    {
        return $this->withdrawId;
    }

    public function correlationId(): string
    {
        return $this->traceContext->correlationId();
    }

    public function traceMetadata(): array
    {
        return $this->traceContext->traceMetadata();
    }

    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId(),
            'withdraw_id' => $this->withdrawId(),
            'correlation_id' => $this->correlationId(),
            'trace_metadata' => $this->traceMetadata(),
        ];
    }
}
