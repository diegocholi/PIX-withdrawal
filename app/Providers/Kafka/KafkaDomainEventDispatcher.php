<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;

final readonly class KafkaDomainEventDispatcher implements DomainEventDispatcher
{
    /**
     * @param \Closure(): KafkaMessageProducer $messageProducerFactory
     */
    public function __construct(
        private \Closure $messageProducerFactory,
        private DomainEventKafkaMessageMapper $messageMapper,
    ) {
    }

    public function dispatch(DomainEvent $event): void
    {
        $producer = ($this->messageProducerFactory)();

        foreach ($this->messageMapper->mapAll($event) as $record) {
            $producer->publish($record);
        }
    }
}
