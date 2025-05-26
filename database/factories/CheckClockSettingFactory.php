<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CheckClockSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'radius' => '100',
        ];
    }
}
