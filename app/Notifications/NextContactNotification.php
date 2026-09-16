<?php

namespace App\Notifications;

use App\Models\Request as RequestModel;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Concerns\DescribesClient;
use Illuminate\Notifications\Notification;

class NextContactNotification extends Notification
{
    use DescribesClient;

    public function __construct(private RequestModel $request) {}

    public function via(object $notifiable): array
    {
        return [TelegramChannel::class];
    }

    public function toTelegram(object $notifiable): string
    {
        $request = $this->request;

        $time = $request->next_contact_at
            ->setTimezone(config('leadhub.timezone'))
            ->format('d.m.Y H:i');

        return implode("\n", [
            "📞 Пора перезвонить по заявке #{$request->id}",
            '',
            'Время: ' . $time,
            ...$this->clientLines($request, TelegramChannel::FOREIGN, 200),
            '',
            config('app.url') . "/requests/{$request->id}",
        ]);
    }
}
