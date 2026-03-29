<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

use DateTimeImmutable;
use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\EventPayloadSerializer;

final readonly class WithdrawNotificationRenderer
{
    public function __construct(
        private WithdrawNotificationTemplate $template,
        private EventPayloadSerializer $eventPayloadSerializer,
    ) {
    }

    public function render(DomainEvent $event): string
    {
        if ($event->eventName() !== 'withdraw.processed') {
            throw InvalidWithdrawNotificationEvent::unsupportedEvent($event->eventName());
        }

        $serializedEvent = $this->eventPayloadSerializer->serialize($event);
        $payload = is_array($serializedEvent['payload'] ?? null) ? $serializedEvent['payload'] : [];

        return strtr($this->template->body(), [
            '{{withdraw_processed_at}}' => $this->formatOccurredAt($this->requiredString($serializedEvent, 'occurred_at')),
            '{{withdraw_amount}}' => $this->formatAmount($this->requiredString($payload, 'amount')),
            '{{pix_key_type}}' => $this->requiredString($payload, 'pix_key_type'),
            '{{pix_key_masked}}' => $this->requiredString($payload, 'pix_key_masked'),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredString(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;

        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        throw InvalidWithdrawNotificationEvent::missingField($field);
    }

    private function formatOccurredAt(string $occurredAt): string
    {
        $date = new DateTimeImmutable($occurredAt);

        return $date->format('d/m/Y H:i:s');
    }

    private function formatAmount(string $amount): string
    {
        $normalized = trim($amount);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            return $normalized;
        }

        [$integerPart, $decimalPart] = array_pad(explode('.', $normalized, 2), 2, '00');
        $groupedIntegerPart = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $integerPart);

        return sprintf('%s,%s', $groupedIntegerPart, str_pad($decimalPart, 2, '0'));
    }
}
