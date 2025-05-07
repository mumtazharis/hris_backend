<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CheckClock>
 */
class CheckClockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
         return [
            'employee_id' => $this->faker->numberBetween(1, 50),
            'check_clock_type' => $this->faker->randomElement(['in', 'out', 'break_start', 'break_end']),
            'check_clock_date' => $this->faker->date(),
            'check_clock_time' => $this->faker->time(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'evidence' => $this->faker->imageUrl(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
        ];
    }
}
