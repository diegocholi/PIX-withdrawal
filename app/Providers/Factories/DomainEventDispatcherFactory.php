<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;
use Tecnofit\PixWithdrawal\Providers\Kafka\DomainEventKafkaMessageMapper;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaDomainEventDispatcher;
use Tecnofit\PixWithdrawal\Providers\Support\HyperfDomainEventDispatcher;
use Tecnofit\PixWithdrawal\Providers\Support\NullDomainEventDispatcher;

final readonly class DomainEventDispatcherFactory
{
    public function __construct(
        private ProviderConfigProvider $providerConfigProvider,
        private EventDispatcherInterface $eventDispatcher,
        private KafkaProducerFactory $kafkaProducerFactory,
        private DomainEventKafkaMessageMapper $domainEventKafkaMessageMapper,
    ) {
    }

    public function create(): DomainEventDispatcher
    {
        $driver = $this->providerConfigProvider->domainEventDriver();

        return match ($driver) {
            'kafka' => new KafkaDomainEventDispatcher(
                fn () => $this->kafkaProducerFactory->create('domain_events'),
                $this->domainEventKafkaMessageMapper,
            ),
            'framework' => new HyperfDomainEventDispatcher($this->eventDispatcher),
            'null' => new NullDomainEventDispatcher(),
            default => throw new \InvalidArgumentException(sprintf(
                'Unsupported providers.domain_events.driver "%s".',
                $driver
            )),
        };
    }
}
