<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function show(){
        $companyId = Auth::user()->company_id;
        return DB::select("
            select 
                c.company_id, c.name
            from companies c where c.company_id = ?
        ", [$companyId]);
    }

    public function getCompanyDepPos(){
        $companyId = Auth::user()->company_id;
        return DB::select("
            select  d.id as \"id_department\", d.name as \"Department\", p.id as \"id_position\", p.name as \"Position\"
            from departments d 
            left join positions p on p.department_id = d.id
            WHERE d.company_id = ?
            ORDER BY d.id
        ", [$companyId]);
    }

    public function editCompany(Request $request){
        
        $companyId = Auth::user()->company_id;
        $request->validate([
            'company_name' => 'required|string',
            'max_annual_leave' => 'required|integer|min:12',
            'max_weekly_overtime' => 'required|integer|max:18|min:0'
        ]);

         if (!$companyId) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        $company = Company::where('company_id',$companyId)->first();

        if (!$company) {
            return response()->json(['message' => 'Company data not found'], 404);
        }

        // Update nama perusahaan
        $company->name = $request->input('company_name');
        $company->max_annual_leave = $request->input('max_annual_leave');
        $company->max_weekly_overtime = $request->input('max_weekly_overtime');
        $company->save();

        return response()->json(['message' => 'Company name updated successfully'], 200);

    }

    public function addDepartment(Request $request){
        $companyId = Auth::user()->company_id;
        $request->validate([
            'department_name' => ['required', 'string'],
        ]);

        // Check manually for existing department (case-insensitive)
        $exists = Department::where('company_id', $companyId)
            ->whereRaw('LOWER(name) = ?', [strtolower($request->department_name)])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Department already exist'], 422);
        }

        $department = Department::create([
            'name' => $request->input('department_name'),
            'company_id' => $companyId,
        ]);

        return response()->json(['message' => 'Department created successfully', 'department_id' => $department->id,]);
    }

    public function editDepartment(Request $request){
        $companyId = Auth::user()->company_id;
        $request->validate([
            'id' => 'required|integer|exists:departments,id',
            'department_name' => 'required|string',
        ]);

        // Cek apakah department milik user
        $department = Department::where('id', $request->id)
            ->where('company_id', $companyId)
            ->first();

        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        // Cek nama sudah ada (case-insensitive) di company yang sama dan bukan diri sendiri
        $exists = Department::where('company_id', $companyId)
            ->whereRaw('LOWER(name) = ?', [strtolower($request->department_name)])
            ->where('id', '!=', $request->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Department already exist'], 422);
        }

        // Update nama departemen
        $department->name = $request->department_name;
        $department->save();

        return response()->json(['message' => 'Department updated successfully']);
    }

    public function deleteDepartment(Request $request){
        $companyId = Auth::user()->company_id;
        $request->validate([
            'department_id' => 'required|exists:departments,id'
        ]);

        $department = Department::where('id', $request->department_id)
        ->where('company_id', $companyId)
        ->first();

        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        // Cek apakah ada employee yang punya posisi di department ini
        $hasEmployees = Employee::whereHas('position', function ($query) use ($request) {
            $query->where('department_id', $request->department_id);
        })->exists();

        if ($hasEmployees) {
            return response()->json([
                'message' => 'Cannot delete department. There are employees assigned to this department.'
            ], 400);
        }

        // Hapus department
        $department->delete();

        return response()->json(['message' => 'Department deleted successfully']);
    }

    public function addPosition(Request $request){
        $companyId = Auth::user()->company_id;
        $request->validate([
            'department_id' => 'required',
            'position_name' => 'required|string'
        ]);

        $department = Department::where('id', $request->department_id)
        ->where('company_id', $companyId)
        ->first();

        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

         // Cek duplikat nama posisi (case-insensitive) dalam department yang sama
        $exists = Position::where('department_id', $request->department_id)
            ->whereRaw('LOWER(name) = ?', [strtolower($request->position_name)])
            ->exists();

        if ($exists) {
             return response()->json(['message' => 'Position already exist in this department'], 422);
        }

        // Simpan posisi
        $position = Position::create([
            'department_id' => $request->department_id,
            'name' => $request->position_name,
        ]);
        return response()->json(['message' => 'Position created successfully', 'position_id' => $position->id,], 200);
    }

    public function editPosition(Request $request){
        $companyId = Auth::user()->company_id;

        // Validasi struktur request
        $request->validate([
            'department_id' => 'required|integer|exists:departments,id',
            'position_id' => 'required|integer|exists:positions,id',
            'position_name' => 'required|string|max:255',
        ]);

        // Pastikan department memang milik perusahaan user
        $department = Department::where('id', $request->department_id)
            ->where('company_id', $companyId)
            ->first();

        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        // Cari posisi dan pastikan milik department ini
        $position = Position::where('id', $request->position_id)
            ->where('department_id', $request->department_id)
            ->first();

        if (!$position) {
            return response()->json(['message' => 'Position not found', 404]);
        }

        // Update nama posisi
        $position->name = $request->position_name;
        $position->save();

        return response()->json(['message' => 'Position updated successfully'], 200);
    }

    public function deletePosition(Request $request){
        $companyId = Auth::user()->company_id;
        $request->validate([
            'position_id' => 'required|exists:positions,id'
        ]);
        
         $position = Position::where('id', $request->position_id)
        ->whereHas('department', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->first();

        if (!$position) {
            return response()->json(['message' => 'Position not found or not authorized'], 404);
        }

        // Cek apakah ada employee yang memakai posisi ini
        $hasEmployees = Employee::where('position_id', $position->id)->exists();

        if ($hasEmployees) {
            return response()->json([
                'message' => 'Cannot delete position. There are employees assigned to this position.'
            ], 400);
        }

        // Hapus posisi
        $position->delete();

        return response()->json(['message' => 'Position deleted successfully']);

    }

}
