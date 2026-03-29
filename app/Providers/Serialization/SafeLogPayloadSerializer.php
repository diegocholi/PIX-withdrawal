<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Serialization;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\LogPayloadSerializer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SensitiveDataMasker;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

final readonly class SafeLogPayloadSerializer implements LogPayloadSerializer
{
    public function __construct(
        private SensitiveDataMasker $sensitiveDataMasker,
        private ProviderPayloadNormalizer $payloadNormalizer,
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(LogContext $context): array
    {
        $payload = $this->payloadNormalizer->normalizeArray($context->toArray());

        if (isset($payload['context']) && is_array($payload['context'])) {
            $payload['context'] = $this->sensitiveDataMasker->maskPayload($payload['context']);
        }

        return $payload;
    }
}
