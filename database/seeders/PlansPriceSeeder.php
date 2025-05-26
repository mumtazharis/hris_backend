<?php

namespace Database\Seeders;

use App\Models\BillingPrice;
use App\Models\PlansPrice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlansPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PlansPrice::insert([
            [
                'plan_id' => 1,
                'employee_min' => 1,
                'employee_max' => 50,
                'price' => 10000,
            ],
            [
                'plan_id' => 1,
                'employee_min' => 51,
                'employee_max' => null,
                'price' => 9000,
            ],
            [
                'plan_id' => 2,
                'employee_min' => 1,
                'employee_max' => 50,
                'price' => 15000,
            ],
            [
                'plan_id' => 2,
                'employee_min' => 51,
                'employee_max' => null,
                'price' => 14000,
            ],
            [
                'plan_id' => 3,
                'employee_min' => 1,
                'employee_max' => 50,
                'price' => 20000,
            ],
            [
                'plan_id' => 3,
                'employee_min' => 51,
                'employee_max' => null,
                'price' => 18000,
            ],
        ]);
    }
}
