<?php

namespace App\Models;

use App\Observers\RequestObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
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
        'files',
        'status',
        'responsible_id',
        'comment',
        'next_contact_at',
        'request_type',
    ];

    protected $casts = [
        'files' => 'array',
        'next_contact_at' => 'datetime',
    ];

    public function statusHistory() {
        return $this->hasMany(StatusHistory::class);
    }
}
