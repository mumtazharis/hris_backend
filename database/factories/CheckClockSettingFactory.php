<?php

namespace Database\Factories;

use App\Models\CheckClockSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckClockSettingFactory extends Factory
{
    protected $model = CheckClockSetting::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' Office',
            'latitude' => $this->faker->latitude(-90, 90),
            'longitude' => $this->faker->longitude(-180, 180),
            'radius' => $this->faker->numberBetween(50, 500), // radius dalam meter
        ];
    }
}
