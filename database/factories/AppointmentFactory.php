<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = CarbonImmutable::now()->addDay()->startOfHour();

        return [
            'doctor_id' => Doctor::factory(),
            'patient_id' => Patient::factory(),
            'start_time' => $startTime,
            'end_time' => $startTime->addMinutes(30),
            'status' => AppointmentStatus::Pending,
            'cancellation_reason' => null,
        ];
    }

    /**
     * Book a slot that starts at $start and lasts $minutes.
     */
    public function startingAt(CarbonImmutable $start, int $minutes = 30): static
    {
        return $this->state([
            'start_time' => $start,
            'end_time' => $start->addMinutes($minutes),
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'status' => AppointmentStatus::Pending,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state([
            'status' => AppointmentStatus::Confirmed,
        ]);
    }

    /**
     * Cancelled bookings always store a short reason.
     */
    public function cancelled(): static
    {
        return $this->state([
            'status' => AppointmentStatus::Cancelled,
            'cancellation_reason' => 'Patient is unavailable.',
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => AppointmentStatus::Completed,
        ]);
    }
}
