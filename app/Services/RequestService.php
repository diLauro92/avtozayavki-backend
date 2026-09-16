<?php

namespace App\Services;

use App\Models\Request as RequestModel;
use App\Models\User;
use App\Notifications\NewRequestNotification;
use App\Support\Phone;
use RuntimeException;

class RequestService
{
    public function create(array $data): RequestModel
    {
        if (isset($data['phone'])) {
            $data['phone'] = Phone::normalize($data['phone']) ?? $data['phone'];
        }

        $data['responsible_id'] ??= $this->defaultResponsibleId();

        $request = RequestModel::create($data);

        $request->responsible?->notify(new NewRequestNotification($request));

        return $request;
    }

    private function defaultResponsibleId(): ?int
    {
        $id = config('leadhub.default_responsible_id');

        if ($id === null) {
            return null;
        }

        if (! User::whereKey($id)->exists()) {
            report(new RuntimeException("Ответственный по умолчанию не найден: user #{$id}"));

            return null;
        }

        return $id;
    }
}
