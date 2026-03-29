<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Diagnostic;

use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;

final readonly class SystemCheckAll
{
    public function __construct(
        private MySqlConnectivityCheck $mySqlConnectivityCheck,
        private KafkaConnectivityCheck $kafkaConnectivityCheck,
        private MailConnectivityCheck $mailConnectivityCheck,
    ) {
    }

    public function run(): DiagnosticReport
    {
        return new DiagnosticReport([
            $this->runMySqlCheck(),
            $this->runKafkaCheck(),
            $this->runMailCheck(),
        ]);
    }

    private function runMySqlCheck(): DiagnosticCheckResult
    {
        try {
            $result = $this->mySqlConnectivityCheck->run();
        } catch (TransientInfrastructureException $exception) {
            return DiagnosticCheckResult::failed('mysql', $exception->getMessage());
        }

        return DiagnosticCheckResult::ok('mysql', [
            'connection' => $result->connectionName(),
            'driver' => $result->driver(),
            'host' => $result->host(),
            'port' => $result->port(),
            'database' => $result->database(),
            'ping' => $result->ping(),
            'server_version' => $result->serverVersion(),
        ]);
    }

    private function runKafkaCheck(): DiagnosticCheckResult
    {
        try {
            $result = $this->kafkaConnectivityCheck->run();
        } catch (TransientInfrastructureException $exception) {
            return DiagnosticCheckResult::failed('kafka', $exception->getMessage());
        }

        return DiagnosticCheckResult::ok('kafka', [
            'client_id' => $result->clientId(),
            'configured_brokers_count' => $result->configuredBrokersCount(),
            'discovered_brokers_count' => $result->discoveredBrokersCount(),
            'discovered_topics_count' => $result->discoveredTopicsCount(),
            'origin_broker_id' => $result->originBrokerId(),
            'origin_broker_name' => $result->originBrokerName(),
        ]);
    }

    private function runMailCheck(): DiagnosticCheckResult
    {
        try {
            $result = $this->mailConnectivityCheck->run();
        } catch (TransientInfrastructureException $exception) {
            return DiagnosticCheckResult::failed('mail', $exception->getMessage());
        }

        return DiagnosticCheckResult::ok('mail', [
            'default_mailer' => $result->defaultMailer(),
            'transport' => $result->transport(),
            'host' => $result->host(),
            'port' => $result->port(),
            'from_address' => $result->fromAddress(),
            'recipient' => $result->recipient(),
            'subject' => $result->subject(),
        ]);
    }
}
