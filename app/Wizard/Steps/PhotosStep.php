<?php

namespace App\Wizard\Steps;

use App\Wizard\Button;
use App\Wizard\Input;
use App\Wizard\InputKind;
use App\Wizard\Reply;
use App\Wizard\WizardState;

// Необязательные фото по одному снимку, до лимита; выход — кнопкой
final readonly class PhotosStep implements Step
{
    public const DONE = 'photo_done';

    public function __construct(
        private string $id,
        private string $label,
        private string $question,
        private int $limit = 10,
        private string $skipButton = 'Без фото',
        private string $doneButton = 'Готово',
        private string $accepted = 'Фото {count} принято. Пришлите ещё или нажмите «Готово».',
        private string $limitReached = 'Уже достаточно фото, больше не нужно. Нажмите «Готово».',
        private string $retry = 'Пришлите именно фото, не файлом. Или нажмите кнопку под сообщением выше.',
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function prompt(WizardState $state): Reply
    {
        return new Reply($this->question, $this->keyboard($state));
    }

    public function handle(WizardState $state, Input $input): StepOutcome
    {
        return match ($input->kind) {
            InputKind::Photo => $this->addPhoto($state, $input->value),
            // Чужая кнопка из истории: молча ждем дальше, как сейчас
            InputKind::Button => $input->value === self::DONE
                ? StepOutcome::next($state)
                : StepOutcome::stay($state),
            default => StepOutcome::stay($state, new Reply($this->retry)),
        };
    }

    public function summary(WizardState $state): ?string
    {
        return $this->label . ': ' . (count($state->photos) ?: '-');
    }

    private function addPhoto(WizardState $state, string $ref): StepOutcome
    {
        if (count($state->photos) >= $this->limit) {
            return StepOutcome::stay($state, new Reply($this->limitReached));
        }

        $updated = $state->withPhoto($ref);
        $text = str_replace('{count}', (string) count($updated->photos), $this->accepted);

        return StepOutcome::stay($updated, new Reply($text, $this->keyboard($updated)));
    }

    /**
     * @return list<list<Button>>
     */
    private function keyboard(WizardState $state): array
    {
        $label = $state->photos === [] ? $this->skipButton : $this->doneButton;

        return [[new Button($label, self::DONE)]];
    }
}
