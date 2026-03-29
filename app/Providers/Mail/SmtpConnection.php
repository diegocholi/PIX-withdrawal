<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

interface SmtpConnection
{
    public function readLine(): ?string;

    public function write(string $payload): void;

    public function enableTls(): void;

    public function didTimeOut(): bool;

    public function close(): void;
}
