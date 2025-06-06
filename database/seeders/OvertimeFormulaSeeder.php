<?php

namespace Database\Seeders;

use App\Models\OvertimeFormula;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OvertimeFormulaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        OvertimeFormula::insert([
            [
              'setting_id' => 1,
              'hour_start' => 0,
              'hour_end' => 1,
              'interval_hours' => null,
              'formula' => '1.5',  
            ],
            [
              'setting_id' => 1,
              'hour_start' => 1,
              'hour_end' => 24,
              'interval_hours' => null,
              'formula' => '2',  
            ],

            [
              'setting_id' => 2,
              'hour_start' => 0,
              'hour_end' => 7,
              'interval_hours' => null,
              'formula' => '2',  
            ],
            [
              'setting_id' => 2,
              'hour_start' => 7,
              'hour_end' => 8,
              'interval_hours' => null,
              'formula' => '3',  
            ],
            [
              'setting_id' => 2,
              'hour_start' => 8,
              'hour_end' => 24,
              'interval_hours' => null,
              'formula' => '4',  
            ],

            [
              'setting_id' => 3,
              'hour_start' => 0,
              'hour_end' => 5,
              'interval_hours' => null,
              'formula' => '2',  
            ],
            [
              'setting_id' => 3,
              'hour_start' => 5,
              'hour_end' => 6,
              'interval_hours' => null,
              'formula' => '3',  
            ],
            [
              'setting_id' => 3,
              'hour_start' => 6,
              'hour_end' => 24,
              'interval_hours' => null,
              'formula' => '4',  
            ],

            [
              'setting_id' => 4,
              'hour_start' => 0,
              'hour_end' => 8,
              'interval_hours' => null,
              'formula' => '2',  
            ],
            [
              'setting_id' => 4,
              'hour_start' => 8,
              'hour_end' => 9,
              'interval_hours' => null,
              'formula' => '3',  
            ],
            [
              'setting_id' => 4,
              'hour_start' => 9,
              'hour_end' => 24,
              'interval_hours' => null,
              'formula' => '4',  
            ],
          
            [
              'setting_id' => 5,
              'hour_start' => null,
              'hour_end' => null,
              'interval_hours' => 1,
              'formula' => '100000',  
            ],
            [
              'setting_id' => 6,
              'hour_start' => null,
              'hour_end' => null,
              'interval_hours' => 2,
              'formula' => '120000',  
            ],
            [
              'setting_id' => 7,
              'hour_start' => null,
              'hour_end' => null,
              'interval_hours' => 1,
              'formula' => '130000',  
            ],
            [
              'setting_id' => 8,
              'hour_start' => null,
              'hour_end' => null,
              'interval_hours' => 3,
              'formula' => '150000',  
            ],
            
        ]);
    }
}
