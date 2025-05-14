<?php

namespace App\Http\Controllers;

use App\Models\CheckClock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function approvalStatus()
    {
        $data = DB::table('check_clocks')
            ->select([
                'check_clock_date as tanggal',
                'status as status_type',
                DB::raw('COUNT(status) as value'),
            ])
            ->whereIn('check_clock_type', ['in', 'out'])
            ->groupBy('check_clock_type', 'check_clock_date', 'status')
            ->orderByDesc('check_clock_date')
            ->get();
        return response()->json($data);
    }
}
