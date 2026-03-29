<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Identifier;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawCommandValidator;
use Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawInput;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\CorrelationIdGenerator;
use Tecnofit\PixWithdrawal\Plugins\Identifier\DeterministicWithdrawDuplicateGuardFingerprintGenerator;
use Tecnofit\PixWithdrawal\Plugins\Identifier\RandomWithdrawIdempotencyKeyGenerator;
use Tecnofit\PixWithdrawal\Plugins\Identifier\RandomUuidGenerator;

final class RandomUuidGeneratorTest extends TestCase
{
    public function testGenerateReturnsUuidVersionFour(): void
    {
        $generator = new RandomUuidGenerator();
        $uuid = $generator->generate();

        self::assertInstanceOf(CorrelationIdGenerator::class, $generator);
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid
        );
    }

    public function testRandomWithdrawIdempotencyKeyGeneratorProducesValidUniqueKeys(): void
    {
        $generator = new RandomWithdrawIdempotencyKeyGenerator();

        $firstKey = $generator->generate($this->validatedCreateWithdrawData('corr-1'));
        $secondKey = $generator->generate($this->validatedCreateWithdrawData('corr-2'));

        self::assertNotSame($firstKey, $secondKey);
        self::assertTrue($generator->isValid($firstKey));
        self::assertTrue($generator->isValid($secondKey));
        self::assertStringStartsWith('withdraw:req:v1:', $firstKey);
    }

    public function testDeterministicWithdrawDuplicateGuardFingerprintUsesBusinessFieldsOnly(): void
    {
        $generator = new DeterministicWithdrawDuplicateGuardFingerprintGenerator();

        $firstFingerprint = $generator->generate($this->validatedCreateWithdrawData('corr-1', 'USER@EXAMPLE.COM'));
        $secondFingerprint = $generator->generate($this->validatedCreateWithdrawData('corr-2', 'user@example.com'));

        self::assertSame($firstFingerprint, $secondFingerprint);
        self::assertStringStartsWith('withdraw:guard:v1:', $firstFingerprint);
    }

    private function validatedCreateWithdrawData(
        string $correlationId,
        string $pixKey = 'user@example.com',
    ): \Tecnofit\PixWithdrawal\Core\Application\Dto\ValidatedCreateWithdrawData {
        $validator = new CreateWithdrawCommandValidator();
        $clock = new class implements \Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-03-28T10:00:00-03:00');
            }
        };

        return $validator->validate(
            new CreateWithdrawInput('acc-1', $correlationId, 'PIX', 'EMAIL', $pixKey, '25.00'),
            $clock,
        );
    }
}
