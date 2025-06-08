<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\GenerateMonthlyBills;
use App\Console\Commands\updateOverdueAdminPayments;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::command(GenerateMonthlyBills::class)
//     ->monthlyOn(28, '00:00');

Schedule::command(GenerateMonthlyBills::class)->everyMinute();
Schedule::command(UpdateOverdueAdminPayments::class)->everyMinute();
