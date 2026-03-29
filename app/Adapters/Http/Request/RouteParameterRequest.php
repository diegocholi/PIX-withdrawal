<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Request;

use InvalidArgumentException;

final class RouteParameterRequest
{
    public function requireUuid(string $parameterName, string $value): string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new InvalidArgumentException(sprintf('The %s path parameter is required.', $parameterName));
        }

        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $normalized)) {
            throw new InvalidArgumentException(sprintf('The %s path parameter must be a valid UUID.', $parameterName));
        }

        return strtolower($normalized);
    }
}
