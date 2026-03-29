<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Infrastructure\Repository;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountTransactionRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawPixRepository;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawRepository;

final class RepositoryContractTest extends TestCase
{
    public function testAccountRepositoryContract(): void
    {
        $reflection = new ReflectionClass(AccountRepository::class);

        self::assertTrue($reflection->isInterface());
        self::assertSame(
            [
                'findById' => '?Tecnofit\\PixWithdrawal\\Core\\Domain\\Entity\\Account',
                'lockById' => '?Tecnofit\\PixWithdrawal\\Core\\Domain\\Entity\\Account',
                'save' => 'void',
            ],
            $this->methodReturnTypes($reflection)
        );
    }

    public function testWithdrawRepositoryContract(): void
    {
        $reflection = new ReflectionClass(WithdrawRepository::class);

        self::assertTrue($reflection->isInterface());
        self::assertSame(
            [
                'findById' => '?Tecnofit\\PixWithdrawal\\Core\\Domain\\Entity\\AccountWithdraw',
                'lockById' => '?Tecnofit\\PixWithdrawal\\Core\\Domain\\Entity\\AccountWithdraw',
                'save' => 'void',
            ],
            $this->methodReturnTypes($reflection)
        );
    }

    public function testWithdrawPixRepositoryContract(): void
    {
        $reflection = new ReflectionClass(WithdrawPixRepository::class);

        self::assertTrue($reflection->isInterface());
        self::assertSame(
            [
                'findByWithdrawId' => '?Tecnofit\\PixWithdrawal\\Core\\Domain\\Entity\\AccountWithdrawPix',
                'save' => 'void',
            ],
            $this->methodReturnTypes($reflection)
        );
    }

    public function testAccountTransactionRepositoryContract(): void
    {
        $reflection = new ReflectionClass(AccountTransactionRepository::class);

        self::assertTrue($reflection->isInterface());
        self::assertSame(
            [
                'existsByWithdrawId' => 'bool',
                'findByWithdrawId' => '?Tecnofit\\PixWithdrawal\\Core\\Domain\\Entity\\AccountTransaction',
                'save' => 'void',
            ],
            $this->methodReturnTypes($reflection)
        );
    }

    /**
     * @return array<string, string>
     */
    private function methodReturnTypes(ReflectionClass $reflection): array
    {
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        usort($methods, static fn (ReflectionMethod $left, ReflectionMethod $right): int => $left->getName() <=> $right->getName());

        $returnTypes = [];

        foreach ($methods as $method) {
            $returnTypes[$method->getName()] = (string) $method->getReturnType();
        }

        return $returnTypes;
    }
}
