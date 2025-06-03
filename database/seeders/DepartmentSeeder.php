<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Department::insert([
            [
            'company_id' => 'COMP001',
            'name' => 'Human Resources',
            'description' => 'Handles recruitment, employee relations, and payroll.',
            'created_at' => now(),
            'updated_at' => now(),
            ],
            [
            'company_id' => 'COMP001',
            'name' => 'Finance',
            'description' => 'Manages company finances, budgets, and audits.',
            'created_at' => now(),
            'updated_at' => now(),
            ],
            [
            'company_id' => 'COMP001',
            'name' => 'IT Department',
            'description' => 'Responsible for technology infrastructure and support.',
            'created_at' => now(),
            'updated_at' => now(),
            ],
            [
            'company_id' => 'COMP002',
            'name' => 'Human Resources comp2',
            'description' => 'Handles recruitment, employee relations, and payroll.',
            'created_at' => now(),
            'updated_at' => now(),
            ],
            [
            'company_id' => 'COMP002',
            'name' => 'Finance comp2',
            'description' => 'Manages company finances, budgets, and audits.',
            'created_at' => now(),
            'updated_at' => now(),
            ],
            [
            'company_id' => 'COMP002',
            'name' => 'IT Department comp2',
            'description' => 'Responsible for technology infrastructure and support.',
            'created_at' => now(),
            'updated_at' => now(),
            ],
        ]);
    }
}
