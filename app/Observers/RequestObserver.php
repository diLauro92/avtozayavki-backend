<?php

namespace App\Observers;

use App\Models\Request;
use App\Models\StatusHistory;

class RequestObserver
{
    /**
     * Handle the Request "created" event.
     */
    public function created(Request $request): void
    {
        StatusHistory::create([
            'request_id' => $request->id,
            'old_status' => null,
            'new_status' => $request->status,
            'changed_by' => null,
            'created_at' => now(),
        ]);
    }

    /**
     * Handle the Request "updated" event.
     */
    public function updating(Request $request): void
    {
        if (! $request->isDirty('status')) {
            return;
        }

        StatusHistory::create([
            'request_id' => $request->id,
            'old_status' => $request->getOriginal('status'),
            'new_status' => $request->status,
            'changed_by' => auth()->id(),
        ]);
    }

    /**
     * Handle the Request "deleted" event.
     */
    public function deleted(Request $request): void
    {
        //
    }

    /**
     * Handle the Request "restored" event.
     */
    public function restored(Request $request): void
    {
        //
    }

    /**
     * Handle the Request "force deleted" event.
     */
    public function forceDeleted(Request $request): void
    {
        //
    }
}
