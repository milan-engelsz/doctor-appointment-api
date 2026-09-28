<?php

namespace App\Services;

use App\Data\FreeSlot;
use App\Enums\AppointmentStatus;
use App\Models\Availability;
use App\Models\Doctor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

readonly class FreeSlotService
{
    public function getFreeSlots(?int $doctorId, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Collection
    {
        return Availability::query()
            ->oldest('starts_at')
            ->with('doctor')
            ->when($doctorId, fn ($query) => $query->where('doctor_id', $doctorId))
            ->when($from, function ($query, CarbonImmutable $from) {
                $query->where('starts_at', '>=', $from->startOfDay());
            })
            ->when($to, function ($query, CarbonImmutable $to) {
                $query->where('ends_at', '<=', $to->endOfDay());
            })
            ->whereFuture('ends_at')
            ->get()
            ->flatMap(function (Availability $availability) {
                $lockedSlots = $this->getDoctorAvailabilityLockedSlots($availability->doctor, $availability);

                return $availability->getSlots()
                    ->filter(fn (FreeSlot $slot) => ! $lockedSlots->contains($slot->startsAt))
                    ->values();
            });
    }

    private function getDoctorAvailabilityLockedSlots(Doctor $doctor, Availability $availability): Collection
    {
        return $doctor->appointments()
            ->where('status', '!=', AppointmentStatus::Cancelled)
            ->whereBetween('start_time', [$availability->starts_at, $availability->ends_at])
            ->pluck('start_time');
    }

    public function getFreeSlot(Doctor $doctor, CarbonImmutable $startTime): ?FreeSlot
    {
        return $this->getFreeSlots(
            $doctor->id,
            $startTime,
            $startTime
        )->firstWhere('startsAt', $startTime);
    }
}
