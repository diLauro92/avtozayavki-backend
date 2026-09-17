<?php

namespace App\Wizard\Steps;

use App\Wizard\Button;
use App\Wizard\Input;
use App\Wizard\InputKind;
use App\Wizard\Reply;
use App\Wizard\WizardState;

// Вопрос с выбором одного варианта кнопкой: срочность
final readonly class ChoiceStep implements Step
{
    /**
     * @param list<list<Button>> $rows варианты по рядам
     */
    public function __construct(
        private string $id,
        private string $field,
        private string $label,
        private string $question,
        private string $retry,
        private array $rows,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function prompt(WizardState $state): Reply
    {
        return new Reply($this->question, $this->rows);
    }

    public function handle(WizardState $state, Input $input): StepOutcome
    {
        // Кнопка от другого вопроса (старое сообщение в истории) ответом не считается
        if ($input->kind !== InputKind::Button || $this->labelFor($input->value) === null) {
            return StepOutcome::stay($state, new Reply($this->retry));
        }

        return StepOutcome::next($state->withAnswer($this->field, $input->value));
    }

    public function summary(WizardState $state): ?string
    {
        return $this->label . ': ' . ($this->labelFor($state->answer($this->field)) ?? '-');
    }

    private function labelFor(?string $value): ?string
    {
        foreach ($this->rows as $row) {
            foreach ($row as $button) {
                if ($button->value === $value) {
                    return $button->label;
                }
            }
        }

        return null;
    }
}
