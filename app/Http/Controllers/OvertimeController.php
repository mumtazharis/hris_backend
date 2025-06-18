<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Overtime;
use Illuminate\Http\Request;
use App\Models\OvertimeFormula;
use App\Models\OvertimeSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OvertimeController extends Controller
{
    public function index(){
        $hrUser = Auth::user();
        $companyId = $hrUser->company_id;
        if (!$hrUser || !$companyId) {
            return response()->json(['message' => 'HR user not authenticated or company_id not found.'], 403);
        }

        $overtimes = DB::select("
        SELECT 
            o.id,
            e.employee_id,
            e.employee_photo,
            o.evidence,
            CONCAT(e.first_name, ' ', e.last_name) AS name, 
            os.name AS overtime_name, 
            os.type, 
            o.date, 
            TO_CHAR(o.start_hour, 'HH24:MI') AS start_hour, 
            TO_CHAR(o.end_hour, 'HH24:MI') AS end_hour, 
            o.payroll,
            o.status
        FROM 
            overtime o
        JOIN 
            employees e ON o.employee_id = e.employee_id
        JOIN 
            overtime_settings os ON o.overtime_setting_id = os.id
        WHERE 
            e.company_id = ?
    ", [$companyId]);

    // Tambahkan AWS URL
    foreach ($overtimes as &$overtime) {
        // Generate employee photo URL
        $overtime->employeePhoto = !empty($overtime->employee_photo)
            ? Storage::disk('s3')->temporaryUrl($overtime->employee_photo, Carbon::now()->addMinutes(1000))
            : null;

        // Generate evidence file URL
        $overtime->overtimeEvidenceUrl = !empty($overtime->evidence)
            ? Storage::disk('s3')->temporaryUrl($overtime->evidence, Carbon::now()->addMinutes(1000))
            : null;

        // Optionally remove raw file path fields
        unset($overtime->employee_photo);
        unset($overtime->evidence);
    }

    return response()->json($overtimes);
    }

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
            'start_hour' => 'required|date_format:H:i',
            'end_hour' => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($request) {
                    if (strtotime($value) <= strtotime($request->start_hour)) {
                        $fail('End hour must be after start hour.');
                    }
                },
            ],
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
        
        $start = Carbon::createFromFormat('H:i', $validatedData['start_hour']);
        $end = Carbon::createFromFormat('H:i', $validatedData['end_hour']);
        $totalHour = $start->diffInMinutes($end) / 60;

        if ($conflict = $this->hasOverlappingOvertime($validatedData['employee_id'], $validatedData['date'], $start, $end)) {
            return response()->json([
                'message' => "Overtime time range overlaps with an existing record ({$conflict->start_hour} - {$conflict->end_hour})."
            ], 422);
        }
        
        if ($this->hasExceededWeeklyOvertimeLimit($validatedData['employee_id'], Carbon::parse($validatedData['date']), $totalHour, $companyId)) {
            return response()->json([
                'message' => "Employee has exceeded the weekly overtime limit."
            ], 422);
        }
     
        // Hitung payroll
        if ($overtimeSetting->type === 'Flat') {
            $formula = OvertimeFormula::where('setting_id', $overtimeSetting->id)->first();
            if (!$formula) {
                return response()->json(['message' => 'Formula not found for Flat overtime setting.'], 400);
            }

            $minInterval = $formula->interval_hours ?: 1;
            if ($totalHour < $minInterval) {
                return response()->json([
                    'message' => "Total hour must be at least {$minInterval} for this overtime setting."
                ], 422);
            }

            $validatedData['payroll'] = $this->countFlat($totalHour, $overtimeSetting->id);
        } else {
            $validatedData['payroll'] = $this->countGoverment(
                $totalHour,
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

    private function hasOverlappingOvertime($employeeId, $date, $start, $end): ? Overtime {
        return Overtime::where('employee_id', $employeeId)
            ->where('status', '!=', 'Rejected')
            ->where('date', $date)
            ->get()
            ->first(function ($overtime) use ($start, $end) {
                $existingStart = Carbon::createFromFormat('H:i:s', $overtime->start_hour);
                $existingEnd = Carbon::createFromFormat('H:i:s', $overtime->end_hour);
                return $start < $existingEnd && $end > $existingStart;
            });
    }

    private function hasExceededWeeklyOvertimeLimit(string $employeeId, Carbon $date, float $newHours, string $companyId): ? string {
        $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $endOfWeek = $date->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $weeklyTotal = Overtime::where('employee_id', $employeeId)
            ->where('status', 'Approved')
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->get()
            ->sum(function ($overtime) {
                $start = Carbon::createFromFormat('H:i:s', $overtime->start_hour);
                $end = Carbon::createFromFormat('H:i:s', $overtime->end_hour);
                return $start->diffInMinutes($end) / 60;
            });

        $maxWeekly = Company::where('company_id', $companyId)->value('max_weekly_overtime') ?? 0;

        return ($weeklyTotal + $newHours) > $maxWeekly;
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
            'reason' => 'required|string'
        ]);

        $overtime = Overtime::with('employee')->find($validatedData['overtime_id']);

        if (!$overtime || !$overtime->employee || $overtime->employee->company_id !== $companyId) {
            return response()->json(['message' => 'Unauthorized or overtime record not found.'], 403);
        }

      
        // Validasi status harus masih Pending
        if ($overtime->status !== 'Pending') {
            return response()->json(['message' => 'Only overtime requests with status Pending can be approved or rejected.'], 422);
        }
        
        if ($validatedData['status'] === 'Approved') {
            $employeeId = $overtime->employee_id;
            $date = Carbon::parse($overtime->date);
            $start = Carbon::createFromFormat('H:i:s', $overtime->start_hour);
            $end = Carbon::createFromFormat('H:i:s', $overtime->end_hour);
            $totalHour = $start->diffInMinutes($end) / 60;

            // Cek batas maxWeekly
            if ($this->hasExceededWeeklyOvertimeLimit($employeeId, $date, $totalHour, $companyId)) {
                return response()->json([
                    'message' => "Employee has exceeded the weekly overtime limit."
                ], 422);
            }

            // Cek overlap waktu
            $existingOvertimes = Overtime::where('employee_id', $employeeId)
                ->where('status', 'Approved')
                ->where('date', $overtime->date)
                ->where('id', '!=', $overtime->id) // Kecualikan dirinya sendiri
                ->get();

            foreach ($existingOvertimes as $existing) {
                $existingStart = Carbon::createFromFormat('H:i:s', $existing->start_hour);
                $existingEnd = Carbon::createFromFormat('H:i:s', $existing->end_hour);

                if ($start < $existingEnd && $end > $existingStart) {
                    return response()->json([
                        'message' => "Overtime time range overlaps with an existing approved record ({$existing->start_hour} - {$existing->end_hour})."
                    ], 422);
                }
            }
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
