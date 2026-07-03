<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'client_name' => $this->client_name,
            'phone' => $this->phone,
            'car_info' => $this->car_info,
            'problem' => $this->problem,
            'urgency' => $this->urgency,
            'files' => $this->files,
            'status' => $this->status,
            'responsible_id' => $this->responsible_id,
            'comment' => $this->comment,
            'next_contact_at' => $this->next_contact_at,
            'request_type' => $this->request_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
