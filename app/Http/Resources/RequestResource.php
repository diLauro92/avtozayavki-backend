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
            'status' => $this->status,
            'responsible_id' => $this->responsible_id,
            'next_contact_at' => $this->next_contact_at,
            'request_type' => $this->request_type,
            'created_at' => $this->created_at,
            'responsible' => $this->whenLoaded('responsible', fn () => [
                'id' => $this->responsible->id,
                'name' => $this->responsible->name,
            ]),
            'status_history' => StatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'photos' => PhotoResource::collection($this->whenLoaded('photos')),
            'updated_at' => $this->updated_at,
        ];
    }
}
