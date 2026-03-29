<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared;

enum MetricName: string
{
    case WITHDRAW_QUEUED = 'withdraw.queued';
    case WITHDRAW_PROCESSING_STARTED = 'withdraw.processing.started';
    case WITHDRAW_PROCESSED = 'withdraw.processed';
    case WITHDRAW_FAILED_INSUFFICIENT_FUNDS = 'withdraw.failed.insufficient_funds';
    case WITHDRAW_FAILED_INTERNAL = 'withdraw.failed.internal';
    case WITHDRAW_RETRIED = 'withdraw.retried';
}
