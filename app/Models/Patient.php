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
 * @property string $phone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Appointment> $appointments
 * @property-read int|null $appointments_count
 *
 * @method static \Database\Factories\PatientFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'email', 'phone'])]
class Patient extends Model
{
    use HasFactory;

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
