<?php

namespace Database\Factories;

use App\Models\CheckClockSetting;
use App\Models\CheckClockSettingTimes;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckClockSettingTimesFactory extends Factory
{
    protected $model = CheckClockSettingTimes::class;

    public function definition()
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        $clockIn = $this->faker->time('H:i:s');
        $breakStart = $this->faker->time('H:i:s');
        $breakEnd = $this->faker->time('H:i:s');
        $clockOut = $this->faker->time('H:i:s');

        return [
            'ck_setting_id' => CheckClockSetting::factory(),
            'day' => $this->faker->randomElement($days),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
        ];
    }
}