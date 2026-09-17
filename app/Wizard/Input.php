<?php

namespace App\Wizard;

// Ответ клиента, уже переведенный адаптером с языка канала
final readonly class Input
{
    // value: текст, значение кнопки или ссылка на фото в канале
    private function __construct(
        public InputKind $kind,
        public ?string $value = null,
    ) {}

    public static function text(string $text): self
    {
        return new self(InputKind::Text, trim($text));
    }

    public static function button(string $value): self
    {
        return new self(InputKind::Button, $value);
    }

    public static function photo(string $ref): self
    {
        return new self(InputKind::Photo, $ref);
    }

    public static function other(): self
    {
        return new self(InputKind::Other);
    }
}
