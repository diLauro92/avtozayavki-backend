<?php

use App\Telegram\Conversations\RequestConversation;
use SergiX44\Nutgram\Nutgram;
use App\Services\TelegramLinkService;
use Illuminate\Support\Str;

$bot = app(Nutgram::class);

$bot->onCommand('start {payload}', function (Nutgram $bot, string $payload) {
    if (! str_starts_with($payload, TelegramLinkService::LINK_PREFIX)) {
        RequestConversation::begin($bot);

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
    RequestConversation::begin($bot);
});

$bot->onCommand('id', function (Nutgram $bot) {
    $bot->sendMessage("Ваш chat_id: {$bot->chatId()}");
});

$bot->onException(function (Nutgram $bot, Throwable $exception) {
    report($exception);

    if ($bot->chatId() === null) {
        return;
    }

    $bot->endConversation();

    try {
        $bot->sendMessage('Что-то пошло не так. Нажмите /start, чтобы начать заново.');
    } catch (Throwable $sendError) {
        report($sendError);
    }
});
