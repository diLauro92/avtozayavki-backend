<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

class TelegramLinkService
{
    public const LINK_PREFIX = 'link_';

    private const TTL_MINUTES = 15;

    public function issueLink(User $user): string
    {
        return $this->deepLink($this->issueCode($user));
    }

    public function issueCode(User $user): string
    {
        $code = Str::random(32);

        $user->telegram_link_code = $code;
        $user->telegram_link_expires_at = now()->addMinutes(self::TTL_MINUTES);
        $user->save();

        return $code;
    }

    public function linkByCode(string $code, int $chatId): ?User
    {
        $user = User::where('telegram_link_code', $code)
            ->where('telegram_link_expires_at', '>', now())
            ->first();

        if ($user === null) {
            return null;
        }

        $user->telegram_chat_id = $chatId;
        $user->telegram_link_code = null;
        $user->telegram_link_expires_at = null;
        $user->save();

        return $user;
    }

    public function unlink(User $user): void
    {
        $user->telegram_chat_id = null;
        $user->telegram_link_code = null;
        $user->telegram_link_expires_at = null;
        $user->save();
    }

    private function deepLink(string $code): string
    {
        $username = config('leadhub.telegram_bot_username');

        if (empty($username)) {
            throw new RuntimeException('Не задан TELEGRAM_BOT_USERNAME');
        }

        return "https://t.me/{$username}?start=" . self::LINK_PREFIX . $code;
    }
}
