<?php

namespace App\Wizard\Steps;

use App\Wizard\Reply;
use App\Wizard\WizardState;

final readonly class StepOutcome
{
    /**
     * @param list<Reply> $replies
     */
    private function __construct(
        public Transition $transition,
        public WizardState $state,
        public array $replies,
    ) {}

    // Ответ не принят или ждем еще: остаемся на этом вопросе
    public static function stay(WizardState $state, Reply ...$replies): self
    {
        return new self(Transition::Stay, $state, $replies);
    }

    // Ответ принят: движок задаст следующий вопрос
    public static function next(WizardState $state, Reply ...$replies): self
    {
        return new self(Transition::Next, $state, $replies);
    }

    public static function submit(WizardState $state): self
    {
        return new self(Transition::Submit, $state, []);
    }

    public static function cancel(WizardState $state): self
    {
        return new self(Transition::Cancel, $state, []);
    }
}
