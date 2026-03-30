<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Diagnostic;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfigProvider;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfigValidator;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfigValidator;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;

final readonly class ConfigValidation
{
    public function __construct(
        private ContainerInterface $container,
        private ConfigInterface $config,
        private ProviderConfigProvider $providerConfigProvider,
        private MySqlConnectionConfigProvider $mySqlConnectionConfigProvider,
        private KafkaConfigValidator $kafkaConfigValidator,
        private MailConfigValidator $mailConfigValidator,
    ) {
    }

    public function validate(): DiagnosticReport
    {
        return new DiagnosticReport([
            $this->validateProviders(),
            $this->validateRuntimes(),
            $this->validateMySql(),
            $this->validateKafka(),
            $this->validateMail(),
        ]);
    }

    private function validateProviders(): DiagnosticCheckResult
    {
        return DiagnosticCheckResult::ok('providers', [
            'environment' => $this->providerConfigProvider->providerEnvironment(),
            'clock_driver' => $this->providerConfigProvider->clockDriver(),
            'uuid_driver' => $this->providerConfigProvider->uuidDriver(),
            'withdraw_duplicate_guard_window_seconds' => $this->providerConfigProvider->withdrawDuplicateGuardWindowSeconds(),
            'observability_driver' => $this->providerConfigProvider->observabilityDriver(),
            'metric_driver' => $this->providerConfigProvider->metricDriver(),
            'domain_event_driver' => $this->providerConfigProvider->domainEventDriver(),
        ]);
    }

    private function validateRuntimes(): DiagnosticCheckResult
    {
        try {
            /** @var array<string, list<class-string>> $requiredBindings */
            $requiredBindings = $this->config->get('providers.runtime.required_bindings', []);

            if ($requiredBindings === []) {
                throw new \InvalidArgumentException(
                    'Provider runtime required bindings configuration must define at least one runtime.'
                );
            }

            $runtimeSummaries = [];

            foreach ($requiredBindings as $runtime => $bindings) {
                if ($bindings === []) {
                    throw new \InvalidArgumentException(sprintf(
                        'Provider runtime "%s" must declare at least one required binding.',
                        $runtime,
                    ));
                }

                foreach ($bindings as $binding) {
                    if (! $this->container->has($binding)) {
                        throw new \InvalidArgumentException(sprintf(
                            'Provider runtime "%s" requires unresolved binding "%s".',
                            $runtime,
                            $binding,
                        ));
                    }

                    $this->container->get($binding);
                }

                $runtimeSummaries[$runtime] = [
                    'binding_count' => count($bindings),
                    'bindings' => array_values($bindings),
                ];
            }
        } catch (\Throwable $throwable) {
            return DiagnosticCheckResult::failed('runtimes', $throwable->getMessage());
        }

        return DiagnosticCheckResult::ok('runtimes', [
            'runtime_count' => count($runtimeSummaries),
            'runtimes' => $runtimeSummaries,
        ]);
    }

    private function validateMySql(): DiagnosticCheckResult
    {
        try {
            $config = $this->mySqlConnectionConfigProvider->defaultConnection();
        } catch (\Throwable $throwable) {
            return DiagnosticCheckResult::failed('mysql', $throwable->getMessage());
        }

        return DiagnosticCheckResult::ok('mysql', [
            'connection' => $config->name(),
            'driver' => $config->driver(),
            'host' => $config->host(),
            'port' => $config->port(),
            'database' => $config->database(),
            'username' => $config->username(),
        ]);
    }

    private function validateKafka(): DiagnosticCheckResult
    {
        try {
            $kafkaConfig = KafkaConfig::fromArray($this->providerConfigProvider->kafka());
            $this->kafkaConfigValidator->validate($kafkaConfig);
            $topics = $kafkaConfig->topics();
            $consumerGroups = $kafkaConfig->consumerGroups();
        } catch (\Throwable $throwable) {
            return DiagnosticCheckResult::failed('kafka', $throwable->getMessage());
        }

        return DiagnosticCheckResult::ok('kafka', [
            'client_id' => $kafkaConfig->clientId(),
            'brokers_count' => count($kafkaConfig->brokers()),
            'required_topic_keys_count' => count($kafkaConfig->requiredTopicKeys()),
            'topics_count' => count($topics),
            'consumer_groups_count' => count($consumerGroups),
            'operation_timeout_ms' => $kafkaConfig->operationTimeoutMs(),
            'flush_timeout_ms' => $kafkaConfig->flushTimeoutMs(),
        ]);
    }

    private function validateMail(): DiagnosticCheckResult
    {
        try {
            $mailConfig = MailConfig::fromArray($this->providerConfigProvider->mail());
            $this->mailConfigValidator->validate($mailConfig);
        } catch (\Throwable $throwable) {
            return DiagnosticCheckResult::failed('mail', $throwable->getMessage());
        }

        return DiagnosticCheckResult::ok('mail', [
            'default_mailer' => $mailConfig->defaultMailer(),
            'transport' => $mailConfig->transport(),
            'host' => $mailConfig->host(),
            'port' => $mailConfig->port(),
            'from_address' => $mailConfig->fromAddress(),
            'from_name' => $mailConfig->fromName(),
            'has_authentication' => $mailConfig->hasAuthentication(),
            'connect_timeout_seconds' => $mailConfig->connectTimeoutSeconds(),
            'read_timeout_seconds' => $mailConfig->readTimeoutSeconds(),
        ]);
    }
}
