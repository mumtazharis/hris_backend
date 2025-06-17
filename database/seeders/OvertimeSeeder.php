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
                'employee_id' => 'EMP001',
                'overtime_setting_id' => 5,
                'date' => '2025-10-10',
                'start_hour' => '17:00',
                'end_hour' => '23:00',
                'payroll' => 300000
            ],
            [
                'employee_id' => 'EMP003',
                'overtime_setting_id' => 5,
                'date' => '2025-10-10',
                'start_hour' => '17:00',
                'end_hour' => '20:00',
                'payroll' => 150000
            ],
        ]);
    }
}
