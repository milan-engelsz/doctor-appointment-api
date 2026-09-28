<?php

namespace App\Http\Resources;

use App\Support\ApiDateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'doctor_id' => $this->resource->doctor_id,
            'starts_at' => $this->resource->starts_at->format(ApiDateTime::FORMAT),
            'ends_at' => $this->resource->ends_at->format(ApiDateTime::FORMAT),
            'slot' => $this->resource->slot,
        ];
    }
}
