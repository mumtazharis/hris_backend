<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Overtime;

class OvertimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Overtime::insert([
            [
                'employee_id' => 'EMP003',
                'overtime_setting_id' => 5,
                'date' => '2025-10-10',
                'total_hour' => 1
            ],
            [
                'employee_id' => 'EMP004',
                'overtime_setting_id' => 5,
                'date' => '2025-10-10',
                'total_hour' => 2
            ],
            [
                'employee_id' => 'EMP006',
                'overtime_setting_id' => 5,
                'date' => '2025-10-10',
                'total_hour' => 2
            ],
            [
                'employee_id' => 'EMP007',
                'overtime_setting_id' => 5,
                'date' => '2025-10-10',
                'total_hour' => 3
            ],
            [
                'employee_id' => 'EMP008',
                'overtime_setting_id' => 5,
                'date' => '2025-10-10',
                'total_hour' => 4
            ],
        ]);
    }
}
