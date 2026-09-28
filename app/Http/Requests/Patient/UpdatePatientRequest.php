<?php

namespace App\Http\Requests\Patient;

use App\Models\Patient;

class UpdatePatientRequest extends StorePatientRequest
{
    protected function getRouteModelModel(): Patient
    {
        return $this->route('patient');
    }

    protected function getRouteModelId(): ?int
    {
        return $this->getRouteModelModel()->getKey();
    }
}
