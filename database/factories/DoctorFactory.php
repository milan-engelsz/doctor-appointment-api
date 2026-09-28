<?php

namespace Database\Factories;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'MD. '.fake()->firstName().' '.fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'field_of_expertise' => fake()->randomElement([
                'Cardiology', 'Dermatology', 'Neurology', 'Orthopedics', 'Pediatrics', 'Psychiatry', 'Urology',
            ]),
        ];
    }
}
