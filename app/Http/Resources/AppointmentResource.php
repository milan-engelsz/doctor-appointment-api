<?php

namespace App\Http\Resources;

use App\Support\ApiDateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'doctor_id' => $this->resource->doctor_id,
            'patient_id' => $this->resource->patient_id,
            'start_time' => $this->resource->start_time->format(ApiDateTime::FORMAT),
            'end_time' => $this->resource->end_time->format(ApiDateTime::FORMAT),
            'status' => $this->resource->status->value,
            'cancellation_reason' => $this->resource->cancellation_reason,
        ];
    }
}
