<?php

namespace App\Http\Requests\FreeSlot;

use App\Models\Doctor;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

#[BodyParameter('from', type: 'string', example: '2027-09-01')]
#[BodyParameter('to', type: 'string', example: '2027-09-02')]
class IndexFreeSlotRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'doctor_id' => [
                'nullable',
                Rule::exists(Doctor::class, 'id'),
            ],
            'from' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
            'to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:from',
            ],
        ];
    }
}
