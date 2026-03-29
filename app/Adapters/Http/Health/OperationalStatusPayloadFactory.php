<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Health;

final class OperationalStatusPayloadFactory
{
    /**
     * @return array{status: 'ok', check: 'health', runtime: 'http'}
     */
    public function health(): array
    {
        return [
            'status' => 'ok',
            'check' => 'health',
            'runtime' => 'http',
        ];
    }

    /**
     * @return array{status: 'ready'|'not_ready', check: 'readiness', runtime: 'http'}
     */
    public function readiness(bool $ready): array
    {
        return [
            'status' => $ready ? 'ready' : 'not_ready',
            'check' => 'readiness',
            'runtime' => 'http',
        ];
    }
}
