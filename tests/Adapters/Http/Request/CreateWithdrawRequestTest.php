<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Request;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\CreateWithdrawRequest;

final class CreateWithdrawRequestTest extends TestCase
{
    public function testValidateBuildsPublicPayloadForImmediateWithdraw(): void
    {
        $request = new CreateWithdrawRequest();

        $payload = $request->validate([
            'amount' => '150.25',
            'method' => ' pix ',
            'pix' => [
                'key_type' => ' email ',
                'key' => 'user@example.com',
            ],
            'schedule' => null,
        ]);

        self::assertSame(
            [
                'amount' => '150.25',
                'method' => 'PIX',
                'pix' => [
                    'key_type' => 'EMAIL',
                    'key' => 'user@example.com',
                ],
                'schedule' => null,
            ],
            $payload->toArray()
        );
    }

    public function testValidateBuildsPublicPayloadForScheduledWithdraw(): void
    {
        $request = new CreateWithdrawRequest();

        $payload = $request->validate([
            'amount' => '150.25',
            'method' => 'PIX',
            'pix' => [
                'key_type' => 'EMAIL',
                'key' => 'scheduled@example.com',
            ],
            'schedule' => [
                'at' => '2026-04-01T12:00:00+00:00',
            ],
        ]);

        self::assertSame('2026-04-01T12:00:00+00:00', $payload->toArray()['schedule']['at']);
    }

    public function testValidateRejectsMissingPixObject(): void
    {
        $request = new CreateWithdrawRequest();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The withdraw request payload must contain a pix object.');

        $request->validate([
            'amount' => '150.25',
            'method' => 'PIX',
        ]);
    }

    public function testValidateRejectsNonObjectSchedule(): void
    {
        $request = new CreateWithdrawRequest();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The schedule field must be an object or null.');

        $request->validate([
            'amount' => '150.25',
            'method' => 'PIX',
            'pix' => [
                'key_type' => 'EMAIL',
                'key' => 'user@example.com',
            ],
            'schedule' => '2026-04-01T12:00:00+00:00',
        ]);
    }

    public function testValidateRejectsUnsupportedMethod(): void
    {
        $request = new CreateWithdrawRequest();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "method" field must be "PIX", "TED" given.');

        $request->validate([
            'amount' => '150.25',
            'method' => 'TED',
            'pix' => [
                'key_type' => 'EMAIL',
                'key' => 'user@example.com',
            ],
        ]);
    }

    public function testValidateRejectsUnsupportedPixKeyType(): void
    {
        $request = new CreateWithdrawRequest();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "pix.key_type" field must be "EMAIL", "PHONE" given.');

        $request->validate([
            'amount' => '150.25',
            'method' => 'PIX',
            'pix' => [
                'key_type' => 'PHONE',
                'key' => '+5511999999999',
            ],
        ]);
    }

    public function testValidateRejectsInvalidScheduleAtFormat(): void
    {
        $request = new CreateWithdrawRequest();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "schedule.at" field must use RFC 3339 format, "2026-04-01 12:00:00" given.');

        $request->validate([
            'amount' => '150.25',
            'method' => 'PIX',
            'pix' => [
                'key_type' => 'EMAIL',
                'key' => 'user@example.com',
            ],
            'schedule' => [
                'at' => '2026-04-01 12:00:00',
            ],
        ]);
    }

    public function testValidateRejectsAmountWithoutTwoFractionDigits(): void
    {
        $request = new CreateWithdrawRequest();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "amount" field must be a positive decimal with two fraction digits, "150.2" given.');

        $request->validate([
            'amount' => '150.2',
            'method' => 'PIX',
            'pix' => [
                'key_type' => 'EMAIL',
                'key' => 'user@example.com',
            ],
        ]);
    }

    public function testValidateRejectsAmountEqualToZero(): void
    {
        $request = new CreateWithdrawRequest();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "amount" field must be greater than zero, "0.00" given.');

        $request->validate([
            'amount' => '0.00',
            'method' => 'PIX',
            'pix' => [
                'key_type' => 'EMAIL',
                'key' => 'user@example.com',
            ],
        ]);
    }

    public function testValidateRejectsEmptyRequiredStringField(): void
    {
        $request = new CreateWithdrawRequest();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "method" field must be a non-empty string.');

        $request->validate([
            'amount' => '150.25',
            'method' => '   ',
            'pix' => [
                'key_type' => 'EMAIL',
                'key' => 'user@example.com',
            ],
        ]);
    }
}
