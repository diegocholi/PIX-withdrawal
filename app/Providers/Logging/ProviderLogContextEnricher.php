<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Logging;

use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

final class ProviderLogContextEnricher
{
    public function enrich(string $message, LogContext $context): LogContext
    {
        $currentContext = $context->context();

        return new LogContext(
            correlationId: $context->correlationId(),
            withdrawId: $context->withdrawId(),
            accountId: $context->accountId(),
            status: $context->status(),
            errorCode: $context->errorCode(),
            traceMetadata: $context->traceMetadata(),
            context: array_merge(
                [
                    'provider' => $this->normalizeOptionalString($currentContext['provider'] ?? null) ?? 'providers',
                    'operation' => $this->normalizeOptionalString($currentContext['operation'] ?? null) ?? $message,
                ],
                $this->extractOptionalOperationalFields($currentContext),
                $currentContext,
            ),
        );
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, string|int>
     */
    private function extractOptionalOperationalFields(array $context): array
    {
        $operationalFields = [];

        foreach (['topic', 'recipient'] as $key) {
            $value = $this->normalizeOptionalString($context[$key] ?? null);

            if ($value !== null) {
                $operationalFields[$key] = $value;
            }
        }

        if (isset($context['partition']) && is_int($context['partition'])) {
            $operationalFields['partition'] = $context['partition'];
        }

        return $operationalFields;
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }
}
