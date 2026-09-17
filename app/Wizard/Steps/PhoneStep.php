<?php

namespace App\Wizard\Steps;

use App\Support\Phone;
use App\Wizard\Input;
use App\Wizard\InputKind;
use App\Wizard\Reply;
use App\Wizard\WizardState;

// Вопрос с номером телефона: принимает любой привычный формат, хранит 7XXXXXXXXXX
final readonly class PhoneStep implements Step
{
    public function __construct(
        private string $id,
        private string $field,
        private string $label,
        private string $question,
        private string $retry,
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
        // Только текст: normalize выбрасывает все не цифры  собирает номер из чего угодно
        $phone = $input->kind === InputKind::Text ? Phone::normalize($input->value) : null;

        if ($phone === null) {
            return StepOutcome::stay($state, new Reply($this->retry));
        }

        return StepOutcome::next($state->withAnswer($this->field, $phone));
    }

    public function summary(WizardState $state): ?string
    {
        return $this->label . ': ' . ($state->answer($this->field) ?? '-');
    }
}
