<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'telegram_chat_id', 'telegram_link_code', 'telegram_link_expires_at'])]
#[Appends(['telegram_linked'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'telegram_chat_id' => 'integer',
            'telegram_link_expires_at' => 'datetime',
        ];
    }

    public function routeNotificationForTelegram(): ?int
    {
        return $this->telegram_chat_id;
    }

    protected function telegramLinked(): Attribute
    {
        return Attribute::get(fn () => $this->telegram_chat_id !== null);
    }
}
