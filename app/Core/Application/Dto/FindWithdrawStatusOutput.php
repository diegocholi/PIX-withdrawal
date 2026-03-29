<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Dto;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\Traceable;

interface FindWithdrawStatusOutput extends Traceable
{
    public function withdrawId(): string;

    public function status(): string;

    public function amount(): string;

    public function method(): string;

    public function scheduled(): bool;

    public function scheduledFor(): ?string;

    public function processedAt(): ?string;

    public function errorReason(): ?string;

    public function pixKeyType(): ?string;

    public function pixKeyMasked(): ?string;

}
