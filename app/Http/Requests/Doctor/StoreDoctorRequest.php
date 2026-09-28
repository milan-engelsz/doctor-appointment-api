<?php

namespace App\Http\Requests\Doctor;

use App\Models\Doctor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDoctorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(Doctor::class, 'email')
                    ->ignore($this->getRouteModelId()),
            ],
            'field_of_expertise' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    protected function getRouteModelId(): ?int
    {
        return null;
    }
}
