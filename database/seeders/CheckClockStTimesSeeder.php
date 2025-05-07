<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CheckClockSettingTimes;

class CheckClockStTimesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $ckSettings = ['WFA', 'WFO', 'Hybrid'];

        foreach ($ckSettings as $index => $ckSetting) {
            foreach ($days as $day) {
                CheckClockSettingTimes::create([
                    'ck_setting_id' => $index + 1, // Assuming ck_setting_id starts from 1
                    'day' => $day,
                    'clock_in' => '08:00:00',
                    'clock_out' => '17:00:00',
                    'break_start' => '12:00:00',
                    'break_end' => '13:00:00',
                ]);
            }
        }
    }
}
