<?php

namespace Tests\Unit\Wizard;

use App\Wizard\Button;
use App\Wizard\Scenarios\AutoService;
use App\Wizard\WizardState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// Страховка переезда: тексты и порядок должны совпадать с RequestConversation
class AutoServiceTest extends TestCase
{
    public function test_steps_go_in_bot_order(): void
    {
        $scenario = AutoService::make();
        $ids = [];

        for ($step = $scenario->first(); $step !== null; $step = $scenario->after($step->id())) {
            $ids[] = $step->id();
        }

        $this->assertSame(['name', 'phone', 'car', 'problem', 'photos', 'urgency', 'confirm'], $ids);
    }

    public static function questions(): array
    {
        return [
            'имя' => ['name', 'Как вас зовут?'],
            'телефон' => ['phone', 'Укажите номер телефона.'],
            'авто' => ['car', 'Марка и модель авто? (можно пропустить - напишите «-»)'],
            'проблема' => ['problem', 'Опишите проблему.'],
            'фото' => ['photos', 'Пришлите фото, если есть - так мастеру будет понятнее. Можно несколько, по одному снимку.'],
            'срочность' => ['urgency', 'Насколько срочно?'],
        ];
    }

    #[DataProvider('questions')]
    public function test_question_texts_match_current_bot(string $id, string $expected): void
    {
        $reply = AutoService::make()->find($id)->prompt(new WizardState($id));

        $this->assertSame($expected, $reply->text);
    }

    public function test_urgency_buttons_are_two_by_two(): void
    {
        $reply = AutoService::make()->find('urgency')->prompt(new WizardState('urgency'));

        $this->assertEquals([
            [new Button('Сегодня', 'today'), new Button('1–2 дня', 'soon')],
            [new Button('Планово', 'planned'), new Button('Аварийно', 'emergency')],
        ], $reply->buttons);
    }

    public function test_confirm_summary_matches_current_bot(): void
    {
        $state = new WizardState(
            'confirm',
            [
                'client_name' => 'Пётр',
                'phone' => '79161234567',
                'car_info' => null,
                'problem' => 'Не заводится в мороз',
                'urgency' => 'today',
            ],
            ['photo-1', 'photo-2'],
        );

        $reply = AutoService::make()->find('confirm')->prompt($state);

        $this->assertSame(implode("\n", [
            'Проверьте заявку:',
            '',
            'Имя: Пётр',
            'Телефон: 79161234567',
            'Авто: -',
            'Проблема: Не заводится в мороз',
            'Срочность: Сегодня',
            'Фото: 2',
        ]), $reply->text);
    }

    public function test_phrases_match_current_bot(): void
    {
        $scenario = AutoService::make();

        $this->assertSame('Здравствуйте! Оставьте заявку - я задам несколько вопросов.', $scenario->greeting);
        $this->assertSame('Заявка №{id} принята! С вами свяжутся.', $scenario->submitted);
        $this->assertSame('Заявка отменена. Напишите /start, чтобы начать заново.', $scenario->cancelled);
    }
}
