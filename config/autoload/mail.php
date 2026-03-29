<?php

declare(strict_types=1);

use Tecnofit\PixWithdrawal\Providers\Config\ProviderEnvironment;

use function Hyperf\Support\env;

$providerEnvironment = ProviderEnvironment::normalize((string) env('APP_ENV', 'prod'));
$defaultMailer = $providerEnvironment === ProviderEnvironment::TEST ? null : 'smtp';
$defaultMailHost = $providerEnvironment === ProviderEnvironment::LOCAL ? 'mailhog' : '127.0.0.1';
$defaultMailPort = $providerEnvironment === ProviderEnvironment::LOCAL ? '1025' : '25';
$defaultFromAddress = $providerEnvironment === ProviderEnvironment::LOCAL
    ? 'no-reply@pix-withdrawal.local'
    : 'no-reply@pix-withdrawal.internal';
$defaultFromName = 'PIX Withdrawal';

return [
    'default' => $defaultMailer,
    'mailers' => $defaultMailer === null ? [] : [
        'smtp' => [
            'transport' => 'smtp',
            'host' => (string) env('MAIL_HOST', $defaultMailHost),
            'port' => (int) env('MAIL_PORT', $defaultMailPort),
            'username' => ($username = trim((string) env('MAIL_USERNAME', ''))) === '' ? null : $username,
            'password' => ($password = trim((string) env('MAIL_PASSWORD', ''))) === '' ? null : $password,
            'encryption' => ($encryption = trim((string) env('MAIL_ENCRYPTION', ''))) === '' ? null : $encryption,
            'from' => [
                'address' => (string) env('MAIL_FROM_ADDRESS', $defaultFromAddress),
                'name' => (string) env('MAIL_FROM_NAME', $defaultFromName),
            ],
            'timeouts' => [
                'connect_seconds' => (int) env('MAIL_CONNECT_TIMEOUT_SECONDS', '5'),
                'read_seconds' => (int) env('MAIL_READ_TIMEOUT_SECONDS', '5'),
            ],
        ],
    ],
];
