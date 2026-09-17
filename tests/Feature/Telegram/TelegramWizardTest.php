<?php

namespace Tests\Feature\Telegram;

use App\Enums\RequestSource;
use App\Models\Request as RequestModel;
use App\Services\PhotoService;
use App\Services\RequestService;
use App\Wizard\WizardState;
use App\Wizard\WizardStore;
use Illuminate\Support\Facades\Exceptions;
use RuntimeException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatType;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Testing\FakeNutgram;
use Tests\TestCase;

// Бот в тестах поддельный: nutgram/laravel подставляет его и грузит routes/telegram.php
class TelegramWizardTest extends TestCase
{
    private const CHAT_ID = 555;

    private FakeNutgram $bot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bot = app(Nutgram::class);
        $this->bot->setCommonChat(Chat::make(id: self::CHAT_ID, type: ChatType::PRIVATE));
    }

    public function test_start_greets_and_asks_name(): void
    {
        $this->bot->hearText('/start')
            ->reply()
            ->assertReplyText('Здравствуйте! Оставьте заявку - я задам несколько вопросов.', 0)
            ->assertReplyText('Как вас зовут?', 1);

        $this->assertSame('name', $this->state()->step);
    }

    public function test_text_answer_moves_to_next_question(): void
    {
        $this->putState(new WizardState('name'));

        $this->bot->hearText('Пётр')
            ->reply()
            ->assertReplyText('Укажите номер телефона.', 0);

        $this->assertSame('phone', $this->state()->step);
        $this->assertSame('Пётр', $this->state()->answer('client_name'));
    }

    public function test_text_outside_wizard_gets_hint(): void
    {
        $this->bot->hearText('здравствуйте')
            ->reply()
            ->assertReplyText('Я принимаю заявки через короткую анкету. Нажмите /start, чтобы начать.', 0);

        $this->assertNull($this->state());
    }

    public function test_photo_uses_largest_size(): void
    {
        $this->putState(new WizardState('photos'));

        $this->bot->hearMessage(['photo' => [
            ['file_id' => 'small', 'file_unique_id' => 's', 'width' => 90, 'height' => 90],
            ['file_id' => 'large', 'file_unique_id' => 'l', 'width' => 1280, 'height' => 1280],
        ]])
            ->reply()
            ->assertReplyText('Фото 1 принято. Пришлите ещё или нажмите «Готово».', 0);

        $this->assertSame(['large'], $this->state()->photos);
    }

    public function test_button_press_is_answered_and_keyboard_is_sent(): void
    {
        $this->putState(new WizardState('urgency'));

        $this->bot->hearCallbackQueryData('today')
            ->reply()
            ->assertCalled('answerCallbackQuery')
            ->assertReplyMessage(['reply_markup' => ['inline_keyboard' => [[
                ['text' => '✅ Отправить', 'callback_data' => 'confirm'],
                ['text' => '❌ Отменить', 'callback_data' => 'cancel'],
            ]]]], 1);

        $this->assertSame('confirm', $this->state()->step);
    }

    public function test_stale_button_outside_wizard_is_only_answered(): void
    {
        $this->bot->hearCallbackQueryData('confirm')
            ->reply()
            ->assertCalled('answerCallbackQuery')
            ->assertCalled('sendMessage', 0);
    }

    public function test_confirm_creates_request_and_stores_photos(): void
    {
        $request = new RequestModel();
        $request->id = 7;

        $requests = $this->createMock(RequestService::class);
        $requests->expects($this->once())->method('create')->willReturn($request);
        $this->app->instance(RequestService::class, $requests);

        $photos = $this->createMock(PhotoService::class);
        $photos->expects($this->once())
            ->method('storeFromTelegram')
            ->with($this->isInstanceOf(Nutgram::class), ['large'], $this->identicalTo($request));
        $this->app->instance(PhotoService::class, $photos);

        $this->putState(new WizardState('confirm', ['phone' => '79161234567'], ['large']));

        $this->bot->hearCallbackQueryData('confirm')
            ->reply()
            ->assertReplyText('Заявка №7 принята! С вами свяжутся.', 1);

        $this->assertNull($this->state());
    }

    public function test_error_resets_wizard_and_apologizes(): void
    {
        Exceptions::fake();

        $requests = $this->createMock(RequestService::class);
        $requests->expects($this->once())
            ->method('create')
            ->willThrowException(new RuntimeException('база недоступна'));
        $this->app->instance(RequestService::class, $requests);

        $this->putState(new WizardState('confirm', ['phone' => '79161234567']));

        $this->bot->hearCallbackQueryData('confirm')
            ->reply()
            ->assertReplyText('Что-то пошло не так. Нажмите /start, чтобы начать заново.', 1);

        $this->assertNull($this->state());
        Exceptions::assertReported(RuntimeException::class);
    }

    private function putState(WizardState $state): void
    {
        app(WizardStore::class)->put(RequestSource::Telegram, self::CHAT_ID, $state);
    }

    private function state(): ?WizardState
    {
        return app(WizardStore::class)->get(RequestSource::Telegram, self::CHAT_ID);
    }
}
