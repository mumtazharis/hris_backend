<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CheckClockSettingTimes;
use Illuminate\Support\Facades\DB;

class CheckClockStTimesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $checkClockIds = DB::table('check_clock_settings')->pluck('id')->toArray();
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        // $ckSettings = ['WFA', 'WFO'];

        foreach ($checkClockIds as $ccId){
             $isWFA = $ccId % 2 === 1;

            foreach ($days as $day) {
                CheckClockSettingTimes::create([
                    'ck_setting_id' => $ccId,
                    'day' => $day,
                    'min_clock_in' => '07:30:00',
                    'clock_in' => '08:00:00',
                    'max_clock_in' => '10:00:00',
                    'clock_out' =>  $isWFA ? null : '17:00:00',
                    'max_clock_out' =>  $isWFA ? null : '21:00:00',
                ]);
            }
        }
    }
}
