<?php

namespace App\Notifications;

use App\Models\Request as RequestModel;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class OverdueRequestNotification extends Notification
{
    public function __construct(
        private RequestModel $request,
        private bool $escalation,
    ) {}

    public function via(object $notifiable): array
    {
        return [TelegramChannel::class];
    }

    public function toTelegram(object $notifiable): string
    {
        $request = $this->request;

        $title = $this->escalation
            ? "🔴 Заявка #{$request->id} без обработки больше " . config('leadhub.escalation_after_minutes') . ' мин'
            : "⏰ Заявка #{$request->id} ждёт обработки больше " . config('leadhub.reminder_after_minutes') . ' мин';

        return implode("\n", [
            $title,
            '',
            'Имя: ' . ($request->client_name ?? '—'),
            'Телефон: ' . $request->phone,
            'Проблема: ' . Str::limit($request->problem, 200),
            'Ответственный: ' . ($request->responsible?->name ?? 'не назначен'),
            '',
            config('app.url'),
        ]);
    }
}
