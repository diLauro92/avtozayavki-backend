<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusHistory extends Model
{
    protected $table = 'status_history';
    protected $fillable = [
        'request_id',
        'old_status',
        'new_status',
        'changed_by',
    ];

    const UPDATED_AT = null;
}
