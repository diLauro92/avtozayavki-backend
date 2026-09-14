<?php

namespace App\Notifications;

use App\Models\Request as RequestModel;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewRequestNotification extends Notification
{
    public function __construct(private RequestModel $request) {}

    public function via(object $notifiable): array
    {
        return [TelegramChannel::class];
    }

    public function toTelegram(object $notifiable): string
    {
        $request = $this->request;

        return implode("\n", [
            "Новая заявка #{$request->id}",
            '',
            'Имя: ' . ($request->client_name ?? '—'),
            'Телефон: ' . $request->phone,
            'Авто: ' . ($request->car_info ?? '—'),
            'Проблема: ' . Str::limit($request->problem, 1000),
            'Срочность: ' . $this->urgencyLabel(),
            'Источник: ' . ($request->source === 'telegram' ? 'Telegram' : 'Ручной ввод'),
            '',
            config('app.url') . "/requests/{$request->id}",
        ]);
    }

    private function urgencyLabel(): string
    {
        return match ($this->request->urgency) {
            'today' => 'Сегодня',
            'soon' => '1–2 дня',
            'planned' => 'Планово',
            'emergency' => 'Аварийно',
            default => '—',
        };
    }
}
