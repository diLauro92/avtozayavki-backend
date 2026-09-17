<?php

use App\Services\TelegramLinkService;
use App\Telegram\TelegramWizard;
use Illuminate\Support\Str;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\UpdateType;

$bot = app(Nutgram::class);

$bot->onCommand('start {payload}', function (Nutgram $bot, string $payload) {
    if (! str_starts_with($payload, TelegramLinkService::LINK_PREFIX)) {
        app(TelegramWizard::class)->start($bot);

        return;
    }

    $chatId = $bot->chatId();

    if ($chatId === null) {
        return;
    }

    $code = Str::after($payload, TelegramLinkService::LINK_PREFIX);
    $user = app(TelegramLinkService::class)->linkByCode($code, $chatId);

    if ($user === null) {
        $bot->sendMessage('Ссылка недействительна или устарела. Запросите новую в личном кабинете.');

        return;
    }

    $bot->sendMessage("Готово, {$user->name}. Уведомления о заявках будут приходить сюда.");
});

$bot->onCommand('start', function (Nutgram $bot) {
    app(TelegramWizard::class)->start($bot);
});

$bot->onCommand('id', function (Nutgram $bot) {
    $bot->sendMessage("Ваш chat_id: {$bot->chatId()}");
});

$bot->fallbackOn(UpdateType::MESSAGE, function (Nutgram $bot) {
    if (app(TelegramWizard::class)->handle($bot)) {
        return;
    }

    if (! $bot->chat()?->isPrivate()) {
        return;
    }

    $bot->sendMessage('Я принимаю заявки через короткую анкету. Нажмите /start, чтобы начать.');
});

// Все кнопки бота сейчас принадлежат анкете
$bot->onCallbackQuery(function (Nutgram $bot) {
    app(TelegramWizard::class)->handle($bot);
});

$bot->onException(function (Nutgram $bot, Throwable $exception) {
    report($exception);

    if ($bot->chatId() === null) {
        return;
    }

    app(TelegramWizard::class)->reset($bot);

    // Страховка переезда: удаляет разговор старого визарда, если он остался в кэше
    $bot->endConversation();

    try {
        $bot->sendMessage('Что-то пошло не так. Нажмите /start, чтобы начать заново.');
    } catch (Throwable $sendError) {
        report($sendError);
    }
});
