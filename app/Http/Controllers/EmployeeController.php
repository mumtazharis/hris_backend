<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Str;
use Carbon\Carbon; 

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::all();
        return response()->json($employees);
    }

    

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:17|unique:employees',
            'email' => 'nullable|string|max:100|unique:employees',
            'nik' => 'nullable|string|max:16|unique:employees',
            'gender' => 'nullable|in:Male,Female',
            'education' => 'nullable|in:SD,SMP,SMA,D3,D4,S1,S2,S3',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'blood_type' => 'nullable|in:A,B,AB,O,Unknown',
            'citizenship' => 'nullable|string|max:100',
            'marital_status' => 'nullable|in:Single,Married,Divorced,Widowed',
            'religion' => 'nullable|string|max:100',
            'position_id' => 'nullable|exists:positions,id',
            'department_id' => 'nullable|exists:departments,id',
            'work_status' => 'nullable|in:Permanent,Internship,Part-time,Outsource',
            'address' => 'nullable|string|max:255',
            'employee_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', 
        ]);

        // Generate employee_id: Tahun Saat Ini (2 Digit) + 4 Angka Acak Unik
        $currentYearTwoDigits = date('y');
        do {
            $uniqueRandomCode = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $generatedEmployeeId = "{$currentYearTwoDigits}{$uniqueRandomCode}";
        } while (Employee::where('employee_id', $generatedEmployeeId)->exists());

        $validated['employee_id'] = $generatedEmployeeId;

        // generate unique file name & save directory
        if ($request->hasFile('employee_photo')) {
            // Membuat nama file unik agar tidak bentrok dengan foto lain
            $fileName = Str::random(8) . '.' . $request->file('employee_photo')->getClientOriginalExtension();
            
            // Menyimpan file foto ke direktori yang ditentukan
            //    Secara default, 'public/employee_photos' akan disimpan di:
            //    'storage/app/public/employee_photos/' di server Anda.
            //    Agar bisa diakses via web, pastikan Anda sudah menjalankan 'php artisan storage:link'.
            $request->file('employee_photo')->storeAs('public/employee_photos', $fileName);
            $validated['employee_photo'] = $fileName;
        }

        // prefix +62 in phone
        if (isset($validated['phone']) && !empty($validated['phone'])) {
            $phone = $validated['phone'];
            $phone = preg_replace('/[^0-9]/', '', $phone); // Tetap sanitasi semua non-digit
        
            // Jika diasumsikan input dari FE selalu '812xxxxxx' (tanpa 0 atau +62)
            // Cukup pastikan diawali '62', lalu tambahkan '+'
            if (!Str::startsWith($phone, '62')) {
                $phone = '62' . $phone;
            }
            $validated['phone'] = '+' . $phone;
        }

        $employee = Employee::create($validated);
        return response()->json($employee, 201);
    }



    /**
     * Display the specified resource.
     *
     * @param  string  $employee_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $employee_id)
    {
        $employee = Employee::where('employee_id', $employee_id)->first();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        return response()->json($employee);
    }



    public function update(Request $request, string $employee_id)
    {
        $employee = Employee::where('employee_id', $employee_id)->first();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        // 2. Definisi aturan validasi
        // 'sometimes' agar validasi hanya berjalan jika field ada di request
        $validated = $request->validate([
            // Personal Information
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'nik' => 'sometimes|nullable|string|max:16|unique:employees,nik,' . $employee->id,
            'gender' => 'sometimes|nullable|in:Male,Female',
            'education' => 'sometimes|nullable|in:SD,SMP,SMA,D3,D4,S1,S2,S3',
            'birth_place' => 'sometimes|nullable|string|max:100',
            'birth_date' => 'sometimes|nullable|date',
            'citizenship' => 'sometimes|nullable|string|max:100',
            'marital_status' => 'sometimes|nullable|in:Single,Married,Divorced,Widowed',
            'religion' => 'sometimes|nullable|string|max:100',
            'blood_type' => 'sometimes|nullable|in:A,B,AB,O,Unknown',
            'address' => 'sometimes|nullable|string|max:255',

            // Contact Information
            'phone' => 'sometimes|nullable|string|max:17|unique:employees,phone,' . $employee->id,
            'email' => 'sometimes|nullable|string|max:100|unique:employees,email,' . $employee->id,

            // Employment Overview
            'department_id' => 'sometimes|nullable|exists:departments,id',
            'position_id' => 'sometimes|nullable|exists:positions,id',
            'salary' => 'sometimes|nullable|string', 
            'work_status' => 'sometimes|nullable|in:Permanent,Internship,Part-time,Outsource',
            'join_date' => 'sometimes|nullable|date',
            'resign_date' => 'sometimes|nullable|date',
            'employee_status' => 'sometimes|nullable|string', 
        ]);

        // 3. Penanganan khusus untuk 'phone' (sanitasi +62)
        if (isset($validated['phone']) && !empty($validated['phone'])) {
            $phone = $validated['phone'];
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (!Str::startsWith($phone, '62')) {
                $phone = '62' . $phone;
            }
            $validated['phone'] = '+' . $phone;
        }

        $employee->update($validated); 
        return response()->json($employee, 200);
    }


    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $employee_id)
    {
        // $employee = Employee::find($id);
        $employee = Employee::where('employee_id', $employee_id)->first();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        if ($employee->employee_photo) {
            Storage::delete('public/employee_photos/' . $employee->employee_photo);
        }

        $employee->delete();
        return response()->json(['message' => 'Employee deleted successfully'], 200);
    }

}
