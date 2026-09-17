<?php

namespace App\Wizard;

use App\Models\Request as RequestModel;

final readonly class Result
{
    /**
     * @param list<Reply> $replies сообщения клиенту по порядку
     * @param ?WizardState $state null - анкета закончена, состояние удалить
     * @param list<string> $photos фото, которые адаптер скачает к созданной заявке
     */
    public function __construct(
        public array $replies,
        public ?WizardState $state,
        public ?RequestModel $created = null,
        public array $photos = [],
    ) {}
}
