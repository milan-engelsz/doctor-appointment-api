<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class IndexDoctorRequest extends FormRequest
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
