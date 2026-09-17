<?php

namespace App\Wizard\Steps;

use App\Wizard\Button;
use App\Wizard\Input;
use App\Wizard\InputKind;
use App\Wizard\Reply;
use App\Wizard\WizardState;

// Согласие на обработку данных: в поле сохраняется редакция документа, а не факт нажатия
final readonly class ConsentStep implements Step
{
    public const ACCEPT = 'consent_accept';

    public function __construct(
        private string $id,
        private string $field,
        private string $version,
        private string $question,
        private string $retry,
        private string $acceptButton = 'Принимаю',
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function prompt(WizardState $state): Reply
    {
        return new Reply($this->question, [[new Button($this->acceptButton, self::ACCEPT)]]);
    }

    public function handle(WizardState $state, Input $input): StepOutcome
    {
        if ($input->kind !== InputKind::Button || $input->value !== self::ACCEPT) {
            return StepOutcome::stay($state, new Reply($this->retry));
        }

        return StepOutcome::next($state->withAnswer($this->field, $this->version));
    }

    // Согласие в сводку не выводим: его уже подтвердили кнопкой
    public function summary(WizardState $state): ?string
    {
        return null;
    }
}
