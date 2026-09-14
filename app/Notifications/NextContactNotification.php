<?php

namespace App\Notifications;

use App\Models\Request as RequestModel;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NextContactNotification extends Notification
{
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
            'Имя: ' . ($request->client_name ?? '-'),
            'Телефон: ' . $request->phone,
            'Проблема: ' . Str::limit($request->problem, 200),
            '',
            config('app.url') . "/requests/{$request->id}",
        ]);
    }
}
