<?php

namespace App\Http\Requests\Doctor\Availability;

use App\Models\Doctor;
use App\Rules\EndsAtFitsSlotDuration;
use App\Rules\SameDayAs;
use App\Support\ApiDateTime;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

#[BodyParameter('starts_at', type: 'string', example: '2027-09-01 09:00:00')]
#[BodyParameter('ends_at', type: 'string', example: '2027-09-01 09:30:00')]
class StoreAvailabilityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'starts_at' => [
                'required',
                'date_format:'.ApiDateTime::FORMAT,
                'after:now',
            ],
            'ends_at' => [
                'required',
                'date_format:'.ApiDateTime::FORMAT,
                'after:starts_at',
                new SameDayAs('starts_at'),
                new EndsAtFitsSlotDuration('starts_at', 'slot'),
            ],
            'slot' => [
                'required',
                'integer',
                'min:30',
                'max:120',
            ],
        ];
    }

    private function doctor(): Doctor
    {
        return $this->route('doctor');
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $startsAt = $this->input('starts_at');
            $endsAt = $this->input('ends_at');
            $overlaps = $this->doctor()
                ->availabilities()
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->exists();
            if (! $overlaps) {
                return;
            }
            $message = 'The :attribute field overlaps an existing availability window for this doctor.';
            $validator->errors()->add('starts_at', str_replace(':attribute', 'starts at', $message));
            $validator->errors()->add('ends_at', str_replace(':attribute', 'ends at', $message));
        });
    }
}
