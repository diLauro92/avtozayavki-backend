<?php

namespace App\Wizard\Steps;

use App\Wizard\Input;
use App\Wizard\InputKind;
use App\Wizard\Reply;
use App\Wizard\WizardState;

// Вопрос с ответом текстом: имя, авто, описание проблемы
final readonly class TextStep implements Step
{
    public function __construct(
        private string $id,
        private string $field,
        private string $label,
        private string $question,
        private string $retry,
        private ?int $maxLength = 255,
        private ?string $skip = null,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function prompt(WizardState $state): Reply
    {
        return new Reply($this->question);
    }

    public function handle(WizardState $state, Input $input): StepOutcome
    {
        if ($input->kind !== InputKind::Text || $input->value === '') {
            return StepOutcome::stay($state, new Reply($this->retry));
        }

        // Клиент явно пропустил необязательный вопрос
        if ($this->skip !== null && $input->value === $this->skip) {
            return StepOutcome::next($state->withAnswer($this->field, null));
        }

        $value = $this->maxLength === null
            ? $input->value
            : mb_substr($input->value, 0, $this->maxLength);

        return StepOutcome::next($state->withAnswer($this->field, $value));
    }

    public function summary(WizardState $state): ?string
    {
        return $this->label . ': ' . ($state->answer($this->field) ?? '-');
    }
}
