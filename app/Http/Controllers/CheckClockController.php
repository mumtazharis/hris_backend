<?php

namespace App\Http\Controllers;

use App\Models\AbsentDetail;
use App\Models\CheckClock;
use \App\Models\AccessToken;
use App\Models\CheckClockSettingTimes;
use App\Models\PresentDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CheckClockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companyId = Auth::user()->company_id;

        $checkClocks = DB::table('check_clocks as cc')
            ->join('employees as e', 'cc.employee_id', '=', 'e.id')
            ->join('positions as p', 'e.position_id', '=', 'p.id')
            ->join('check_clock_settings as ccs', 'cc.ck_setting_id', '=', 'ccs.id')
            ->join('check_clock_setting_times as ccst', function ($join) {
                $join->on('ccst.ck_setting_id', '=', 'ccs.id')
                    ->whereRaw("LOWER(ccst.day) = LOWER(TRIM(TO_CHAR(cc.check_clock_date, 'Day')))");
            })
            ->leftJoin('present_detail_cc as pdc', 'pdc.ck_id', '=', 'cc.id')
            ->leftJoin('absent_detail_cc as adc', 'adc.ck_id', '=', 'cc.id')
            ->leftJoin('users as u', 'cc.submitter_id', '=', 'u.id')
            ->where('e.company_id', $companyId)
            ->groupBy([
                'cc.id',
                'u.full_name',
                'e.employee_id',
                'cc.employee_id',
                'e.first_name',
                'e.last_name',
                'p.name',
                'cc.check_clock_date',
                'ccs.name'
            ])
            ->select([
                'cc.id as data_id',
                'u.full_name as submitter_name',
                'e.employee_id as employee_number',
                'cc.employee_id',
                DB::raw("CONCAT(e.first_name, ' ', e.last_name) as employee_name"),
                'p.name as position',
                'cc.check_clock_date as date',
                'ccs.name as work_type',
                DB::raw('MAX(CASE WHEN pdc.check_clock_type = \'in\' THEN pdc.check_clock_time END) as clock_in'),
                DB::raw('MAX(CASE WHEN pdc.check_clock_type = \'out\' THEN pdc.check_clock_time END) as clock_out'),
                DB::raw('MAX(CASE WHEN pdc.check_clock_type = \'in\' THEN pdc.latitude END) as latitude'),
                DB::raw('MAX(CASE WHEN pdc.check_clock_type = \'in\' THEN pdc.longitude END) as longitude'),
                DB::raw('MAX(CASE WHEN pdc.check_clock_type = \'in\' THEN pdc.evidence END) as present_evidence'),
                DB::raw('MAX(CASE WHEN adc.start_date IS NOT NULL THEN adc.start_date END) as absent_start_date'),
                DB::raw('MAX(CASE WHEN adc.end_date IS NOT NULL THEN adc.end_date END) as absent_end_date'),
                DB::raw('MAX(adc.evidence) as absent_evidence'),
                DB::raw('MAX(cc.status_approval) as approval_status'),
                DB::raw('MAX(cc.reject_reason) as reject_reason'),
                DB::raw("
        CASE
            WHEN MAX(CASE WHEN pdc.check_clock_type = 'in' THEN pdc.check_clock_time END) IS NULL THEN cc.status
            WHEN MAX(CASE WHEN pdc.check_clock_type = 'in' THEN pdc.check_clock_time END) < MIN(ccst.min_clock_in) THEN 'Invalid (Too Early)'
            WHEN MAX(CASE WHEN pdc.check_clock_type = 'in' THEN pdc.check_clock_time END) <= MIN(ccst.clock_in) THEN 'On Time'
            WHEN MAX(CASE WHEN pdc.check_clock_type = 'in' THEN pdc.check_clock_time END) <= MIN(ccst.max_clock_in) THEN 'Late'
            ELSE 'Absent'
        END as status
    "),
            ])
            ->orderBy('cc.check_clock_date', 'DESC')
            ->orderBy('employee_name')
            ->get();

        // Generate temporary S3 URLs
        $checkClocks->transform(function ($item) {
            $item->present_evidence_url = $item->present_evidence
                ? Storage::disk('s3')->temporaryUrl($item->present_evidence, Carbon::now()->addMinutes(30))
                : null;

            $item->absent_evidence_url = $item->absent_evidence
                ? Storage::disk('s3')->temporaryUrl($item->absent_evidence, Carbon::now()->addMinutes(30))
                : null;

            return $item;
        });
        return response()->json($checkClocks);
    }

    public function getEmployeeData()
    {
        $companyId = Auth::user()->company_id;

        // SELECT 
        //     e.id AS data_id,
        //     e.employee_id AS id_employee,
        //     CONCAT(e.first_name, \' \', e.last_name) AS name,
        //     ck.check_clock_date,
        //     p.name AS position,
        //     ccs.name AS worktype,
        //     MAX(CASE WHEN pd.check_clock_type = \'in\' THEN pd.check_clock_time END) AS clock_in,
        //     MAX(CASE WHEN pd.check_clock_type = \'out\' THEN pd.check_clock_time END) AS clock_out
        // FROM employees e
        // JOIN positions p ON e.position_id = p.id
        // LEFT JOIN check_clocks ck ON ck.employee_id = e.id
        // LEFT JOIN present_detail_cc pd ON pd.ck_id = ck.id
        // LEFT JOIN check_clock_settings ccs ON ccs.id = ck.ck_setting_id
        // WHERE e.company_id = ?
        // GROUP BY e.id, e.employee_id, e.first_name, e.last_name, p.name, ccs.name, ck.check_clock_date
        // ORDER BY e.first_name ASC;
        $query = '
        SELECT 
            e.id AS data_id,
            e.employee_id AS id_employee,
            CONCAT(e.first_name, \' \', e.last_name) AS Name,
            ck.check_clock_date,
            p.name AS position,
            ccs.name AS workType,
            MAX(CASE WHEN pd.check_clock_type = \'in\' THEN pd.check_clock_time END) AS clock_in,
            MAX(CASE WHEN pd.check_clock_type = \'out\' THEN pd.check_clock_time END) AS clock_out
        FROM employees e
        JOIN positions p ON e.position_id = p.id
        LEFT JOIN (
            SELECT *
            FROM check_clocks
            WHERE DATE(check_clock_date) = CURRENT_DATE
        ) ck ON ck.employee_id = e.id
        LEFT JOIN present_detail_cc pd ON pd.ck_id = ck.id
        LEFT JOIN check_clock_settings ccs ON ccs.id = ck.ck_setting_id
        WHERE e.company_id = ?
        GROUP BY e.id, e.employee_id, e.first_name, e.last_name, p.name, ccs.name, ck.check_clock_date
        ORDER BY e.first_name ASC
        ';

        $data = DB::select($query, [$companyId]);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $user = Auth::user()->id;
        $companyId = Auth::user()->company_id;

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'ck_setting_name' => 'required|in:WFA,WFO',
            'check_clock_date' => 'required|date',
            'status' => 'required|in:Present,Sick Leave,Annual Leave',
            'status_approval' => 'nullable|in:Approved,Pending,Rejected',
            'reject_reason' => 'nullable|string',

            'check_clock_type' => 'nullable|required_if:status,Present|in:in,out',
            'check_clock_time' => 'required_if:status,Present|date_format:H:i',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'evidence' => 'required_if:status,Sick Leave,Annual Leave|file|mimes:jpeg,png,jpg|max:5120',

            'start_date' => 'nullable|required_if:status,Sick Leave,Annual Leave|date|after_or_equal:today',
            'end_date' => 'nullable|required_if:status,Sick Leave,Annual Leave|date|after_or_equal:today',
        ], [
            'employee_id.required' => 'Employee is required.',
            'employee_id.exists' => 'Selected employee does not exist.',

            'ck_setting_name.required' => 'Work Type is required.',
            'ck_setting_name.in' => 'Work Type must be either WFA or WFO.',

            'check_clock_date.required' => 'Check clock date is required.',
            'check_clock_date.date' => 'Check clock date must be a valid date.',

            'status.required' => 'Attendance Type is required.',
            'status.in' => 'Attendance Type must be Present, Sick Leave, or Annual Leave.',

            'status_approval.in' => 'Status approval must be Approved, Pending, or Rejected.',

            'reject_reason.string' => 'Reject reason must be a text.',

            'check_clock_type.required_if' => 'Check clock type is required when status is Present.',
            'check_clock_type.in' => 'Check clock type must be either "in" or "out".',

            'check_clock_time.required_if' => 'Check clock time is required when status is Present.',
            'check_clock_time.date_format' => 'Invalid Check Clock Time Input.',

            'evidence.required_if' => 'Evidence is required when status is Sick Leave or Annual Leave.',
            'evidence.image' => 'Evidence must be an image file.',
            'evidence.max' => 'Evidence image must not exceed 5MB.',

            'start_date.required_if' => 'Start date is required when status is Sick Leave or Annual Leave.',
            'start_date.date' => 'Start date must be a valid date.',
            'start_date.after_or_equal' => 'Start date must be today or a future date.',

            'end_date.required_if' => 'End date is required when status is Sick Leave or Annual Leave.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after_or_equal' => 'End date must be today or a future date.',
        ]);

        $evidencePath = null;

        if ($request->status === 'Present') {
            if ($request->hasFile('evidence')) {
                $evidencePath = $request->file('evidence')->store('evidence_present', 's3');
            }
            if ($request->check_clock_type === 'in') {
                // Check if CheckClock exists for this employee and date (with any PresentDetail)
                $exists = CheckClock::where('employee_id', $request->employee_id)
                    ->whereDate('check_clock_date', \Carbon\Carbon::parse($request->check_clock_date)->toDateString())
                    ->exists();

                if ($exists) {
                    return response()->json(['errors' => ['message' => 'This employee have already check clock today']], 422);
                }

                // Create CheckClock and PresentDetail for 'in'
                $ckSetting = \App\Models\CheckClockSetting::where('company_id', $companyId)
                    ->where('name', $request->ck_setting_name)->first();

                if (!$ckSetting) {
                    return response()->json(['error' => ['message' => 'Invalid work type, should be either WFA or WFO.']], 422);
                }

                DB::beginTransaction();
                try {
                    $checkClock = CheckClock::create([
                        'employee_id' => $request->employee_id,
                        'submitter_id' => $user,
                        'ck_setting_id' => $ckSetting->id,
                        'check_clock_date' => $request->check_clock_date,
                        'status' => $request->status,
                        'status_approval' => $request->status_approval ?? 'Pending',
                        'reject_reason' => $request->reject_reason,
                    ]);

                    PresentDetail::create([
                        'ck_id' => $checkClock->id,
                        'check_clock_type' => 'in',
                        'check_clock_time' => $request->check_clock_time,
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                        'evidence' => $evidencePath,
                    ]);

                    DB::commit();
                    return response()->json(['success' => ['message' => 'Check clock with clock-in recorded successfully.']], 200);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['errors' => ['message' => $e->getMessage()]], 500);
                }
            } elseif ($request->check_clock_type === 'out') {
                // For 'out', find existing CheckClock for employee and date
                $checkClock = CheckClock::where('employee_id', $request->employee_id)
                    ->whereDate('check_clock_date', \Carbon\Carbon::parse($request->check_clock_date)->toDateString())
                    ->first();

                if (!$checkClock) {
                    return response()->json(['errors' => ['message' => 'No existing check clock found for clock-out. Please clock-in first.']], 422);
                }

                // Create PresentDetail with type 'out'
                PresentDetail::create([
                    'ck_id' => $checkClock->id,
                    'check_clock_type' => 'out',
                    'check_clock_time' => $request->check_clock_time,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'evidence' => $evidencePath,
                ]);

                return response()->json(['success' => ['message' => 'Clock-out recorded successfully.']], 201);
            }
        } else {
            if ($request->hasFile('evidence')) {
                $evidencePath = $request->file('evidence')->store('evidence_absent', 's3');
            }
            // Handle Sick Leave, Annual Leave as before
            $exists = CheckClock::where('employee_id', $request->employee_id)
                ->whereDate('check_clock_date', \Carbon\Carbon::parse($request->check_clock_date)->toDateString())
                ->exists();

            if ($exists) {
                return response()->json(['errors' => ['message' => 'This employee have already check clock today']], 422);
            }

            $ckSetting = \App\Models\CheckClockSetting::where('company_id', $companyId)
                ->where('name', $request->ck_setting_name)->first();

            if (!$ckSetting) {
                return response()->json(['error' => ['message' => 'Invalid work type, should be either WFA or WFO.']], 422);
            }

            $userIsAdmin = Auth::user()->role == "admin";

            $statusApproval = $userIsAdmin
                ? 'Approved'
                : ($request->status_approval ?? 'Pending');

            DB::beginTransaction();
            try {
                $checkClock = CheckClock::create([
                    'employee_id' => $request->employee_id,
                    'submitter_id' => $user,
                    'ck_setting_id' => $ckSetting->id,
                    'check_clock_date' => $request->check_clock_date,
                    'status' => $request->status,
                    'status_approval' => $statusApproval,
                    'reject_reason' => $request->reject_reason,
                ]);

                AbsentDetail::create([
                    'ck_id' => $checkClock->id,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'evidence' => $evidencePath,
                ]);

                DB::commit();
                return response()->json(['success' => ['message' => 'Check clock recorded successfully.']], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['errors' => ['message' => $e->getMessage()]], 500);
            }
        }
    }

    public function reject(Request $request)
    {
        $request->validate([
            'data_id' => 'required|exists:check_clocks,id',
            'reject_reason' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $checkClock = CheckClock::findOrFail($request->data_id);
            $checkClock->update([
                'reject_reason' => $request->reject_reason,
                'status_approval' => 'Rejected',
            ]);

            DB::commit();
            return response()->json(['success' => ['message' => 'Check clock rejected successfully.']], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => ['message' => $e->getMessage()]], 500);
        }
    }

    public function approval(Request $request, string $checkClock)
    {
        $validator = $request->validate([
            'status_approval' => 'required|string|in:Approved,Pending,Rejected',
            'reject_reason' => 'required_if:status_approval,Rejected|max:255',
        ]);

        $record = CheckClock::findOrFail($checkClock);

        $record->status_approval = $request->status_approval;
        if ($validator['status_approval'] === 'Rejected') {
            $record->reject_reason = $validator['reject_reason'];
        } else {
            $record->reject_reason = null; // or "No Reason Provided" if you prefer
        }
        $record->save();

        return response()->json(['message' => 'Check clock status updated successfully']);
    }
}
