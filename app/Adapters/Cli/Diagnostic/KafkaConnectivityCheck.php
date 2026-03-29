<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Diagnostic;

use RdKafka\Conf;
use RdKafka\Producer;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;

final readonly class KafkaConnectivityCheck
{
    public function __construct(private KafkaConfig $kafkaConfig)
    {
    }

    public function run(): KafkaConnectivityCheckResult
    {
        try {
            $producer = new Producer($this->buildConf());
            $registeredBrokers = $producer->addBrokers(implode(',', $this->kafkaConfig->brokers()));

            if ($registeredBrokers < 1) {
                throw new \RuntimeException('Kafka producer could not register any broker from configuration.');
            }

            $metadata = $producer->getMetadata(true, null, $this->kafkaConfig->operationTimeoutMs());
        } catch (\Throwable $throwable) {
            throw new TransientInfrastructureException('Kafka connectivity check failed.', 0, $throwable);
        }

        return new KafkaConnectivityCheckResult(
            clientId: $this->kafkaConfig->clientId(),
            configuredBrokersCount: count($this->kafkaConfig->brokers()),
            discoveredBrokersCount: count($metadata->getBrokers()),
            discoveredTopicsCount: count($metadata->getTopics()),
            originBrokerId: $metadata->getOrigBrokerId(),
            originBrokerName: trim((string) $metadata->getOrigBrokerName()),
        );
    }

    private function buildConf(): Conf
    {
        $conf = new Conf();
        $conf->set('client.id', $this->kafkaConfig->clientId());
        $conf->set('bootstrap.servers', implode(',', $this->kafkaConfig->brokers()));

        return $conf;
    }
}
