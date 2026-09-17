<?php

namespace App\Wizard;

// Сообщение клиенту; адаптер переводит его на язык канала
final readonly class Reply
{
    /**
     * @param list<list<Button>> $buttons ряды кнопок
     */
    public function __construct(
        public string $text,
        public array $buttons = [],
    ) {}
}
