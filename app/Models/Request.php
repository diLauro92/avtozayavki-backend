<?php

namespace App\Models;

use App\Observers\RequestObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[ObservedBy(RequestObserver::class)]
class Request extends Model
{
    protected $attributes = [
        'status' => 'new',
        'request_type' => 'client',
    ];
    protected $fillable = [
        'source',
        'client_name',
        'phone',
        'car_info',
        'problem',
        'urgency',
        'status',
        'responsible_id',
        'next_contact_at',
        'request_type',
        'next_contact_reminded_at',
    ];

    protected $casts = [
        'next_contact_at' => 'datetime',
        'reminded_at' => 'datetime',
        'escalated_at' => 'datetime',
        'next_contact_reminded_at' => 'datetime',
    ];

    public function statusHistory() {
        return $this->hasMany(StatusHistory::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable');
    }
}
