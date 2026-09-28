<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $doctor_id
 * @property int $patient_id
 * @property CarbonImmutable $start_time
 * @property CarbonImmutable $end_time
 * @property AppointmentStatus $status
 * @property string|null $cancellation_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Doctor $doctor
 * @property-read Patient $patient
 *
 * @method static \Database\Factories\AppointmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereCancellationReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereDoctorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment wherePatientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['doctor_id', 'patient_id', 'start_time', 'end_time', 'status', 'cancellation_reason'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_time' => 'immutable_datetime',
            'end_time' => 'immutable_datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function scopeOrdered($query)
    {
        return $query->oldest('start_time');
    }

    public function canTransitionTo(AppointmentStatus $next): bool
    {
        return $this->status->canTransitionTo($next);
    }
}
