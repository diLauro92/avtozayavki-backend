<?php

use App\Telegram\Conversations\RequestConversation;
use SergiX44\Nutgram\Nutgram;

$bot = app(Nutgram::class);

$bot->onCommand('start', function (Nutgram $bot) {
    RequestConversation::begin($bot);
});
