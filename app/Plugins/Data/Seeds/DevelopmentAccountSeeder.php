<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Seeds;

use DateTimeImmutable;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountRepository;

final readonly class DevelopmentAccountSeeder
{
    public function __construct(
        private AccountRepository $accountRepository,
    ) {
    }

    public function seed(): void
    {
        foreach ($this->accounts() as $account) {
            $this->accountRepository->save($account);
        }
    }

    /**
     * @return list<Account>
     */
    private function accounts(): array
    {
        $createdAt = new DateTimeImmutable('2026-03-29 12:00:00+00:00');

        return [
            Account::reconstitute(
                '11111111-1111-4111-8111-111111111111',
                'Seed Immediate Success Wallet',
                Money::fromDecimal('849.25'),
                $createdAt,
                $createdAt,
            ),
            Account::reconstitute(
                '22222222-2222-4222-8222-222222222222',
                'Seed Scheduled Pending Wallet',
                Money::fromDecimal('250.50'),
                $createdAt,
                $createdAt,
            ),
            Account::reconstitute(
                '33333333-3333-4333-8333-333333333333',
                'Seed Scheduled Insufficient Wallet',
                Money::fromDecimal('40.00'),
                $createdAt,
                $createdAt,
            ),
            Account::reconstitute(
                '44444444-4444-4444-8444-444444444444',
                'Seed Empty Balance Wallet',
                Money::fromDecimal('0.00'),
                $createdAt,
                $createdAt,
            ),
        ];
    }
}
