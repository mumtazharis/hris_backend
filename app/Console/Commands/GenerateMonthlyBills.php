<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User; // Jika Anda ingin mengambil data per user
use Illuminate\Support\Facades\Log;


class GenerateMonthlyBills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:generate-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly bills for all admin users/companies';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Generating monthly bills...');

        // Periode pembayaran: bulan-tahun saat ini (misal: 05-2025)
        $period = Carbon::now()->format('m-Y');

        // Deadline: tanggal 28 bulan ini, atau 5 hari dari sekarang jika tanggal saat ini sudah lewat 28
        $deadlineDate = Carbon::now()->day(28)->format('Y-m-d');
        if (Carbon::now()->day > 28) {
            $deadlineDate = Carbon::now()->addMonth()->day(28)->format('Y-m-d');
        }

        
        
        // Logic untuk mengambil semua user dengan role 'admin'
        // Asumsi ada kolom 'role' di tabel users
        $users = User::where('role', 'admin')->get();
        
        foreach ($users as $user) {
            // Dapatkan informasi plan dan total employee untuk user ini
            $plan = DB::table('users')
            ->join('companies', 'users.company_id', '=', 'companies.id')
            ->join('billing_plans', 'companies.plan_id', '=', 'billing_plans.id')
            ->where('users.id', $user->id)
            ->select('billing_plans.plan_name as plan_name', 'billing_plans.id as plan_id')
            ->first();
            
            if ($plan) {
                $companyIdOfUser = $user->company_id;
                
                // Hitung total karyawan untuk perusahaan user ini
                $totalEmployeeQuerySql = "
                SELECT (
                    COALESCE((SELECT COUNT(*) FROM users WHERE company_id = ?), 0) +
                    COALESCE((
                        SELECT COUNT(*)
                        FROM deleted_employee_log
                        WHERE user_id IN (SELECT id FROM users WHERE company_id = ?)
                        AND TO_CHAR(created_at, 'YYYY-MM') = TO_CHAR(CURRENT_TIMESTAMP, 'YYYY-MM')
                    ), 0)
                ) AS total_employees_including_this_month_deleted
                ";
                $totalEmployeeResult = DB::selectOne($totalEmployeeQuerySql, [$companyIdOfUser, $companyIdOfUser]);
                $numberOfEmployees = $totalEmployeeResult->total_employees_including_this_month_deleted ?? 0;

                
               $payment_id = 'hris-' . $user->id . '-' . $period;
                // Dapatkan harga per user berdasarkan jumlah karyawan
                $pricePerUserQuerySql = "
                    SELECT
                        pp.price
                    FROM
                        \"plans_price\" pp
                    WHERE
                        pp.\"plan_id\" = ? AND
                        ? >= pp.\"employee_min\" AND
                        (pp.\"employee_max\" IS NULL OR ? <= pp.\"employee_max\")
                    ORDER BY pp.\"employee_min\" ASC
                    LIMIT 1;
                ";
                $pricePerUserResult = DB::selectOne($pricePerUserQuerySql, [$plan->plan_id, $numberOfEmployees, $numberOfEmployees]);
                $price = $pricePerUserResult->price ?? 0;
                $amount = $numberOfEmployees * $price;

                // Masukkan data ke tabel bills
                DB::table('bills')->insert([
                    'payment_id' => $payment_id,
                    'user_id' => $user->id,
                    'total_employee' => $numberOfEmployees,
                    'amount' => $amount,
                    'period' => $period, // Format: 05-2022
                    'deadline' => $deadlineDate,
                    'status' => 'pending',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }

        $this->info('Monthly bills generated successfully!');
        
        Log::info('Bills generated at ' . now());
        return 0;
    }
}
