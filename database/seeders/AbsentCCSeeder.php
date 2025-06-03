<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AbsentCCSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $checkClockIds = DB::table('check_clocks') ->whereIn('status', ['Annual Leave', 'Sick Leave'])->pluck('id')->toArray();
        
        foreach ($checkClockIds as $ckId) {
            $startDate = Carbon::now();
            $endDate = Carbon::now()->addDays(rand(1, 7)); // Random duration between 1-7 days
            
            DB::table('absent_detail_cc')->insert([
                'ck_id' => $ckId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'evidence' => 'evidence_absent_' . $ckId . '.pdf',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
