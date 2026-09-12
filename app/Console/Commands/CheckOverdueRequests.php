<?php

namespace App\Console\Commands;

use App\Models\Request as RequestModel;
use App\Models\User;
use App\Notifications\OverdueRequestNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Throwable;

class CheckOverdueRequests extends Command
{
    protected $signature = 'leadhub:check-overdue';

    protected $description = 'Напоминания и эскалация по заявкам, которые долго висят в статусе «Новая»';

    public function handle(): int
    {
        $escalationBorder = now()->subMinutes(config('leadhub.escalation_after_minutes'));

        $toEscalate = RequestModel::where('status', 'new')
            ->whereNull('escalated_at')
            ->where('created_at', '<=', $escalationBorder)
            ->get();

        foreach ($toEscalate as $request) {
            $this->sendOverdue($request, escalation: true);
        }

        $reminderBorder = now()->subMinutes(config('leadhub.reminder_after_minutes'));

        $toRemind = RequestModel::where('status', 'new')
            ->whereNull('reminded_at')
            ->where('created_at', '<=', $reminderBorder)
            ->get();

        foreach ($toRemind as $request) {
            $this->sendOverdue($request, escalation: false);
        }

        return self::SUCCESS;
    }

    private function sendOverdue(RequestModel $request, bool $escalation): void
    {
        $recipients = collect([$request->responsible]);

        if ($escalation) {
            $recipients->push(User::find(config('leadhub.manager_id')));
        }

        try {
            Notification::send(
                $recipients->filter()->unique('id'),
                new OverdueRequestNotification($request, $escalation),
            );
        } catch (Throwable $e) {
            report($e);
            $this->error("#{$request->id}: не отправлено, повторим через минуту");

            return;
        }

        $marks = $escalation
            ? ['escalated_at' => now(), 'reminded_at' => $request->reminded_at ?? now()]
            : ['reminded_at' => now()];

        $request->forceFill($marks)->saveQuietly();

        $this->info("#{$request->id}: " . ($escalation ? 'эскалация' : 'напоминание'));
    }
}
