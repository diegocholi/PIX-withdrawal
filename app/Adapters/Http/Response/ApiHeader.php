<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Response;

final class ApiHeader
{
    public const ACCEPT = 'Accept';
    public const CONTENT_TYPE = 'Content-Type';
    public const CORRELATION_ID = 'X-Correlation-Id';
    public const LOCATION = 'Location';

    private function __construct()
    {
    }
}
