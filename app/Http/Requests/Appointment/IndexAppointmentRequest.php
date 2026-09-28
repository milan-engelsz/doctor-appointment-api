<?php

namespace App\Http\Requests\Appointment;

use App\Enums\AppointmentStatus;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAppointmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'doctor_id' => [
                'nullable',
                Rule::exists(Doctor::class, 'id'),
            ],
            'patient_id' => [
                'nullable',
                Rule::exists(Patient::class, 'id'),
            ],
            'status' => [
                'nullable',
                Rule::enum(AppointmentStatus::class),
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }
}
