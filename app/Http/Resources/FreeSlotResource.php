<?php

namespace App\Http\Resources;

use App\Data\FreeSlot;
use App\Support\ApiDateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FreeSlot */
class FreeSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'doctor_id' => $this->resource->doctor_id,
            'starts_at' => $this->resource->startsAt->format(ApiDateTime::FORMAT),
            'ends_at' => $this->resource->endsAt->format(ApiDateTime::FORMAT),
        ];
    }
}
