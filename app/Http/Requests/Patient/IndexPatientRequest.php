<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class IndexPatientRequest extends FormRequest
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
