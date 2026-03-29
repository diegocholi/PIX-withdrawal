<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared;

final class ErrorCode
{
    public const CORE_UNEXPECTED_ERROR = 'core.unexpected_error';

    public const ACCOUNT_EMPTY_ID = 'account.empty_id';
    public const ACCOUNT_EMPTY_NAME = 'account.empty_name';
    public const ACCOUNT_INCONSISTENT_TIMESTAMPS = 'account.inconsistent_timestamps';
    public const ACCOUNT_INSUFFICIENT_BALANCE = 'account.insufficient_balance';
    public const ACCOUNT_NOT_FOUND = 'account.not_found';
    public const ACCOUNT_STALE_UPDATE = 'account.stale_update';

    public const ACCOUNT_TRANSACTION_EMPTY_FIELD = 'account_transaction.empty_field';
    public const ACCOUNT_TRANSACTION_INCONSISTENT_BALANCE_FLOW = 'account_transaction.inconsistent_balance_flow';
    public const ACCOUNT_TRANSACTION_ZERO_AMOUNT = 'account_transaction.zero_amount';

    public const ACCOUNT_WITHDRAW_EMPTY_FIELD = 'account_withdraw.empty_field';
    public const ACCOUNT_WITHDRAW_INCONSISTENT_STATE = 'account_withdraw.inconsistent_state';
    public const ACCOUNT_WITHDRAW_INVALID_STATUS_TRANSITION = 'account_withdraw.invalid_status_transition';
    public const ACCOUNT_WITHDRAW_NEGATIVE_RETRY_COUNT = 'account_withdraw.negative_retry_count';

    public const ACCOUNT_WITHDRAW_PIX_EMPTY_WITHDRAW_ID = 'account_withdraw_pix.empty_withdraw_id';
    public const ACCOUNT_WITHDRAW_PIX_INCONSISTENT_TIMESTAMPS = 'account_withdraw_pix.inconsistent_timestamps';
    public const ACCOUNT_WITHDRAW_PIX_UNSUPPORTED_METHOD = 'account_withdraw_pix.unsupported_method';
    public const ACCOUNT_WITHDRAW_PIX_UNSUPPORTED_PIX_KEY_TYPE = 'account_withdraw_pix.unsupported_pix_key_type';

    public const CREATE_WITHDRAW_COMMAND_EMPTY_FIELD = 'create_withdraw_command.empty_field';
    public const CREATE_WITHDRAW_COMMAND_INVALID_METHOD = 'create_withdraw_command.invalid_method';
    public const CREATE_WITHDRAW_COMMAND_INVALID_PIX_KEY_TYPE = 'create_withdraw_command.invalid_pix_key_type';

    public const MONEY_INSUFFICIENT_AMOUNT = 'money.insufficient_amount';
    public const MONEY_INVALID_FORMAT = 'money.invalid_format';
    public const MONEY_NEGATIVE_AMOUNT = 'money.negative_amount';

    public const PIX_KEY_INVALID_EMAIL = 'pix_key.invalid_email';
    public const PIX_KEY_UNSUPPORTED_TYPE = 'pix_key.unsupported_type';

    public const SCHEDULE_AT_INVALID_FORMAT = 'schedule_at.invalid_format';
    public const SCHEDULE_AT_PAST_DATE = 'schedule_at.past_date';

    public const TRACE_EMPTY_CORRELATION_ID = 'trace.empty_correlation_id';
    public const TRACE_INVALID_METADATA_KEY = 'trace.invalid_metadata_key';
    public const TRACE_UNSUPPORTED_METADATA_VALUE = 'trace.unsupported_metadata_value';

    public const WITHDRAW_NOT_FOUND = 'withdraw.not_found';
    public const WITHDRAW_DUPLICATE_REQUEST_BLOCKED = 'withdraw.duplicate_request_blocked';
    public const WITHDRAW_INSUFFICIENT_FUNDS = 'withdraw.insufficient_funds';
    public const WITHDRAW_NOT_PROCESSABLE = 'withdraw.not_processable';
    public const WITHDRAW_UNSUPPORTED_METHOD = 'withdraw.unsupported_method';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::CORE_UNEXPECTED_ERROR,
            self::ACCOUNT_EMPTY_ID,
            self::ACCOUNT_EMPTY_NAME,
            self::ACCOUNT_INCONSISTENT_TIMESTAMPS,
            self::ACCOUNT_INSUFFICIENT_BALANCE,
            self::ACCOUNT_NOT_FOUND,
            self::ACCOUNT_STALE_UPDATE,
            self::ACCOUNT_TRANSACTION_EMPTY_FIELD,
            self::ACCOUNT_TRANSACTION_INCONSISTENT_BALANCE_FLOW,
            self::ACCOUNT_TRANSACTION_ZERO_AMOUNT,
            self::ACCOUNT_WITHDRAW_EMPTY_FIELD,
            self::ACCOUNT_WITHDRAW_INCONSISTENT_STATE,
            self::ACCOUNT_WITHDRAW_INVALID_STATUS_TRANSITION,
            self::ACCOUNT_WITHDRAW_NEGATIVE_RETRY_COUNT,
            self::ACCOUNT_WITHDRAW_PIX_EMPTY_WITHDRAW_ID,
            self::ACCOUNT_WITHDRAW_PIX_INCONSISTENT_TIMESTAMPS,
            self::ACCOUNT_WITHDRAW_PIX_UNSUPPORTED_METHOD,
            self::ACCOUNT_WITHDRAW_PIX_UNSUPPORTED_PIX_KEY_TYPE,
            self::CREATE_WITHDRAW_COMMAND_EMPTY_FIELD,
            self::CREATE_WITHDRAW_COMMAND_INVALID_METHOD,
            self::CREATE_WITHDRAW_COMMAND_INVALID_PIX_KEY_TYPE,
            self::MONEY_INSUFFICIENT_AMOUNT,
            self::MONEY_INVALID_FORMAT,
            self::MONEY_NEGATIVE_AMOUNT,
            self::PIX_KEY_INVALID_EMAIL,
            self::PIX_KEY_UNSUPPORTED_TYPE,
            self::SCHEDULE_AT_INVALID_FORMAT,
            self::SCHEDULE_AT_PAST_DATE,
            self::TRACE_EMPTY_CORRELATION_ID,
            self::TRACE_INVALID_METADATA_KEY,
            self::TRACE_UNSUPPORTED_METADATA_VALUE,
            self::WITHDRAW_NOT_FOUND,
            self::WITHDRAW_DUPLICATE_REQUEST_BLOCKED,
            self::WITHDRAW_INSUFFICIENT_FUNDS,
            self::WITHDRAW_NOT_PROCESSABLE,
            self::WITHDRAW_UNSUPPORTED_METHOD,
        ];
    }
}
