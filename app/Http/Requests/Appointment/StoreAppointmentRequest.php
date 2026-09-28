<?php

namespace App\Http\Requests\Appointment;

use App\Models\Doctor;
use App\Models\Patient;
use App\Support\ApiDateTime;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

#[BodyParameter('start_time', type: 'string', example: '2027-09-01 09:00:00')]
#[BodyParameter('end_time', type: 'string', example: '2027-09-01 09:30:00')]
class StoreAppointmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'doctor_id' => [
                'required',
                Rule::exists(Doctor::class, 'id'),
            ],
            'patient_id' => [
                'required',
                Rule::exists(Patient::class, 'id'),
            ],
            'start_time' => [
                'required',
                'date_format:Y-m-d H:i:s',
                'after:now',
            ],
        ];
    }

    public function startTime(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat(
            ApiDateTime::FORMAT,
            $this->string('start_time'),
        );
    }
}
