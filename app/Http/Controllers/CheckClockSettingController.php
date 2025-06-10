<?php

namespace App\Http\Controllers;

use App\Models\CheckClockSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckClockSettingController extends Controller
{
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'data_id' => 'required|integer|exists:check_clock_settings,id',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'radius' => 'nullable|numeric',
            ]);

            $hrUser = Auth::user();
            $companyId = $hrUser->company_id;
            $record = CheckClockSetting::where('id', $validatedData['data_id'])
                ->where('company_id', $companyId)
                ->firstOrFail();

            $record->fill([
                'latitude' => $validatedData['latitude'] ?? $record->latitude,
                'longitude' => $validatedData['longitude'] ?? $record->longitude,
                'radius' => $validatedData['radius'] ?? $record->radius,
            ]);

            if ($record->save()) {
                return response()->json([
                    'success' => ['message' => 'Successfully update the location'],
                    'data' => $record
                ], 200);
            }

            return response()->json([
                'errors' => ['message' => 'Failed to update the data']
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'errors' => ['message' => 'Record not found']
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => ['message' => 'Failed to update the data: ' . $e->getMessage()]
            ], 500);
        }
    }
}
