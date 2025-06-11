<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PresentCCSeeder extends Seeder
{
    public function run(): void
    {
        // Get all relevant check clocks with status 'Present'
        $checkClocks = DB::table('check_clocks')
            ->where('status', 'Present')
            ->select('id', 'ck_setting_id', 'check_clock_date')
            ->get();

        foreach ($checkClocks as $clock) {
            $dayName = strtolower(Carbon::parse($clock->check_clock_date)->format('l'));

            // Get schedule for that day
            $ckTimes = DB::table('check_clock_setting_times')
                ->where('ck_setting_id', $clock->ck_setting_id)
                ->where('day', $dayName)
                ->first();

            if (!$ckTimes) {
                continue;
            }

            // Generate realistic "in" time based on schedule
            $inTime = Carbon::parse($ckTimes->clock_in)->subMinutes(rand(0, 30));

            // Insert the "in" record
            DB::table('present_detail_cc')->insert([
                'ck_id' => $clock->id,
                'ck_times_id' => $ckTimes->id,
                'check_clock_type' => 'in',
                'check_clock_time' => $inTime->format('H:i'),
                'latitude' => '3.597031',
                'longitude' => '98.678513',
                'evidence' => 'evidence_in_' . $clock->id . '.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Derive "out" time based on "in" time + 8 hours
            $outTime = $inTime->copy()->addHours(8)->addMinutes(rand(0, 30)); // Small randomness

            // Insert the "out" record
            DB::table('present_detail_cc')->insert([
                'ck_id' => $clock->id,
                'ck_times_id' => $ckTimes->id,
                'check_clock_type' => 'out',
                'check_clock_time' => $outTime->format('H:i'),
                'latitude' => '3.597031',
                'longitude' => '98.678513',
                'evidence' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
