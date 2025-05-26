<?php

namespace Database\Seeders;

use App\Models\BillingPlan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BillingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BillingPlan::insert([
            [
                'plan_name' => 'Standart',
            ],
            [
                'plan_name' => 'Premium',
            ],
            [
                'plan_name' => 'Ultra',
            ],
        ]);
    }
}
