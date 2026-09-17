<?php

namespace App\Telegram;

use App\Enums\RequestSource;
use App\Services\PhotoService;
use App\Services\RequestService;
use App\Wizard\Button;
use App\Wizard\Input;
use App\Wizard\Reply;
use App\Wizard\Result;
use App\Wizard\Scenarios\AutoService;
use App\Wizard\WizardEngine;
use App\Wizard\WizardStore;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

// Переводчик между Telegram и движком анкеты
final readonly class TelegramWizard
{
    private const CHANNEL = RequestSource::Telegram;

    private WizardEngine $engine;

    public function __construct(
        RequestService $requests,
        private WizardStore $store,
        private PhotoService $photos,
    ) {
        $this->engine = new WizardEngine(AutoService::make(), $requests, self::CHANNEL);
    }

    public function start(Nutgram $bot): void
    {
        $this->apply($bot, $this->engine->start());
    }

    // false — клиент сейчас не в анкете, обновление не наше
    public function handle(Nutgram $bot): bool
    {
        if ($bot->isCallbackQuery()) {
            // Убираем часики на кнопке, даже если нажатие ни к чему не относится
            $bot->answerCallbackQuery();
        }

        $chatId = $bot->chatId();
        $state = $chatId === null ? null : $this->store->get(self::CHANNEL, $chatId);

        if ($state === null) {
            return false;
        }

        $this->apply($bot, $this->engine->handle($state, $this->input($bot)));

        return true;
    }

    public function reset(Nutgram $bot): void
    {
        $chatId = $bot->chatId();

        if ($chatId !== null) {
            $this->store->forget(self::CHANNEL, $chatId);
        }
    }

    private function apply(Nutgram $bot, Result $result): void
    {
        $chatId = $bot->chatId();

        if ($chatId === null) {
            return;
        }

        // Состояние - до отправки: если отправка упадет, повторное Отправить не создаст дубль
        if ($result->state === null) {
            $this->store->forget(self::CHANNEL, $chatId);
        } else {
            $this->store->put(self::CHANNEL, $chatId, $result->state);
        }

        foreach ($result->replies as $reply) {
            $this->send($bot, $reply);
        }

        // Фото качаем после ответа, чтобы клиент не ждал загрузки
        if ($result->created !== null && $result->photos !== []) {
            $this->photos->storeFromTelegram($bot, $result->photos, $result->created);
        }
    }

    private function input(Nutgram $bot): Input
    {
        // При нажатии кнопки message() вернет сообщение бота, поэтому кнопка - первой
        if ($bot->isCallbackQuery()) {
            return Input::button($bot->callbackQuery()?->data ?? '');
        }

        $message = $bot->message();
        $sizes = $message?->photo ?? [];

        if ($sizes !== []) {
            // Размеры идут по возрастанию, последний - самый крупный
            return Input::photo(end($sizes)->file_id);
        }

        if ($message?->text !== null) {
            return Input::text($message->text);
        }

        return Input::other();
    }

    private function send(Nutgram $bot, Reply $reply): void
    {
        $keyboard = null;

        if ($reply->buttons !== []) {
            $keyboard = InlineKeyboardMarkup::make();

            foreach ($reply->buttons as $row) {
                $keyboard->addRow(...array_map(
                    fn (Button $button) => InlineKeyboardButton::make($button->label, callback_data: $button->value),
                    $row,
                ));
            }
        }

        $bot->sendMessage(
            $reply->text,
            link_preview_options: LinkPreviewOptions::make(is_disabled: true),
            reply_markup: $keyboard,
        );
    }
}
