<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Enum;

enum WithdrawMethod: string
{
    case PIX = 'PIX';

    public function isPix(): bool
    {
        return $this === self::PIX;
    }

    public function supportsPixPayload(): bool
    {
        return $this->isPix();
    }

    public function supportsPixKeyType(PixKeyType $pixKeyType): bool
    {
        return in_array($pixKeyType, $this->supportedPixKeyTypes(), true);
    }

    /**
     * @return list<PixKeyType>
     */
    public function supportedPixKeyTypes(): array
    {
        return match ($this) {
            self::PIX => [
                PixKeyType::EMAIL,
            ],
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $method): string => $method->value,
            self::cases()
        );
    }
}
