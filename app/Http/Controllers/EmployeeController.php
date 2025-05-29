<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{

    public function index()
    {
        $employees = Employee::all();
        return response()->json($employees);
    }



    public function store(Request $request)
    {
        // 1. Ambil informasi user HR yang sedang login
        $hrUser = Auth::user();

        // Validasi apakah user HR ada dan punya company_id
        if (!$hrUser || !$hrUser->company_id) {
            return response()->json(['message' => 'HR user not authenticated or company_id not found.'], 403);
        }

        // 2. Validasi input dari request
        $validatedData = $request->validate([
            // Validasi untuk data USER (login)
            'email' => 'required|string|email|max:255|unique:users,email', 
            'password' => 'required|string|min:6', 

            // Validasi untuk data EMPLOYEE (sesuai skema tabel employees)
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:17|unique:employees',
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

        DB::beginTransaction();

        try {
            // generate employee_id 
            $currentYearTwoDigits = date('y');
            do {
                $uniqueRandomCode = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $generatedEmployeeId = "{$currentYearTwoDigits}{$uniqueRandomCode}";
            } while (Employee::where('employee_id', $generatedEmployeeId)->exists());

            // password default -> employee_id
            $defaultPassword = $generatedEmployeeId; 

            // 4. Buat User baru dengan role 'employee'
            $user = User::create([
                'full_name' => $validatedData['first_name'] . ' ' . $validatedData['last_name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($defaultPassword), 
                'role' => 'employee',
                'company_id' => $hrUser->company_id,
                'is_profile_complete' => false, // <-- Penting: set ini untuk memaksa ganti password
            ]);

            // 5. Siapkan data untuk employee
            $employeeData = collect($validatedData)->except(['email', 'password'])->all();
            $employeeData['user_id'] = $user->id;
            $employeeData['employee_id'] = $generatedEmployeeId; 

            // 6. Kelola upload foto karyawan
            if ($request->hasFile('employee_photo')) {
                $fileName = Str::random(8) . '.' . $request->file('employee_photo')->getClientOriginalExtension();
                $request->file('employee_photo')->storeAs('public/employee_photos', $fileName);
                $employeeData['employee_photo'] = $fileName;
            } else {
                unset($employeeData['employee_photo']);
            }

            // 7. Format nomor telepon (prefix +62)
            if (isset($employeeData['phone']) && !empty($employeeData['phone'])) {
                $phone = $employeeData['phone'];
                $phone = preg_replace('/[^0-9]/', '', $phone);
                if (!Str::startsWith($phone, '62')) {
                    $phone = '62' . $phone;
                }
                $employeeData['phone'] = '+' . $phone;
            }

            // 8. Buat entri Employee baru
            $employee = Employee::create($employeeData);

            DB::commit();

            return response()->json([
                'employee' => $employee,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create employee and user.', 'error' => $e->getMessage()], 500);
        }
    }




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
        // 1. Temukan data Employee yang akan diupdate
        $employee = Employee::where('employee_id', $employee_id)->first();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        // 2. Temukan data User yang terhubung dengan Employee ini
        $user = User::find($employee->user_id);

        if (!$user) {
            return response()->json(['message' => 'Associated user not found for this employee.'], 500);
        }

        // 3. Definisi aturan validasi untuk update
        $validatedData = $request->validate([
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

            // Contact Information (untuk User dan Employee)
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'sometimes|nullable|string|max:17|unique:employees,phone,' . $employee->id,

            // **Tambahkan validasi untuk password baru di sini**
            'password' => 'sometimes|nullable|string|min:8|confirmed', // 'confirmed' membutuhkan 'password_confirmation, bingung ganti password nanti ada konfirmasinya nggak?
            'password_confirmation' => 'sometimes|nullable|string|min:8', // Harus ada jika 'password' ada

            // Employment Overview
            'department_id' => 'sometimes|nullable|exists:departments,id',
            'position_id' => 'sometimes|nullable|exists:positions,id',
            'salary' => 'sometimes|nullable|string',
            'work_status' => 'sometimes|nullable|in:Permanent,Internship,Part-time,Outsource',
            'join_date' => 'sometimes|nullable|date',
            'resign_date' => 'sometimes|nullable|date',
            'employee_status' => 'sometimes|nullable|string',
            'employee_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        DB::beginTransaction();

        try {
            // 4. Update data User jika ada perubahan pada nama, email, atau password
            $userDataToUpdate = [];
            if (isset($validatedData['first_name']) || isset($validatedData['last_name'])) {
                $userDataToUpdate['full_name'] = ($validatedData['first_name'] ?? $user->first_name) . ' ' . ($validatedData['last_name'] ?? $user->last_name);
            }
            if (isset($validatedData['email'])) {
                $userDataToUpdate['email'] = $validatedData['email'];
            }
            // **Logika Update Password**
            if (isset($validatedData['password']) && !empty($validatedData['password'])) {
                $userDataToUpdate['password'] = Hash::make($validatedData['password']);
                // Jika HR mereset password, mungkin ingin mengembalikan status is_profile_complete ke false
                // agar karyawan dipaksa ganti lagi
                $userDataToUpdate['is_profile_complete'] = false;
            }

            if (!empty($userDataToUpdate)) {
                $user->update($userDataToUpdate);
            }

            // 5. Siapkan data untuk update Employee
            // Kecualikan 'password' dan 'password_confirmation' karena itu hanya untuk tabel users
            $employeeDataToUpdate = collect($validatedData)->except(['email', 'password', 'password_confirmation', 'first_name', 'last_name'])->all();

            // 6. Penanganan update foto karyawan
            if ($request->hasFile('employee_photo')) {
                // Hapus foto lama jika ada
                if ($employee->employee_photo) {
                    Storage::delete('public/employee_photos/' . $employee->employee_photo);
                }
                // Simpan foto baru
                $fileName = Str::random(8) . '.' . $request->file('employee_photo')->getClientOriginalExtension();
                $request->file('employee_photo')->storeAs('public/employee_photos', $fileName);
                $employeeDataToUpdate['employee_photo'] = $fileName;
            // } elseif (array_key_exists('employee_photo', $request->all()) && is_null($request->input('employee_photo'))) {
            //     // Jika input 'employee_photo' dikirim dengan nilai null (berarti ingin menghapus foto)
            //     if ($employee->employee_photo) {
            //         Storage::delete('public/employee_photos/' . $employee->employee_photo);
            //     }
            //     $employeeDataToUpdate['employee_photo'] = null; 
            } else {
                unset($employeeDataToUpdate['employee_photo']);
            }

            // 7. Penanganan khusus untuk 'phone' (sanitasi +62)
            if (isset($employeeDataToUpdate['phone']) && !empty($employeeDataToUpdate['phone'])) {
                $phone = $employeeDataToUpdate['phone'];
                $phone = preg_replace('/[^0-9]/', '', $phone);
                if (!Str::startsWith($phone, '62')) {
                    $phone = '62' . $phone;
                }
                $employeeDataToUpdate['phone'] = '+' . $phone;
            }

            // 8. Update data Employee
            $employee->update($employeeDataToUpdate);

            DB::commit();

            return response()->json($employee, 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update employee and user.', 'error' => $e->getMessage()], 500);
        }
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
