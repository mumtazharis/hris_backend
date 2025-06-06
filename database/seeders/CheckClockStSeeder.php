<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CheckClockSetting;
use Illuminate\Support\Facades\DB;

class CheckClockStSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyIds = DB::table('companies')->pluck('company_id')->toArray();
        foreach ($companyIds as $companyId) {
            CheckClockSetting::create([
                'company_id' => $companyId,
                'name' => 'WFA',
                'latitude' => null,
                'longitude' => null,
                'radius' => null,
            ]);

            CheckClockSetting::create([
                'company_id' => $companyId,
                'name' => 'WFO',
                'latitude' => -7.944240526367352,
                'longitude' => 112.61488447149941,
                'radius' => 100,
            ]);
        }
    }
}
