<?php

namespace App\Http\Requests\Patient;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
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
                Rule::unique(Patient::class, 'email')
                    ->ignore($this->getRouteModelId()),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
            ],
        ];
    }

    protected function getRouteModelId(): ?int
    {
        return null;
    }
}
