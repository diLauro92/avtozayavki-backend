<?php

namespace App\Wizard;

use App\Enums\RequestSource;
use App\Services\RequestService;
use App\Wizard\Steps\Step;
use App\Wizard\Steps\StepOutcome;
use App\Wizard\Steps\Transition;
use LogicException;

// Ведет клиента по анкете и про каналы ничего не знает
final readonly class WizardEngine
{
    public function __construct(
        private Scenario $scenario,
        private RequestService $requests,
        private RequestSource $source,
    ) {}

    public function start(): Result
    {
        $first = $this->scenario->first();
        $state = new WizardState($first->id());

        return new Result([new Reply($this->scenario->greeting), $first->prompt($state)], $state);
    }

    public function handle(WizardState $state, Input $input): Result
    {
        $step = $this->scenario->find($state->step);

        // Состояние сохранено до деплоя, в котором шаг переименовали
        if ($step === null) {
            return $this->start();
        }

        $outcome = $step->handle($state, $input);

        return match ($outcome->transition) {
            Transition::Stay => new Result($outcome->replies, $outcome->state),
            Transition::Next => $this->advance($step, $outcome),
            Transition::Submit => $this->submit($outcome->state),
            Transition::Cancel => new Result([new Reply($this->scenario->cancelled)], null),
        };
    }

    private function advance(Step $current, StepOutcome $outcome): Result
    {
        $next = $this->scenario->after($current->id());

        if ($next === null) {
            throw new LogicException("После шага «{$current->id()}» нет следующего: анкета должна заканчиваться подтверждением");
        }

        $state = $outcome->state->withStep($next->id());

        return new Result([...$outcome->replies, $next->prompt($state)], $state);
    }

    private function submit(WizardState $state): Result
    {
        $request = $this->requests->create([...$state->answers, 'source' => $this->source]);

        $text = str_replace('{id}', (string) $request->id, $this->scenario->submitted);

        return new Result([new Reply($text)], null, $request, $state->photos);
    }
}
