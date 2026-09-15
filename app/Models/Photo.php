<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['path', 'thumb_path', 'width', 'height', 'size', 'source'])]
class Photo extends Model
{
    public const SOURCE_TELEGRAM = 'telegram';

    public const SOURCE_MANUAL = 'manual';

    public function photoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
