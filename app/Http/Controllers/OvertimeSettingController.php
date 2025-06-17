<?php

namespace App\Http\Controllers;

use App\Models\Overtime;
use App\Models\OvertimeFormula;
use App\Models\OvertimeSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OvertimeSettingController extends Controller
{
    public function index(){
        $hrUser = Auth::user();
        $companyId = $hrUser->company_id;
    
        $overtimes = DB::select("
            select os.id, os.name, os.type, os.category, os.working_days, os.status 
            from overtime_settings os
            where os.company_id = ? and os.deleted_at is null 
        ", [$companyId]);

        $result = [];

        foreach ($overtimes as $overtime) {
            // Ambil formula untuk setiap overtime
            $formulas = DB::select("
                select hour_start, hour_end, interval_hours, formula 
                from overtime_formula 
                where setting_id = ?
                order by hour_start
            ", [$overtime->id]);

            $formatted = [];

            foreach ($formulas as $f) {
                if ($overtime->type === 'Flat') {
                    // Untuk flat, hanya tampilkan rate × Hour / interval
                    $formatted[] = "Hour x {$f->formula} / {$f->interval_hours}";
                } else {
                    $start = $f->hour_start + 1;
                    $end = $f->hour_end;

                    $range = $start == $end ? "Hour {$start}" : "Hour {$start} - {$end}";
                    $formula = str_replace('*', 'x', $f->formula);

                    $formatted[] = "{$range}: {$formula} x (Monthly Salay / 173)";
                }
            }

            $result[] = [
                'id' => $overtime->id,
                'name' => $overtime->name,
                'type' => $overtime->type,
                'category' => $overtime->category,
                'working_days' => $overtime->working_days,
                'status' =>$overtime->status,
                'formulas' => $formatted,
            ];
        }

        return response()->json($result);
    }

    public function store(Request $request){
        $hrUser = Auth::user();
        $companyId = $hrUser->company_id;
        if (!$hrUser || !$companyId) {
            return response()->json(['message' => 'HR user not authenticated or company_id not found.'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:Regular Weekday,Shortday Holiday,Holiday',
            'interval_hours' => 'required|integer|min:1|max:24',
            'rate' => 'required|integer|min:1'
        ]);
        $overtime = collect($validatedData)->except(['rate', 'interval_hours'])->all();
        $overtime['company_id'] = $companyId;
        $overtime['type'] = 'Flat';
        $overtime['status'] = 'Inactive';
        $formula['formula'] = $request['rate'];
        $formula['interval_hours'] = $request['interval_hours'];
        DB::beginTransaction();

        try {
            $overtime = OvertimeSetting::create($overtime);
            $formula['setting_id'] = $overtime->id;

            $formula = OvertimeFormula::create($formula);

            DB::commit();
            return response()->json(['message' => 'Success to create overtime dan formula.'], 200);;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create overtime and formula.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id){
        $hrUser = Auth::user();
        $companyId = $hrUser->company_id;
        if (!$hrUser || !$companyId) {
            return response()->json(['message' => 'HR user not authenticated or company_id not found.'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:Regular Weekday,Shortday Holiday,Holiday',
            'interval_hours' => 'required|numeric|max:24',
            'rate' => 'required|numeric'
        ]);

        $overtime = OvertimeSetting::where('id', $id)
            ->where('company_id', $companyId)
            ->where('type', 'Flat')
            ->first();

        if (!$overtime) {
            return response()->json(['message' => 'Only flat-type overtimes can be updated, or overtime not found.'], 403);
        }

        DB::beginTransaction();
        try {
            // Update overtime setting
            $overtime->update([
                'name' => $validatedData['name'],
                'category' => $validatedData['category'],
            ]);

            // Update formula terkait
            OvertimeFormula::where('setting_id', $overtime->id)->update([
                'formula' => $validatedData['rate'],
                'interval_hours' => $validatedData['interval_hours'],
            ]);

            DB::commit();
            return response()->json(['message' => 'Overtime flat updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update overtime flat.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function delete($id)
    {
        $hrUser = Auth::user();
        $companyId = $hrUser->company_id;

        if (!$hrUser || !$companyId) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $overtime = OvertimeSetting::where('id', $id)
            ->where('company_id', $companyId)
            ->where('type', 'Flat')
            ->first();

        if (!$overtime) {
            return response()->json(['message' => 'Only flat-type overtime setting can be deleted or it does not exist.'], 404);
        }
        if ($overtime->status === 'Active') {
            return response()->json(['message' => 'Active overtime setting cannot be deleted.'], 400);
        }

        $overtime->delete();

        return response()->json(['message' => 'Overtime setting has been deleted successfully.'], 200);
    }

    public function changeStatus(Request $request)
    {
        $hrUser = Auth::user();
        $companyId = $hrUser->company_id;

        if (!$hrUser || !$companyId) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $overtimeSettingId = $request->input('overtime_setting_id');

        if (!$overtimeSettingId) {
            return response()->json(['message' => 'Missing overtime_setting_id'], 400);
        }

        // Update all settings to "Inactive" for this company
        DB::table('overtime_settings')
            ->where('company_id', $companyId)
            ->update(['status' => 'Inactive']);

        // Set the selected one to "Active"
        $updated = DB::table('overtime_settings')
            ->where('company_id', $companyId)
            ->where('id', $overtimeSettingId)
            ->update(['status' => 'Active']);

        if ($updated) {
            return response()->json(['message' => 'Overtime setting updated successfully.']);
        } else {
            return response()->json(['message' => 'Overtime setting not found or not updated.'], 400);
        }
    }

}
