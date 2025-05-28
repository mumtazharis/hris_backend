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
        $billingDay = 28; // Tanggal pembuatan tagihan
        $deadlineOffsetDays = 5; // Deadline 5 hari setelah tanggal pembuatan tagihan
        $billingDate = Carbon::now()->day($billingDay);
                // Jika tanggal saat ini sudah lewat tanggal 28, maka tanggal tagihan adalah tanggal 28 bulan berikutnya
                if (Carbon::now()->day > $billingDay) {
                    $billingDate->addMonth();
                }
                $billingDate->day($billingDay); // Pastikan tanggalnya tetap 28 setelah addMonth jika diperlukan

                // Deadline untuk tabel bills: 5 hari setelah tanggal pembuatan tagihan
                $deadlineDate = $billingDate->copy()->addDays($deadlineOffsetDays)->format('Y-m-d');

        
        
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
                    COALESCE((
                        SELECT COUNT(*)
                        FROM employees
                        WHERE employee_status = 'Active'
                        AND user_id IN (
                            SELECT id FROM users WHERE company_id = ?
                        )
                    ), 0)
                    +
                    COALESCE((
                        SELECT COUNT(*)
                        FROM employees
                        WHERE user_id IN (
                            SELECT id FROM users WHERE company_id = ?
                        )
                        AND TO_CHAR(resign_date, 'YYYY-MM') = TO_CHAR(CURRENT_TIMESTAMP, 'YYYY-MM')
                        AND employee_status IN ('Resign', 'Retire')
                    ), 0)
                    +
                    COALESCE((
                        SELECT COUNT(*)
                        FROM employees
                        WHERE user_id IN (
                            SELECT id FROM users WHERE company_id = ?
                        )
                        AND employee_status IN ('Resign', 'Retire')
                        AND resign_date >= date_trunc('month', CURRENT_DATE - interval '1 month') + interval '28 day'
                        AND resign_date < date_trunc('month', CURRENT_DATE)
                        AND EXTRACT(DAY FROM resign_date) IN (29, 30, 31)
                    ), 0)
                ) AS total_employees_including_this_month_resigned;";
                $totalEmployeeResult = DB::selectOne($totalEmployeeQuerySql, [$companyIdOfUser, $companyIdOfUser, $companyIdOfUser]);
                $numberOfEmployees = $totalEmployeeResult->total_employees_including_this_month_resigned ?? 0;

                
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
        Log::info('Jumlah user ditemukan: ' . $users->count());
        Log::info("Bill berhasil dibuat untuk user: " . $user->id);

        
        return 0;
    }
}
