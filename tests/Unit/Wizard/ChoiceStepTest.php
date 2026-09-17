<?php

namespace Tests\Unit\Wizard;

use App\Wizard\Button;
use App\Wizard\Input;
use App\Wizard\Steps\ChoiceStep;
use App\Wizard\Steps\Transition;
use App\Wizard\WizardState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChoiceStepTest extends TestCase
{
    public static function allowedValues(): array
    {
        return [
            'первый ряд' => ['today'],
            'второй ряд' => ['emergency'],
        ];
    }

    #[DataProvider('allowedValues')]
    public function test_accepts_button_from_its_rows(string $value): void
    {
        $outcome = $this->step()->handle(new WizardState('urgency'), Input::button($value));

        $this->assertSame(Transition::Next, $outcome->transition);
        $this->assertSame($value, $outcome->state->answer('urgency'));
        $this->assertSame([], $outcome->replies);
    }

    public static function rejectedInputs(): array
    {
        return [
            'кнопка другого вопроса' => [Input::button('photo_done')],
            'подпись текстом' => [Input::text('Сегодня')],
            'значение текстом' => [Input::text('today')],
            'фото' => [Input::photo('abc')],
            'стикер или файл' => [Input::other()],
        ];
    }

    #[DataProvider('rejectedInputs')]
    public function test_rejects_and_asks_again(Input $input): void
    {
        $state = new WizardState('urgency');

        $outcome = $this->step()->handle($state, $input);

        $this->assertSame(Transition::Stay, $outcome->transition);
        $this->assertSame($state, $outcome->state);
        $this->assertCount(1, $outcome->replies);
        $this->assertSame('Выберите кнопкой.', $outcome->replies[0]->text);
    }

    public function test_prompt_shows_buttons_by_rows(): void
    {
        $reply = $this->step()->prompt(new WizardState('urgency'));

        $this->assertSame('Насколько срочно?', $reply->text);
        $this->assertEquals(self::rows(), $reply->buttons);
    }

    public static function summaries(): array
    {
        return [
            'выбранный вариант' => [['urgency' => 'soon'], 'Срочность: 1–2 дня'],
            'нет ответа' => [[], 'Срочность: -'],
            'неизвестное значение' => [['urgency' => 'weird'], 'Срочность: -'],
        ];
    }

    #[DataProvider('summaries')]
    public function test_summary_shows_label_of_chosen_button(array $answers, string $expected): void
    {
        $state = new WizardState('confirm', $answers);

        $this->assertSame($expected, $this->step()->summary($state));
    }

    private static function rows(): array
    {
        return [
            [new Button('Сегодня', 'today'), new Button('1–2 дня', 'soon')],
            [new Button('Планово', 'planned'), new Button('Аварийно', 'emergency')],
        ];
    }

    private function step(): ChoiceStep
    {
        return new ChoiceStep(
            id: 'urgency',
            field: 'urgency',
            label: 'Срочность',
            question: 'Насколько срочно?',
            retry: 'Выберите кнопкой.',
            rows: self::rows(),
        );
    }
}
