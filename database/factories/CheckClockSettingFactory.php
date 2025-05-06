<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CheckClockSettingFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->company . ' Clock Setting',
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'radius' => $this->faker->randomFloat(2, 0.1, 10) . '', // as string
        ];
    }
}