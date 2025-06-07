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
        $ckSettings = ['WFA', 'WFO'];

        foreach ($ckSettings as $index => $ckSetting) {
            foreach ($days as $day) {
                CheckClockSettingTimes::create([
                    'ck_setting_id' => $index + 1, // Assuming ck_setting_id starts from 1
                    'day' => $day,
                    'min_clock_in' => '07:30:00',
                    'clock_in' => '08:00:00',
                    'max_clock_in' => '10:00:00',
                    'clock_out' =>  $ckSetting === 'WFA' ? null : '17:00:00',
                    'max_clock_out' =>  $ckSetting === 'WFA' ? null : '21:00:00',
                ]);
            }
        }
    }
}
