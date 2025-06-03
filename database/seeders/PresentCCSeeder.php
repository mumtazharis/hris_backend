<?php
namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PresentCCSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = ['in', 'out'];
        
        // Get existing check_clocks IDs
        $checkClockIds = DB::table('check_clocks')->where('status', 'Present')->pluck('id')->toArray();
        
        foreach ($checkClockIds as $ckId) {
            // Create multiple entries for each check_clock
            foreach ($types as $type) {
                DB::table('present_detail_cc')->insert([
                    'ck_id' => $ckId,
                    'check_clock_type' => $type,
                    'check_clock_time' => $type == "in" ? Carbon::now()->format('H:i:s') : Carbon::now()->addHours(8)->format('H:i:s'),
                    'latitude' => '3.597031',  // Example coordinates
                    'longitude' => '98.678513', // Example coordinates
                    'evidence' => 'evidence_' . $type . '_' . $ckId . '.jpg',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}
