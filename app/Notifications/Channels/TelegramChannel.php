<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use SergiX44\Nutgram\Nutgram;

class TelegramChannel
{
    public function __construct(private Nutgram $bot) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $chatId = $notifiable->routeNotificationFor('telegram', $notification);

        if ($chatId === null) {
            return;
        }

        $this->bot->sendMessage($notification->toTelegram($notifiable), chat_id: $chatId);
    }
}
