<?php
namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function getUser(){
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if(!$user){
            return response()->json(['Unauthorized'], 401);
        }
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $currentMonthBill = $user->bills()
            ->whereMonth('deadline', $currentMonth)
            ->whereYear('deadline', $currentYear)
            ->first();

        $userPhotoUrl = null;
        if (!empty($user->user_photo) && $user->user_photo !== '') {
            $userPhotoUrl = Storage::disk('s3')->temporaryUrl(
                $user->user_photo,
                Carbon::now()->addMinutes(10) // berlaku 10 menit
            );
        }
        return response()->json([
            'photo_url' => $userPhotoUrl,
            'full_name' => $user->full_name,
            'user_role' => $user->role,
            'company_name' => $user->company->name,
            'company_id' => $user->company_id,
            'plan_name' => optional(optional($user->company)->billingPlan)->plan_name,
            'bill_period' => $currentMonthBill?->period,
            'bill_status' => $currentMonthBill?->status,
            'bill_deadline' => ($currentMonthBill && $currentMonthBill->status !== 'paid') ? $currentMonthBill->deadline : null,
            'is_profile_complete' => $user->is_profile_complete,
        ]);
    }
    
    public function getUserEmployee(){
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if(!$user){
            return response()->json(['Unauthorized'], 401);
        }

        $employee = Employee::where('user_id', $user->id)->where('employee_status', 'Active')->first();

        if (!$employee) {
            return response()->json(['Cannot find the employee data'], 404);
        }

        $userPhotoUrl = null;
        if (!empty($user->user_photo) && $user->user_photo !== '') {
            $userPhotoUrl = Storage::disk('s3')->temporaryUrl(
                $user->user_photo,
                Carbon::now()->addMinutes(10) // berlaku 10 menit
            );
        }

        return response()->json([
            'id_employee' => $employee->employee_id,
            'photo_url' => $userPhotoUrl,
            'full_name' => $employee->first_name . ' ' .  $employee->last_name,
            'company_name' => $user->company->name,
        ]);
    }

    public function changePassword(Request $request){
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[\W_]/|confirmed',
        ]);

        if (!Hash::check($request->old_password, $user->password)) {
        return response()->json([
            'message' => 'Old password is incorrect.'
        ], 400);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully.'
    ]);
    }

}