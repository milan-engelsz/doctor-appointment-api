<?php

namespace Database\Factories;

use App\Models\Availability;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Availability>
 */
class AvailabilityFactory extends Factory
{
    /**
     * Future window whose end is always start + 8 slots (not an independent faker date).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $minutes = 30;
        $startsAt = CarbonImmutable::instance(fake()->dateTimeBetween('+1 week', '+2 weeks'))
            ->startOfHour();

        return [
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addMinutes($minutes * 8),
            'slot' => $minutes,
        ];
    }

    /**
     * One-day window of 8 × 30-minute slots starting at $clock (08:00–12:00 or 13:00–17:00).
     */
    public function windowOn(CarbonImmutable $day, string $clock = '08:00'): static
    {
        return $this->state(function () use ($day, $clock) {
            $startsAt = $day->setTimeFromTimeString($clock);

            return [
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->addMinutes(30 * 8),
                'slot' => 30,
            ];
        });
    }
}
