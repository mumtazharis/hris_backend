<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserController extends Controller
{
    public function getUser(){
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $currentMonthBill = $user->bills()
            ->whereMonth('deadline', $currentMonth)
            ->whereYear('deadline', $currentYear)
            ->first();
        // Jika login berhasil, buat token dan kirimkan sebagai response
        return response()->json([
            'full_name' => $user->full_name,
            'user_role' => $user->role,
            'company_name' => $user->company->name,
            'plan_name' => optional(optional($user->company)->billingPlan)->plan_name,
            'bill_period' => $currentMonthBill?->period,
            'bill_status' => $currentMonthBill?->status,
            'bill_deadline' => ($currentMonthBill && $currentMonthBill->status !== 'paid') ? $currentMonthBill->deadline : null,
            'is_profile_complete' => $user->is_profile_complete,
        ]);
    }

}