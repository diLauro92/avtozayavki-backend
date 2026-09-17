<?php

namespace Tests\Feature\Wizard;

use App\Enums\RequestSource;
use App\Models\Request as RequestModel;
use App\Services\RequestService;
use App\Wizard\Input;
use App\Wizard\Reply;
use App\Wizard\Scenario;
use App\Wizard\Scenarios\AutoService;
use App\Wizard\Steps\TextStep;
use App\Wizard\WizardEngine;
use App\Wizard\WizardState;
use LogicException;
use Tests\TestCase;

class WizardEngineTest extends TestCase
{
    public function test_start_greets_and_asks_first_question(): void
    {
        $result = $this->engine()->start();

        $this->assertSame(
            ['Здравствуйте! Оставьте заявку - я задам несколько вопросов.', 'Как вас зовут?'],
            $this->texts($result->replies),
        );
        $this->assertSame('name', $result->state->step);
    }

    public function test_rejected_answer_keeps_step(): void
    {
        $result = $this->engine()->handle(new WizardState('phone'), Input::text('abc'));

        $this->assertSame(
            ['Не похоже на номер. Введите телефон в формате +7 999 123-45-67.'],
            $this->texts($result->replies),
        );
        $this->assertSame('phone', $result->state->step);
        $this->assertNull($result->created);
    }

    public function test_accepted_answer_asks_next_question(): void
    {
        $result = $this->engine()->handle(new WizardState('name'), Input::text('Пётр'));

        $this->assertSame(['Укажите номер телефона.'], $this->texts($result->replies));
        $this->assertSame('phone', $result->state->step);
        $this->assertSame('Пётр', $result->state->answer('client_name'));
    }

    public function test_walks_whole_scenario_and_submits(): void
    {
        $request = new RequestModel();
        $request->id = 42;

        $requests = $this->createMock(RequestService::class);
        $requests->expects($this->once())
            ->method('create')
            ->with([
                'client_name' => 'Пётр',
                'phone' => '79161234567',
                'car_info' => null,
                'problem' => 'Не заводится',
                'urgency' => 'soon',
                'source' => RequestSource::Telegram,
            ])
            ->willReturn($request);

        $engine = $this->engine($requests);
        $result = $engine->start();
        $texts = $this->texts($result->replies);

        $answers = [
            Input::text('Пётр'),
            Input::text('abc'),
            Input::text('+7 916 123-45-67'),
            Input::text('-'),
            Input::text('Не заводится'),
            Input::photo('file-1'),
            Input::button('today'),
            Input::photo('file-2'),
            Input::button('photo_done'),
            Input::button('soon'),
            Input::text('да'),
            Input::button('confirm'),
        ];

        foreach ($answers as $input) {
            $result = $engine->handle($result->state, $input);
            $texts = [...$texts, ...$this->texts($result->replies)];
        }

        $this->assertSame([
            'Здравствуйте! Оставьте заявку - я задам несколько вопросов.',
            'Как вас зовут?',
            'Укажите номер телефона.',
            'Не похоже на номер. Введите телефон в формате +7 999 123-45-67.',
            'Марка и модель авто? (можно пропустить - напишите «-»)',
            'Опишите проблему.',
            'Пришлите фото, если есть - так мастеру будет понятнее. Можно несколько, по одному снимку.',
            'Фото 1 принято. Пришлите ещё или нажмите «Готово».',
            'Фото 2 принято. Пришлите ещё или нажмите «Готово».',
            'Насколько срочно?',
            "Проверьте заявку:\n\nИмя: Пётр\nТелефон: 79161234567\nАвто: -\nПроблема: Не заводится\nСрочность: 1–2 дня\nФото: 2",
            'Нажмите «Отправить» или «Отменить» под заявкой.',
            'Заявка №42 принята! С вами свяжутся.',
        ], $texts);
        $this->assertNull($result->state);
        $this->assertSame($request, $result->created);
        $this->assertSame(['file-1', 'file-2'], $result->photos);
    }

    public function test_cancel_does_not_create_request(): void
    {
        $requests = $this->createMock(RequestService::class);
        $requests->expects($this->never())->method('create');

        $result = $this->engine($requests)->handle(new WizardState('confirm'), Input::button('cancel'));

        $this->assertSame(['Заявка отменена. Напишите /start, чтобы начать заново.'], $this->texts($result->replies));
        $this->assertNull($result->state);
        $this->assertNull($result->created);
    }

    public function test_unknown_step_starts_over(): void
    {
        $result = $this->engine()->handle(new WizardState('removed'), Input::text('что-то'));

        $this->assertSame('name', $result->state->step);
        $this->assertSame('Здравствуйте! Оставьте заявку - я задам несколько вопросов.', $result->replies[0]->text);
    }

    public function test_next_after_last_step_is_scenario_error(): void
    {
        $scenario = new Scenario(
            greeting: 'Привет',
            steps: [new TextStep(id: 'only', field: 'note', label: 'Заметка', question: 'Что?', retry: 'Текстом.')],
            submitted: 'Принято',
            cancelled: 'Отменено',
        );
        $engine = new WizardEngine($scenario, $this->createStub(RequestService::class), RequestSource::Manual);

        $this->expectException(LogicException::class);

        $engine->handle(new WizardState('only'), Input::text('что-то'));
    }

    private function engine(?RequestService $requests = null): WizardEngine
    {
        return new WizardEngine(
            AutoService::make(),
            $requests ?? $this->createStub(RequestService::class),
            RequestSource::Telegram,
        );
    }

    /**
     * @param list<Reply> $replies
     * @return list<string>
     */
    private function texts(array $replies): array
    {
        return array_map(fn (Reply $reply) => $reply->text, $replies);
    }
}
