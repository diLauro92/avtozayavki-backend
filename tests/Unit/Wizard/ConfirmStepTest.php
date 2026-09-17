<?php

namespace Tests\Unit\Wizard;

use App\Wizard\Button;
use App\Wizard\Input;
use App\Wizard\Reply;
use App\Wizard\Steps\ConfirmStep;
use App\Wizard\Steps\Step;
use App\Wizard\Steps\StepOutcome;
use App\Wizard\Steps\Transition;
use App\Wizard\WizardState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfirmStepTest extends TestCase
{
    public function test_prompt_lists_summaries_in_given_order_skipping_nulls(): void
    {
        $step = new ConfirmStep(id: 'confirm', summaryOf: [
            $this->fixedSummary('Срочность: Сегодня'),
            $this->fixedSummary(null),
            $this->fixedSummary('Фото: 2'),
        ]);

        $reply = $step->prompt(new WizardState('confirm'));

        $this->assertSame("Проверьте заявку:\n\nСрочность: Сегодня\nФото: 2", $reply->text);
        $this->assertEquals(
            [[new Button('✅ Отправить', ConfirmStep::SEND), new Button('❌ Отменить', ConfirmStep::CANCEL)]],
            $reply->buttons,
        );
    }

    public static function decisions(): array
    {
        return [
            'отправить' => [ConfirmStep::SEND, Transition::Submit],
            'отменить' => [ConfirmStep::CANCEL, Transition::Cancel],
        ];
    }

    #[DataProvider('decisions')]
    public function test_button_decides(string $value, Transition $expected): void
    {
        $state = new WizardState('confirm', ['phone' => '79161234567']);

        $outcome = $this->step()->handle($state, Input::button($value));

        $this->assertSame($expected, $outcome->transition);
        $this->assertSame($state, $outcome->state);
        $this->assertSame([], $outcome->replies);
    }

    public static function rejectedInputs(): array
    {
        return [
            'кнопка другого вопроса' => [Input::button('today')],
            'текст «отправить»' => [Input::text('отправить')],
            'фото' => [Input::photo('abc')],
            'стикер или файл' => [Input::other()],
        ];
    }

    #[DataProvider('rejectedInputs')]
    public function test_rejects_and_asks_again(Input $input): void
    {
        $state = new WizardState('confirm');

        $outcome = $this->step()->handle($state, $input);

        $this->assertSame(Transition::Stay, $outcome->transition);
        $this->assertSame($state, $outcome->state);
        $this->assertCount(1, $outcome->replies);
        $this->assertSame('Нажмите «Отправить» или «Отменить» под заявкой.', $outcome->replies[0]->text);
    }

    public function test_is_not_part_of_summary(): void
    {
        $this->assertNull($this->step()->summary(new WizardState('confirm')));
    }

    private function step(): ConfirmStep
    {
        return new ConfirmStep(id: 'confirm', summaryOf: []);
    }

    // Шаг-заглушка: отдаёт заранее заданную строку сводки
    private function fixedSummary(?string $line): Step
    {
        return new class ($line) implements Step
        {
            public function __construct(private ?string $line) {}

            public function id(): string
            {
                return 'fixed';
            }

            public function prompt(WizardState $state): Reply
            {
                return new Reply('');
            }

            public function handle(WizardState $state, Input $input): StepOutcome
            {
                return StepOutcome::stay($state);
            }

            public function summary(WizardState $state): ?string
            {
                return $this->line;
            }
        };
    }
}
