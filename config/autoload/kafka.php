<?php

declare(strict_types=1);

use Tecnofit\PixWithdrawal\Providers\Config\ProviderEnvironment;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;

use function Hyperf\Support\env;

$providerEnvironment = ProviderEnvironment::normalize((string) env('APP_ENV', 'prod'));
$defaultBrokers = match ($providerEnvironment) {
    ProviderEnvironment::LOCAL => 'kafka:19092',
    ProviderEnvironment::TEST => '',
    default => '127.0.0.1:9092',
};

$brokers = array_values(
    array_filter(
        array_map(
            static fn (string $broker): string => trim($broker),
            explode(',', (string) env('KAFKA_BROKERS', $defaultBrokers))
        ),
        static fn (string $broker): bool => $broker !== ''
    )
);

return [
    'brokers' => $brokers,
    'client_id' => sprintf('pix-withdrawal-%s', $providerEnvironment),
    'consumer_groups' => [
        'withdraw' => [
            'group_id' => sprintf('pix-withdrawal-%s-withdraw', $providerEnvironment),
            'auto.offset.reset' => $providerEnvironment === ProviderEnvironment::LOCAL ? 'earliest' : 'latest',
        ],
        'notification' => [
            'group_id' => sprintf('pix-withdrawal-%s-notification', $providerEnvironment),
            'auto.offset.reset' => $providerEnvironment === ProviderEnvironment::LOCAL ? 'earliest' : 'latest',
        ],
    ],
    'operation_timeout_ms' => 1000,
    'producers' => [
        'domain_events' => [
            'acks' => $providerEnvironment === ProviderEnvironment::LOCAL ? '1' : 'all',
        ],
    ],
    'flush_timeout_ms' => 5000,
    'required_topic_keys' => [
        KafkaConfig::TOPIC_WITHDRAW_PROCESS,
        KafkaConfig::TOPIC_WITHDRAW_SUCCEEDED,
        KafkaConfig::TOPIC_WITHDRAW_FAILED,
        KafkaConfig::TOPIC_NOTIFICATION_EMAIL_WITHDRAW,
    ],
    'topics' => [
        KafkaConfig::TOPIC_WITHDRAW_PROCESS => 'withdraw.process',
        KafkaConfig::TOPIC_WITHDRAW_SUCCEEDED => 'withdraw.succeeded',
        KafkaConfig::TOPIC_WITHDRAW_FAILED => 'withdraw.failed',
        KafkaConfig::TOPIC_NOTIFICATION_EMAIL_WITHDRAW => 'notification.email.withdraw',
    ],
];
