<?php

namespace App\Wizard\Steps;

use App\Wizard\Button;
use App\Wizard\Input;
use App\Wizard\InputKind;
use App\Wizard\Reply;
use App\Wizard\WizardState;

// Сводка ответов и решение клиента: отправить или отменить
final readonly class ConfirmStep implements Step
{
    public const SEND = 'confirm';
    public const CANCEL = 'cancel';

    /**
     * @param list<Step> $summaryOf шаги, чьи ответы показать, в порядке показа
     */
    public function __construct(
        private string $id,
        private array $summaryOf,
        private string $title = 'Проверьте заявку:',
        private string $sendButton = '✅ Отправить',
        private string $cancelButton = '❌ Отменить',
        private string $retry = 'Нажмите «Отправить» или «Отменить» под заявкой.',
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function prompt(WizardState $state): Reply
    {
        $lines = [$this->title, ''];

        foreach ($this->summaryOf as $step) {
            $line = $step->summary($state);

            if ($line !== null) {
                $lines[] = $line;
            }
        }

        $buttons = [[
            new Button($this->sendButton, self::SEND),
            new Button($this->cancelButton, self::CANCEL),
        ]];

        return new Reply(implode("\n", $lines), $buttons);
    }

    public function handle(WizardState $state, Input $input): StepOutcome
    {
        if ($input->kind === InputKind::Button && $input->value === self::SEND) {
            return StepOutcome::submit($state);
        }

        if ($input->kind === InputKind::Button && $input->value === self::CANCEL) {
            return StepOutcome::cancel($state);
        }

        return StepOutcome::stay($state, new Reply($this->retry));
    }

    public function summary(WizardState $state): ?string
    {
        return null;
    }
}
