<?php

namespace App\Notifications;

use App\Models\Request as RequestModel;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Concerns\DescribesClient;
use Illuminate\Notifications\Notification;

class NewRequestNotification extends Notification
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

        return implode("\n", [
            "Новая заявка #{$request->id}",
            '',
            ...$this->clientLines($request, TelegramChannel::FOREIGN, 1000, withCar: true),
            'Срочность: ' . $this->urgencyLabel(),
            'Источник: ' . $request->source->label(),
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
