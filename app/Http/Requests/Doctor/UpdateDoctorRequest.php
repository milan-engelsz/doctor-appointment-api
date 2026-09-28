<?php

namespace App\Http\Requests\Doctor;

use App\Models\Doctor;

class UpdateDoctorRequest extends StoreDoctorRequest
{
    protected function getRouteModelModel(): Doctor
    {
        return $this->route('doctor');
    }

    protected function getRouteModelId(): ?int
    {
        return $this->getRouteModelModel()->getKey();
    }
}
