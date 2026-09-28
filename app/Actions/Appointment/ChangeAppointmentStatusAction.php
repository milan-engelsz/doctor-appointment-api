<?php

namespace App\Actions\Appointment;

use App\Enums\AppointmentStatus;
use App\Exceptions\InvalidAppointmentTransition;
use App\Models\Appointment;

readonly class ChangeAppointmentStatusAction
{
    /**
     * @throws \Throwable
     * @throws InvalidAppointmentTransition
     */
    public function execute(Appointment $appointment, AppointmentStatus $status): void
    {
        throw_unless(
            $appointment->status->canTransitionTo($status),
            new InvalidAppointmentTransition(
                'Invalid appointment status transition.'
            )
        );

        $appointment->update([
            'status' => $status->value,
        ]);
    }
}
