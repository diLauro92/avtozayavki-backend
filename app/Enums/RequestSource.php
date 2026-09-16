<?php

namespace App\Enums;

enum RequestSource: string
{
    case Telegram = 'telegram';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Telegram => 'Telegram',
            self::Manual => 'Ручной ввод',
        };
    }

    // Данные клиента уже прошли через иностранный сервис
    public function isForeign(): bool
    {
        return match ($this) {
            self::Telegram => true,
            self::Manual => false,
        };
    }
}
