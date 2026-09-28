<?php

namespace App\Models;

use App\Data\FreeSlot;
use Carbon\CarbonImmutable;
use Database\Factories\AvailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $doctor_id
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property int $slot
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Doctor $doctor
 *
 * @method static \Database\Factories\AvailabilityFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability whereDoctorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability whereSlot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['starts_at', 'ends_at', 'slot'])]
class Availability extends Model
{
    /** @use HasFactory<AvailabilityFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function scopeOrdered($query)
    {
        return $query->oldest('starts_at')->oldest('id');
    }

    public function getSlots(): Collection
    {
        $cursor = $this->starts_at;
        $windowEnd = $this->ends_at;
        $minutes = $this->slot;
        $slots = new Collection;

        while ($cursor->addMinutes($minutes)->lte($windowEnd)) {
            $slotEnd = $cursor->addMinutes($minutes);

            if ($cursor->isFuture()) {
                $slots->push(new FreeSlot($this->doctor_id, $cursor, $slotEnd));
            }

            $cursor = $slotEnd;
        }

        return $slots;
    }
}
