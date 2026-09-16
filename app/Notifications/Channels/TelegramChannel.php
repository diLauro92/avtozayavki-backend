<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;

class TelegramChannel
{
    // Telegram — иностранный сервис: отправленное сюда покидает РФ
    public const FOREIGN = true;

    public function __construct(private Nutgram $bot) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $chatId = $notifiable->routeNotificationFor('telegram', $notification);

        if ($chatId === null) {
            return;
        }

        $this->bot->sendMessage(
            $notification->toTelegram($notifiable),
            chat_id: $chatId,
            link_preview_options: LinkPreviewOptions::make(is_disabled: true),
        );
    }
}
