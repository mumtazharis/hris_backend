<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;


class BillsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua user dengan role admin
        $admins = User::where('role', 'admin')->get();

        $periods = [
            Carbon::now()->format('m-Y'),
            Carbon::now()->subMonth()->format('m-Y'),
        ];

        $data = [];
        foreach ($admins as $admin) {
            foreach ($periods as $period) {
                $paymentId = 'hris-' . $admin->id . '-' . $period;
                $data[] = [
                    'payment_id' => $paymentId,
                    'user_id' => $admin->id,
                    'total_employee' => rand(5, 50),
                    'amount' => rand(100000, 500000),
                    'period' => $period,
                    'deadline' => Carbon::createFromFormat('m-Y', $period)->endOfMonth()->format('Y-m-d'),
                    'status' => 'pending',
                    'pay_at' => null, // Belum dibayar
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('bills')->insert($data);
    }
}
