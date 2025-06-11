<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Overtime;
use Illuminate\Http\Request;
use App\Models\OvertimeFormula;
use App\Models\OvertimeSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OvertimeController extends Controller
{
    public function index(){
        $hrUser = Auth::user();
        $companyId = $hrUser->company_id;
        if (!$hrUser || !$companyId) {
            return response()->json(['message' => 'HR user not authenticated or company_id not found.'], 403);
        }

        return DB::select("
            SELECT 
                o.id,
                e.employee_id, 
                CONCAT(e.first_name, ' ', e.last_name) AS name, 
                os.name AS overtime_name, 
                os.type, 
                o.date, 
                total_hour, 
                o.payroll,
                o.status
            FROM 
                overtime o
            JOIN 
                employees e ON o.employee_id = e.employee_id
            JOIN 
                overtime_settings os ON o.overtime_setting_id = os.id
            WHERE e.company_id = ?

        ", [$companyId]);
    }

    // public function create(Request $request){
    //     $hrUser = Auth::user();
    //     $companyId = $hrUser->company_id;
    //     if (!$hrUser || !$companyId) {
    //         return response()->json(['message' => 'HR user not authenticated or company_id not found.'], 403);
    //     }

    //     $validatedData = $request->validate([
    //         'employee_id' => 'required|string',
    //         'overtime_setting_id' => 'required',
    //         'date' => 'required|date',
    //         'total_hour' => 'required|numeric|min:0'
    //     ]);

    //     // Ambil dan validasi employee
    //     $employee = Employee::where('employee_id', $validatedData['employee_id'])->first();
    //     if (!$employee || $employee->company_id !== $companyId) {
    //         return response()->json(['message' => 'Unauthorized: Employee not found or not in the same company.'], 403);
    //     }

    //     // Ambil dan validasi overtime setting
    //     $overtimeSetting = OvertimeSetting::find($validatedData['overtime_setting_id']);
    //     if (!$overtimeSetting || ($overtimeSetting->company_id !== $companyId && $overtimeSetting->company_id !== null)) {
    //         return response()->json(['message' => 'Unauthorized: Overtime setting not found or not in the same company.'], 403);
    //     }

    //     if ($overtimeSetting->type === 'Flat') {
    //         $formula = OvertimeFormula::where('setting_id', $overtimeSetting->id)->first();
    //         if (!$formula) {
    //             return response()->json(['message' => 'Formula not found for Flat overtime setting.'], 400);
    //         }

    //         $minInterval = $formula->interval_hours ?: 1;
    //         if ($validatedData['total_hour'] < $minInterval) {
    //             return response()->json([
    //                 'message' => "Total hour must be at least {$minInterval} for this overtime setting."
    //             ], 422);
    //         }

    //         $validatedData['payroll'] = $this->countFlat($validatedData['total_hour'], $overtimeSetting->id);
    //     } else {
    //         $validatedData['payroll'] = $this->countGoverment($validatedData['total_hour'], $overtimeSetting->id, $employee->salary);
    //     }

    //     $overtime = Overtime::create($validatedData);

    //     return response()->json([
    //         'message' => 'Overtime created successfully',
    //         'data' => $overtime
    //     ], 201);


    // }
    public function create(Request $request)
    {
        $hrUser = Auth::user();
        $companyId = $hrUser->company_id;

        if (!$hrUser || !$companyId) {
            return response()->json(['message' => 'HR user not authenticated or company_id not found.'], 403);
        }

        // Tolak jika overtime_setting_id dikirim dari frontend
        if ($request->has('overtime_setting_id')) {
            return response()->json(['message' => 'Overtime setting ID should not be provided. It is determined automatically.'], 422);
        }

        // Validasi input TANPA overtime_setting_id
        $validatedData = $request->validate([
            'employee_id' => 'required|string',
            'date' => 'required|date',
            'total_hour' => 'required|numeric|min:0'
        ]);

        // Validasi employee
        $employee = Employee::where('employee_id', $validatedData['employee_id'])->first();
        if (!$employee || $employee->company_id !== $companyId) {
            return response()->json(['message' => 'Unauthorized: Employee not found or not in the same company.'], 403);
        }

        // Cari overtime setting dengan company_id dan status Active
        $overtimeSetting = OvertimeSetting::where('company_id', $companyId)
            ->where('status', 'Active')
            ->first();

        if (!$overtimeSetting) {
            return response()->json(['message' => 'No active overtime setting found for this company.'], 404);
        }

        // Tambahkan overtime_setting_id ke data yang tervalidasi
        $validatedData['overtime_setting_id'] = $overtimeSetting->id;

        // Hitung payroll
        if ($overtimeSetting->type === 'Flat') {
            $formula = OvertimeFormula::where('setting_id', $overtimeSetting->id)->first();
            if (!$formula) {
                return response()->json(['message' => 'Formula not found for Flat overtime setting.'], 400);
            }

            $minInterval = $formula->interval_hours ?: 1;
            if ($validatedData['total_hour'] < $minInterval) {
                return response()->json([
                    'message' => "Total hour must be at least {$minInterval} for this overtime setting."
                ], 422);
            }

            $validatedData['payroll'] = $this->countFlat($validatedData['total_hour'], $overtimeSetting->id);
        } else {
            $validatedData['payroll'] = $this->countGoverment(
                $validatedData['total_hour'],
                $overtimeSetting->id,
                $employee->salary
            );
        }

        // Buat overtime record
        $overtime = Overtime::create($validatedData);

        return response()->json([
            'message' => 'Overtime created successfully',
            'data' => $overtime
        ], 201);
    }

    private function countFlat($total_hour, $setting_id){
        $formula = OvertimeFormula::where('setting_id', $setting_id)->first();

        if (!$formula) {
            return 0; // atau lempar exception jika seharusnya selalu ada formula
        }

        if ($formula->interval_hours === 0 || is_null($formula->interval_hours)) {
            $formula->interval_hours = 1;
        }

        // Hitung payroll berdasarkan formula
        $payroll = $formula->formula * floor($total_hour / $formula->interval_hours);

        return $payroll;
    }


    private function countGoverment($total_hour, $setting_id, $monthly_salary){
        $formulas = OvertimeFormula::where('setting_id', $setting_id)
            ->orderBy('hour_start')
            ->get();

        if ($formulas->isEmpty()) {
            return 0;
        }

        $totalPayroll = 0;

        for ($i = 0; $i < ceil($total_hour); $i++) {
            $hourPortion = ($i + 1 > $total_hour) ? $total_hour - $i : 1;

            // Cari formula yang cocok untuk jam ke-i
            $formula = $formulas->first(function ($f) use ($i) {
                return $i >= $f->hour_start && $i < $f->hour_end;
            });

            if ($formula) {
                $rate = ($formula->formula * $monthly_salary) / 173;
                $totalPayroll += $rate * $hourPortion;
            }
        }

        return round($totalPayroll);
    }

    public function approval(Request $request){
       
        $hrUser = Auth::user();
        $companyId = $hrUser->company_id;
        if (!$hrUser || !$companyId) {
            return response()->json(['message' => 'HR user not authenticated or company_id not found.'], 403);
        }

        $validatedData = $request->validate([
            'overtime_id' => 'required|exists:overtime,id',
            'status' => 'required|in:Approved,Rejected',
            'reason' => 'sometimes|string'
        ]);

        $overtime = Overtime::with('employee')->find($validatedData['overtime_id']);

        if (!$overtime || !$overtime->employee || $overtime->employee->company_id !== $companyId) {
            return response()->json(['message' => 'Unauthorized or overtime record not found.'], 403);
        }

      
        // Validasi status harus masih Pending
        if ($overtime->status !== 'Pending') {
            return response()->json(['message' => 'Only overtime requests with status Pending can be approved or rejected.'], 422);
        }
          
        // Update status
        $overtime->status = $validatedData['status'];
       
        if (array_key_exists('reason', $validatedData)) {
            $overtime->rejection_reason = $validatedData['reason'];
        }
        //   return response()->json(['message' => 'ok.'], 200);
        $overtime->save();

        return response()->json(['message' => 'Overtime status updated successfully.']);
    }

    public function delete($id){
        $hrUser = Auth::user();
        $companyId = $hrUser->company_id;

        if (!$hrUser || !$companyId) {
            return response()->json(['message' => 'HR user not authenticated or company_id not found.'], 403);
        }

        $overtime = Overtime::with('employee')->find($id);

        if (!$overtime || !$overtime->employee || $overtime->employee->company_id !== $companyId) {
            return response()->json(['message' => 'Unauthorized or overtime record not found.'], 403);
        }

        // Optional: hanya boleh hapus jika status masih Pending
        if ($overtime->status !== 'Pending') {
            return response()->json(['message' => 'Only pending overtime requests can be deleted.'], 422);
        }

        $overtime->delete();

        return response()->json(['message' => 'Overtime record deleted successfully.']);
    }

}
