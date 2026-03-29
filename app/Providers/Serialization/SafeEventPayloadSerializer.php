<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Serialization;

use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\EventPayloadSerializer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SensitiveDataMasker;

final readonly class SafeEventPayloadSerializer implements EventPayloadSerializer
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
    public function serialize(DomainEvent $event): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->payloadNormalizer->normalizeArray($event->toArray());

        if (! isset($payload['payload']) || ! is_array($payload['payload'])) {
            return $payload;
        }

        $payload['payload'] = $this->sensitiveDataMasker->maskPayload($payload['payload']);

        return $payload;
    }
}
