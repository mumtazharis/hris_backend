<?php

namespace App\Http\Controllers;

use App\Models\CheckClock;
use App\Models\CheckClockSetting;
use App\Models\CheckClockSettingTimes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckClockSettingTimesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $hrUser = Auth::user();
        $companyId = $hrUser->company_id;
        $location = CheckClockSetting::select(
            'check_clock_settings.id as data_id',
            'check_clock_settings.latitude',
            'check_clock_settings.longitude',
            'check_clock_settings.radius'
        )
            ->where('check_clock_settings.name', '=', 'WFO')
            ->where('check_clock_settings.company_id', $companyId)
            ->get();

        $times = CheckClockSettingTimes::select(
            'check_clock_setting_times.id as data_id',
            'check_clock_settings.name as worktype',
            'check_clock_settings.id as worktype_id',
            'check_clock_setting_times.day',
            'check_clock_setting_times.clock_in',
            'check_clock_setting_times.min_clock_in',
            'check_clock_setting_times.max_clock_in',
            'check_clock_setting_times.clock_out',
            'check_clock_setting_times.max_clock_out',
            'check_clock_settings.latitude',
            'check_clock_settings.longitude',
            'check_clock_settings.radius',
            'check_clock_setting_times.created_at',
        )
            ->join('check_clock_settings', 'check_clock_setting_times.ck_setting_id', '=', 'check_clock_settings.id')
            ->whereNull('check_clock_setting_times.deleted_at')
            ->where('check_clock_settings.company_id', $companyId)
            ->orderBy('check_clock_setting_times.created_at', 'desc')
            ->distinct()
            ->get();
        if ($times) {
            return response()->json(['location_rule' => $location, 'ckdata' => $times]);
        }
        return response()->json(['errors' => ['message' => 'Failed to feth the data']], 401);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $checkClockSettingTimes)
    {
        // try {
        $validated = $request->validate([
            'minClockIn' => 'nullable|date_format:H:i',
            'clockIn' => 'nullable|date_format:H:i',
            'maxClockIn' => 'nullable|date_format:H:i',
            'clockOut' => 'nullable|date_format:H:i',
            'maxClockOut' => 'nullable|date_format:H:i',
        ]);

        $hrUser = Auth::user();
        $companyId = $hrUser->company_id;
        $record = CheckClockSettingTimes::where('id',$checkClockSettingTimes)
            ->whereHas('checkClockSetting', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->firstOrFail();

        if (!$record) {
            return response()->json(['errors' => ['message' => 'Record not found']], 404);
        }

        $record->clock_in = $validated['clockIn'];
        $record->max_clock_in = $validated['maxClockIn'];
        $record->min_clock_in = $validated['minClockIn'];
        // if (isset($validated['clockOut'])) {
            $record->clock_out = $validated['clockOut'];
        // }

        // if (isset($validated['maxClockOut'])) {
            $record->max_clock_out = $validated['maxClockOut'];
        // }
        if ($record->save()) {
            return response()->json(['success' => ['message' => 'Successfully update the setting times']], 200);
        }
        return response()->json(['errors' => ['message' => 'Failed to update the data']], 400);
    }
}
