<?php

namespace Tests\Unit\Wizard;

use App\Wizard\Button;
use App\Wizard\Input;
use App\Wizard\Steps\ConsentStep;
use App\Wizard\Steps\Transition;
use App\Wizard\WizardState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConsentStepTest extends TestCase
{
    public function test_prompt_shows_single_accept_button(): void
    {
        $reply = $this->step()->prompt(new WizardState('consent'));

        $this->assertSame('Согласны на обработку данных?', $reply->text);
        $this->assertEquals([[new Button('Принимаю', ConsentStep::ACCEPT)]], $reply->buttons);
    }

    public function test_accept_saves_document_version(): void
    {
        $outcome = $this->step()->handle(new WizardState('consent'), Input::button(ConsentStep::ACCEPT));

        $this->assertSame(Transition::Next, $outcome->transition);
        $this->assertSame('2026-09-17', $outcome->state->answer('consent_version'));
        $this->assertSame([], $outcome->replies);
    }

    public static function rejectedInputs(): array
    {
        return [
            'кнопка другого вопроса' => [Input::button('today')],
            'слово «принимаю» текстом' => [Input::text('принимаю')],
            'фото' => [Input::photo('abc')],
            'стикер или файл' => [Input::other()],
        ];
    }

    #[DataProvider('rejectedInputs')]
    public function test_without_accept_wizard_does_not_move(Input $input): void
    {
        $state = new WizardState('consent');

        $outcome = $this->step()->handle($state, $input);

        $this->assertSame(Transition::Stay, $outcome->transition);
        $this->assertSame($state, $outcome->state);
        $this->assertCount(1, $outcome->replies);
        $this->assertSame('Нажмите «Принимаю».', $outcome->replies[0]->text);
        $this->assertNull($outcome->state->answer('consent_version'));
    }

    public function test_is_not_part_of_summary(): void
    {
        $this->assertNull($this->step()->summary(new WizardState('confirm')));
    }

    private function step(): ConsentStep
    {
        return new ConsentStep(
            id: 'consent',
            field: 'consent_version',
            version: '2026-09-17',
            question: 'Согласны на обработку данных?',
            retry: 'Нажмите «Принимаю».',
        );
    }
}
