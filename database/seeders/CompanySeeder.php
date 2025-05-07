<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::insert([
            [
                'name' => 'Company One',
                'company_id' => 'COMP001',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Company Two',
                'company_id' => 'COMP002',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
