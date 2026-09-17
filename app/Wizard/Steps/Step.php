<?php

namespace App\Wizard\Steps;

use App\Wizard\Input;
use App\Wizard\Reply;
use App\Wizard\WizardState;

interface Step
{
    // Имя вопроса: по нему состояние помнит, на что ждем ответ
    public function id(): string;

    public function prompt(WizardState $state): Reply;

    public function handle(WizardState $state, Input $input): StepOutcome;

    // Строка сводки перед отправкой; null - шаг в сводку не попадает
    public function summary(WizardState $state): ?string;
}
