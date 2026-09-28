<?php

namespace App\Actions\Appointment;

use App\Data\FreeSlot;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\FreeSlotService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

readonly class StoreAppointmentAction
{
    public function __construct(private FreeSlotService $freeSlotService) {}

    public function execute(Doctor $doctor, Patient $patient, CarbonImmutable $startTime): Appointment
    {
        $freeSlot = $this->freeSlotService->getFreeSlot($doctor, $startTime);

        if (! $freeSlot) {
            throw ValidationException::withMessages([
                'start_time' => 'The slot is not available.',
            ]);
        }

        if ($this->patientHasOverlap($patient, $freeSlot)) {
            throw ValidationException::withMessages([
                'start_time' => 'The patient has an overlapping appointment.',
            ]);
        }

        return $this->createAppointment($doctor, $patient, $freeSlot);
    }

    private function patientHasOverlap(Patient $patient, FreeSlot $freeSlot): bool
    {
        return $patient->appointments()
            ->where('start_time', '<', $freeSlot->endsAt)
            ->where('end_time', '>', $freeSlot->startsAt)
            ->whereNot('status', AppointmentStatus::Cancelled)
            ->exists();
    }

    private function createAppointment(Doctor $doctor, Patient $patient, FreeSlot $freeSlot): Appointment
    {
        $appointment = new Appointment;

        $appointment->fill([
            'start_time' => $freeSlot->startsAt,
            'end_time' => $freeSlot->endsAt,
            'status' => AppointmentStatus::Pending,
        ]);

        $appointment->doctor()->associate($doctor);
        $appointment->patient()->associate($patient);

        $appointment->save();

        return $appointment;
    }
}
