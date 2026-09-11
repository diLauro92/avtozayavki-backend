<?php

use App\Telegram\Conversations\RequestConversation;
use SergiX44\Nutgram\Nutgram;

$bot = app(Nutgram::class);

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
