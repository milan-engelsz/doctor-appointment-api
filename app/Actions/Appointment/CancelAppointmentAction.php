<?php

namespace App\Actions\Appointment;

use App\Enums\AppointmentStatus;
use App\Exceptions\InvalidAppointmentTransition;
use App\Models\Appointment;

readonly class CancelAppointmentAction
{
    public function __construct(
        private ChangeAppointmentStatusAction $changeStatus,
    ) {}

    /**
     * @throws \Throwable
     * @throws InvalidAppointmentTransition
     */
    public function execute(Appointment $appointment, string $cancellationReason): void
    {
        throw_unless(
            $this->passesCancellationNoticePeriod($appointment),
            new InvalidAppointmentTransition(
                'Appointments can only be cancelled at least 24 hours before the start.'
            )
        );

        $appointment->cancellation_reason = $cancellationReason;

        $this->changeStatus->execute(
            $appointment,
            AppointmentStatus::Cancelled,
        );
    }

    private function passesCancellationNoticePeriod(Appointment $appointment): bool
    {
        if ($appointment->status !== AppointmentStatus::Confirmed) {
            return true;
        }

        return $appointment->start_time->greaterThanOrEqualTo(now()->addHours(24));
    }
}
