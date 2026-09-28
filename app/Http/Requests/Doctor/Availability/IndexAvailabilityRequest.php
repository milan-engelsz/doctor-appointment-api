<?php

namespace App\Http\Requests\Doctor\Availability;

use Illuminate\Foundation\Http\FormRequest;

class IndexAvailabilityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }
}
