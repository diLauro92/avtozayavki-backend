<?php

namespace Tests\Unit\Wizard;

use App\Wizard\Input;
use App\Wizard\Steps\TextStep;
use App\Wizard\Steps\Transition;
use App\Wizard\WizardState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TextStepTest extends TestCase
{
    public function test_accepts_text_and_moves_next(): void
    {
        $outcome = $this->carStep()->handle(new WizardState('car'), Input::text('  Lada Vesta '));

        $this->assertSame(Transition::Next, $outcome->transition);
        $this->assertSame('Lada Vesta', $outcome->state->answer('car_info'));
        $this->assertSame([], $outcome->replies);
    }

    public function test_skip_value_clears_answer(): void
    {
        $state = new WizardState('car', ['car_info' => 'старое']);

        $outcome = $this->carStep()->handle($state, Input::text('-'));

        $this->assertSame(Transition::Next, $outcome->transition);
        $this->assertNull($outcome->state->answer('car_info'));
    }

    public static function rejectedInputs(): array
    {
        return [
            'пустой текст' => [Input::text('   ')],
            'кнопка' => [Input::button('today')],
            'фото' => [Input::photo('abc')],
            'стикер или файл' => [Input::other()],
        ];
    }

    #[DataProvider('rejectedInputs')]
    public function test_rejects_non_text_and_asks_again(Input $input): void
    {
        $state = new WizardState('car');

        $outcome = $this->carStep()->handle($state, $input);

        $this->assertSame(Transition::Stay, $outcome->transition);
        $this->assertSame($state, $outcome->state);
        $this->assertCount(1, $outcome->replies);
        $this->assertSame('Напишите текстом.', $outcome->replies[0]->text);
    }

    public function test_cuts_long_text_by_characters(): void
    {
        $outcome = $this->carStep()->handle(new WizardState('car'), Input::text(str_repeat('я', 300)));

        $this->assertSame(str_repeat('я', 255), $outcome->state->answer('car_info'));
    }

    public function test_without_limit_keeps_full_text(): void
    {
        $outcome = $this->problemStep()->handle(new WizardState('problem'), Input::text(str_repeat('я', 300)));

        $this->assertSame(300, mb_strlen($outcome->state->answer('problem')));
    }

    public function test_dash_is_regular_text_without_skip(): void
    {
        $outcome = $this->problemStep()->handle(new WizardState('problem'), Input::text('-'));

        $this->assertSame('-', $outcome->state->answer('problem'));
    }

    public function test_summary_shows_answer_or_dash(): void
    {
        $step = $this->carStep();

        $this->assertSame('Авто: -', $step->summary(new WizardState('confirm')));
        $this->assertSame('Авто: Lada', $step->summary(new WizardState('confirm', ['car_info' => 'Lada'])));
    }

    private function carStep(): TextStep
    {
        return new TextStep(
            id: 'car',
            field: 'car_info',
            label: 'Авто',
            question: 'Марка и модель?',
            retry: 'Напишите текстом.',
            skip: '-',
        );
    }

    private function problemStep(): TextStep
    {
        return new TextStep(
            id: 'problem',
            field: 'problem',
            label: 'Проблема',
            question: 'Опишите проблему.',
            retry: 'Опишите словами.',
            maxLength: null,
        );
    }
}
