<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $field_of_expertise
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Appointment> $appointments
 * @property-read int|null $appointments_count
 * @property-read Collection<int, Availability> $availabilities
 * @property-read int|null $availabilities_count
 *
 * @method static \Database\Factories\DoctorFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereFieldOfExpertise($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'email', 'field_of_expertise'])]
class Doctor extends Model
{
    use HasFactory;

    /** @return HasMany<Availability, $this> */
    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class)
            ->ordered();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
