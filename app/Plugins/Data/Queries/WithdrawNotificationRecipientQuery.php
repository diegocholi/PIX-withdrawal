<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Queries;

use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\WithdrawNotificationRecipientQuery as WithdrawNotificationRecipientQueryContract;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;

final readonly class WithdrawNotificationRecipientQuery implements WithdrawNotificationRecipientQueryContract
{
    public function __construct(private MySqlConnectionConfig $connectionConfig)
    {
    }

    public function findRecipientByWithdrawId(string $withdrawId): ?string
    {
        $recipient = Db::connection($this->connectionConfig->name())
            ->table('account_withdraw_pix')
            ->where('account_withdraw_id', trim($withdrawId))
            ->where('type', PixKeyType::EMAIL->value)
            ->value('key');

        if (! is_scalar($recipient)) {
            return null;
        }

        $normalizedRecipient = trim((string) $recipient);

        return $normalizedRecipient === '' ? null : mb_strtolower($normalizedRecipient);
    }
}
