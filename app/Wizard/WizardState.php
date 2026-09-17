<?php

namespace App\Wizard;

final readonly class WizardState
{
    /**
     * @param string $step вопрос, на который ждем ответ
     * @param array<string, ?string> $answers ответы по полям заявки
     * @param list<string> $photos ссылки на фото в канале
     */
    public function __construct(
        public string $step,
        public array $answers = [],
        public array $photos = [],
    ) {}

    public function withStep(string $step): self
    {
        return new self($step, $this->answers, $this->photos);
    }

    public function withAnswer(string $field, ?string $value): self
    {
        return new self($this->step, [...$this->answers, $field => $value], $this->photos);
    }

    public function withPhoto(string $ref): self
    {
        return new self($this->step, $this->answers, [...$this->photos, $ref]);
    }

    public function answer(string $field): ?string
    {
        return $this->answers[$field] ?? null;
    }

    public function toArray(): array
    {
        return [
            'step' => $this->step,
            'answers' => $this->answers,
            'photos' => $this->photos,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['step'], $data['answers'] ?? [], $data['photos'] ?? []);
    }
}
