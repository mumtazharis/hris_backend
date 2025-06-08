<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Bills; // Pastikan model Bills sudah ada

class UpdateOverdueAdminPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-overdue-admin-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking overdue bills...');

        $now = Carbon::now()->format('Y-m-d');

        // Update semua tagihan dengan status 'pending' dan deadline sudah lewat
        $affected = DB::table('bills')
            ->where('status', 'pending')
            ->whereDate('deadline', '<', $now)
            ->update([
                'status' => 'overdue',
                'updated_at' => Carbon::now()
            ]);

        DB::table('bills')
            ->where('status', 'overdue')
            ->whereNull('fine') // supaya tidak double update kalau sudah punya denda
            ->update([
                'fine' => DB::raw('amount * 0.2'),
                'updated_at' => Carbon::now()
            ]);

        $this->info("Total tagihan yang diubah ke overdue: $affected");
        Log::info("[$now] Overdue bills updated: $affected with fines applied");


        return 0;
    }
}
