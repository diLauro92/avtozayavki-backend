<?php

namespace App\Services;

use App\Models\Request as RequestModel;

class RequestService
{
    public function create(array $data): RequestModel
    {
        $request = RequestModel::create($data);

        return $request;
    }
}
