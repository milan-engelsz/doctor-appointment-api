<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Doctor;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a small, testable dataset: past / today / tomorrow windows and bookings.
     */
    public function run(): void
    {
        // Five doctors with random factory data. Only the first three get windows and bookings.
        $doctors = Doctor::factory()->count(5)->create();
        $bookableDoctors = $doctors->take(3)->values();

        $yesterday = CarbonImmutable::today()->subDay();
        $today = CarbonImmutable::today();
        $tomorrow = CarbonImmutable::today()->addDay();

        // Yesterday, today, and tomorrow: even doctor IDs work mornings, odd IDs afternoons.
        foreach ($bookableDoctors as $doctor) {
            $clock = $this->clockFor($doctor->id);

            foreach ([$yesterday, $today, $tomorrow] as $day) {
                Availability::factory()
                    ->for($doctor)
                    ->windowOn($day, $clock)
                    ->create();
            }
        }

        // Ten patients; six of them get one appointment each (no patient overlap).
        $patients = Patient::factory()->count(10)->create();

        foreach ($patients->random(6)->values() as $index => $patient) {
            // Round-robin across the three bookable doctors, same morning/afternoon slot as their window.
            $doctor = $bookableDoctors[$index % 3];
            $clock = $this->clockFor($doctor->id);

            $factory = Appointment::factory()
                ->for($doctor)
                ->for($patient);

            if ($index < 2) {
                // First two: yesterday, already closed (cancelled or completed).
                $factory = $factory->startingAt($yesterday->setTimeFromTimeString($clock));
                $factory = fake()->randomElement([
                    AppointmentStatus::Cancelled,
                    AppointmentStatus::Completed,
                ]) === AppointmentStatus::Cancelled
                    ? $factory->cancelled()
                    : $factory->completed();
            } elseif ($index < 4) {
                // Next two: confirmed for today (open remaining slots stay free).
                $factory = $factory
                    ->startingAt($today->setTimeFromTimeString($clock))
                    ->confirmed();
            } else {
                // Last two: pending for tomorrow (later slots stay free).
                $factory = $factory
                    ->startingAt($tomorrow->setTimeFromTimeString($clock))
                    ->pending();
            }

            $factory->create();
        }
    }

    /**
     * Even doctor IDs start at 08:00, odd IDs at 13:00.
     */
    private function clockFor(int $doctorId): string
    {
        return $doctorId % 2 === 0 ? '08:00' : '13:00';
    }
}
