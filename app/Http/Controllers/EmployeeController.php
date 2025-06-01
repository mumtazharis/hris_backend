<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Symfony\Component\Clock\now;

class EmployeeController extends Controller
{

    public function index()
    {
        // $employees = Employee::select('id', 'name', 'gender', 'phone', 'position', '')->get();
        
        // return response()->json($employees);
        $summary = DB::select("
            SELECT
                COUNT(*) AS \"Total Employee\",
                COUNT(*) FILTER (
                    WHERE employee_status = 'Active'
                    AND join_date >= CURRENT_DATE - INTERVAL '30 days'
                ) AS \"Total New Hire\",
                COUNT(*) FILTER (
                    WHERE employee_status = 'Active'
                ) AS \"Active Employee\"
            FROM employees;

        ");

        $data = DB::select("
            select 
            e.employee_id as id, 
            e.first_name || ' ' || e.last_name as \"name\",
            e.gender,
            e.phone,
            p.name as \"position\",
            e.contract_type as \"contract_type\",
            ccs.name as \"workType\",
            e.employee_status as \"status\"
            from employees e 
            left join positions p on e.position_id = p.id
            left join departments d on p.department_id = d.id
            left join check_clock_settings ccs on e.ck_setting_id = ccs.id
            order by e.id
        ");

        return response()->json([
            'periode' => now()->format('F Y'),
            'summary' => $summary[0], // karena COUNT(*) return 1 row
            'employees' => $data
        ]);
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
            'email' => 'required|string|email|max:255|unique:employees,email', 
            'password' => 'nullable|string|min:6', 

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
            // 'department_id' => 'nullable|exists:departments,id',
            'contract_type' => 'nullable|in:Permanent,Internship,Part-time,Outsource',
            'address' => 'nullable|string|max:255',
            'bank_code' => 'nullable|exists:banks,code',
            'account_number' => 'nullable',
            'employee_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:10000',
        ]);

        DB::beginTransaction();

        try {
            // generate employee_id 
            $currentYearTwoDigits = date('y');
            do {
                $uniqueRandomCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $generatedEmployeeId = "{$currentYearTwoDigits}{$uniqueRandomCode}";
            } while (Employee::where('employee_id', $generatedEmployeeId)->exists());

            // password default -> employee_id
            $password = $request['password'] ?? $generatedEmployeeId;
            
            // 4. Buat User baru dengan role 'employee'
            $user = User::create([
                'full_name' => $request['first_name'] . ' ' . $request['last_name'],
                'password' => Hash::make($password), 
                'role' => 'employee',
                'company_id' => $hrUser->company_id,
                'is_profile_complete' => false,
            ]);

            // 5. Siapkan data untuk employee
            $employeeData = collect($validatedData)->except(['password'])->all();
            $employeeData['user_id'] = $user->id;
            $employeeData['employee_id'] = $generatedEmployeeId; 

            // 6. Kelola upload foto karyawan
            if ($request->hasFile('employee_photo')) {
       
                $path = $request->file('employee_photo')->store('public/employee_photos');
                $fileName = basename($path);
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

            // $position = Position::find($employeeData['position_id']);
            // $employeeData['department_id'] = $position?->department_id;
            $employeeData['join_date'] = now();
            $employeeData['employee_status'] = 'Active';
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
        $employee = Employee::with([
            'position.department',
            'bank'
        ])->where('employee_id', $employee_id)->first();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        // Buat array employee tanpa relasi lengkap
        $employeeData = $employee->toArray();

        // Hapus relasi position dan bank yang lengkap supaya tidak ikut dalam response
        unset($employeeData['position']);
        unset($employeeData['bank']);

        return response()->json([
            'employee' => $employeeData,
            'department_id' => $employee->position->department->id ?? null,
            'position_name' => $employee->position->name ?? null,
            'department_name' => $employee->position->department->name ?? null,
            'bank_name' => $employee->bank->name ?? null,
        ]);
    }




    public function update(Request $request, string $employee_id)
    {
        // dd($request->all(), $employee_id);
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
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id|required_with:department_id',

            'salary' => 'sometimes|nullable|string',
            'bank_code' => 'sometimes|nullable|exists:banks,code',
            'contract_type' => 'sometimes|nullable|in:Permanent,Internship,Part-time,Outsource',
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
            $employeeDataToUpdate = collect($validatedData)->except(['password', 'password_confirmation'])->all();

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

            // if (!is_null($employeeDataToUpdate['position_id'])) {
            //     $position = Position::find($employeeDataToUpdate['position_id']);
            //     if ($position) {
            //         $employeeDataToUpdate['department_id'] = $position->department_id;
            //     }
            // }

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

    public function exportCsv(Request $request)
    {
        $fileName = 'employee.csv';
        $query = Employee::query();

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('employee_status')) {
            $query->where('employee_status', $request->employee_status);
        }

        if ($request->filled('contract_type')) {
            $query->where('contract_type', $request->contract_type);
        }

        if ($request->filled('position_id')) {
            $query->where('position_id', $request->position_id);
        }

        if ($request->filled('department_id')) {
            $query->whereHas('position.department', function ($q) use ($request) {
                $q->where('id', $request->department_id);
            });
        }


        $employees = $query->get();
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function() use ($employees) {
            $file = fopen('php://output', 'w');

            // Tulis header kolom
            fputcsv($file, [
                'ID', 
                'employee_id', 
                'nik',
                'first_name',
                'last_name',
                'position_id',
                'address',
                'email',
                'phone',
                'birth_place',
                'birth_date',
                'education',
                'religion',
                'marital_status',
                'citizenship',
                'gender',
                'blood_type',
                'salary',
                'contract_type',
                'bank_code',
                'account_number',
                'join_date',
                'resign_date',
                'employee_photo',
                'employee_status',
                ]
            );

            // Tulis data tiap baris
            foreach ($employees as $employee) {
                fputcsv($file, [
                $employee->id,
                $employee->employee_id,
                $employee->nik,
                $employee->first_name,
                $employee->last_name,
                $employee->position_id,
                $employee->address,
                $employee->email,
                $employee->phone,
                $employee->birth_place,
                $employee->birth_date,
                $employee->education,
                $employee->religion,
                $employee->marital_status,
                $employee->citizenship,
                $employee->gender,
                $employee->blood_type,
                $employee->salary,
                $employee->contract_type,
                $employee->bank_code,
                $employee->account_number,
                $employee->join_date,
                $employee->resign_date,
                $employee->employee_photo,
                $employee->employee_status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function previewCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle);

        $validRows = [];
        $invalidRows = [];
        $rowNumber = 2;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);

            // Normalisasi tanggal kosong
            foreach (['birth_date', 'join_date', 'resign_date'] as $dateField) {
                if (isset($data[$dateField]) && trim($data[$dateField]) === '') {
                    $data[$dateField] = null;
                }
            }

            $validator = Validator::make($data, [
                'email' => 'required|email|unique:employees,email',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:17|unique:employees,phone',
                'nik' => 'nullable|string|max:16|unique:employees,nik',
                'position_id' => 'nullable|exists:positions,id',
                'birth_date' => 'nullable|date',
                'join_date' => 'nullable|date',
                'resign_date' => 'nullable|date',
                'education' => 'nullable|in:SD,SMP,SMA,D3,D4,S1,S2,S3',
                'gender' => 'nullable|in:Male,Female',
                'blood_type' => 'nullable|in:A,B,AB,O,Unknown',
                'marital_status' => 'nullable|in:Single,Married,Divorced,Widowed',
                'contract_type' => 'nullable|in:Permanent,Internship,Part-time,Outsource',
                'bank_code' => 'nullable|exists:banks,code',
            ]);

            if ($validator->fails()) {
                $invalidRows[] = $data;
            } else {
                $validRows[] = $data;
            }

            $rowNumber++;
        }

        fclose($handle);

        return response()->json([
            'total_rows' => $rowNumber - 2,
            'valid_rows_count' => count($validRows),
            'invalid_rows_count' => count($invalidRows),
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
        ]);
    }

    public function confirmImport(Request $request)
    {   
        
        $request->validate([
            'employees' => 'required|array',
            'employees.*.email' => 'required|email|unique:employees,email',
            'employees.*.first_name' => 'required|string|max:255',
            'employees.*.last_name' => 'required|string|max:255',
            'employees.*.phone' => 'nullable|string|max:17|unique:employees,phone',
            'employees.*.nik' => 'nullable|string|max:16|unique:employees,nik',
            'employees.*.position_id' => 'nullable|exists:positions,id',
            'employees.*.birth_date' => 'nullable|date',
            'employees.*.join_date' => 'nullable|date',
            'employees.*.resign_date' => 'nullable|date',
            'employees.*.education' => 'nullable|in:SD,SMP,SMA,D3,D4,S1,S2,S3',
            'employees.*.gender' => 'nullable|in:Male,Female',
            'employees.*.blood_type' => 'nullable|in:A,B,AB,O,Unknown',
            'employees.*.marital_status' => 'nullable|in:Single,Married,Divorced,Widowed',
            'employees.*.contract_type' => 'nullable|in:Permanent,Internship,Part-time,Outsource',
            'employees.*.bank_code' => 'nullable|exists:banks,code',
        ]);


        $hrUser = Auth::user();

        if (!$hrUser || !$hrUser->company_id) {
            return response()->json(['message' => 'HR user not authenticated or company_id not found.'], 403);
        }

        DB::beginTransaction();

        try {
            foreach ($request->employees as $data) {
                $password = $data['employee_id'] ?? str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

                $user = User::create([
                    'full_name' => $data['first_name'] . ' ' . $data['last_name'],
                    'password' => Hash::make($password),
                    'role' => 'employee',
                    'company_id' => $hrUser->company_id,
                    'is_profile_complete' => false,
                ]);

                if (!empty($data['phone'])) {
                    $phone = preg_replace('/[^0-9]/', '', $data['phone']);
                    if (!Str::startsWith($phone, '62')) {
                        $phone = '62' . $phone;
                    }
                    $data['phone'] = '+' . $phone;
                }

                Employee::create([
                    'user_id' => $user->id,
                    'employee_id' => $data['employee_id'],
                    'nik' => $data['nik'] ?? null,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'position_id' => $data['position_id'] ?? null,
                    'address' => $data['address'] ?? null,
                    'birth_place' => $data['birth_place'] ?? null,
                    'birth_date' => $data['birth_date'] ?? null,
                    'education' => $data['education'] ?? null,
                    'religion' => $data['religion'] ?? null,
                    'marital_status' => $data['marital_status'] ?? null,
                    'citizenship' => $data['citizenship'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'blood_type' => $data['blood_type'] ?? null,
                    'salary' => $data['salary'] ?? null,
                    'contract_type' => $data['contract_type'] ?? null,
                    'bank_code' => $data['bank_code'] ?? null,
                    'account_number' => $data['account_number'] ?? null,
                    'join_date' => $data['join_date'] ?? now(),
                    'resign_date' => $data['resign_date'] ?? null,
                    'employee_photo' => $data['employee_photo'] ?? null,
                    'employee_status' => $data['employee_status'] ?? 'Active',
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Import berhasil!'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan data.', 'error' => $e->getMessage()], 500);
        }
    }

    public function resetPassword(string $employee_id)
    {
        // Cari employee berdasarkan employee_id
        $employee = Employee::where('employee_id', $employee_id)->firstOrFail();

        // Cari user yang terkait
        $user = User::findOrFail($employee->user_id);

        // Setel ulang password ke default (sama dengan employee_id)
        $user->password = Hash::make($employee->employee_id);
        $user->save();

        // Opsional: bisa return response atau redirect dengan pesan sukses
        return response()->json([
            'message' => 'Password berhasil direset ke default.',
            'default_password' => $employee->employee_id, // jangan dikirim di production
        ]);
    }


    // public function importCsv(Request $request)
    // {
    //     $request->validate([
    //         'csv_file' => 'required|file|mimes:csv,txt',
    //     ]);

    //     $hrUser = Auth::user();

    //     if (!$hrUser || !$hrUser->company_id) {
    //         return response()->json(['message' => 'HR user not authenticated or company_id not found.'], 403);
    //     }

    //     $file = $request->file('csv_file');
    //     $handle = fopen($file, 'r');
    //     $header = fgetcsv($handle); // Ambil header

    //     $rows = [];
    //     $invalidRows = [];

    //     $rowNumber = 2; // Karena baris 1 adalah header

    //     while (($row = fgetcsv($handle)) !== false) {
    //         $data = array_combine($header, $row);

    //         // Validasi sederhana per baris
    //         $validator = Validator::make($data, [
    //             'email' => 'required|email|unique:employees,email',
    //             'first_name' => 'required|string|max:255',
    //             'last_name' => 'required|string|max:255',
    //             'phone' => 'nullable|string|max:17|unique:employees,phone',
    //             'nik' => 'nullable|string|max:16|unique:employees,nik',
    //             'position_id' => 'nullable|exists:positions,id',
    //             'birth_date' => 'nullable|date',
    //             'join_date' => 'nullable|date',
    //             'resign_date' => 'nullable|date|nullable',
    //             'education' => 'nullable|in:SD,SMP,SMA,D3,D4,S1,S2,S3',
    //             'gender' => 'nullable|in:Male,Female',
    //             'blood_type' => 'nullable|in:A,B,AB,O,Unknown',
    //             'marital_status' => 'nullable|in:Single,Married,Divorced,Widowed',
    //             'contract_type' => 'nullable|in:Permanent,Internship,Part-time,Outsource',
    //             'bank_code' => 'nullable|exists:banks,code',
    //         ]);

    //         if ($validator->fails()) {
    //             $invalidRows[] = [
    //                 'row' => $rowNumber,
    //                 'errors' => $validator->errors()->all(),
    //                 'data' => $data,
    //             ];
    //         } else {
    //             $rows[] = $data;
    //         }

    //         $rowNumber++;
    //     }

    //     fclose($handle);

    //     // Jika ada baris tidak valid, tampilkan pesan error
    //     if (count($invalidRows) > 0) {
    //         return response()->json([
    //             'message' => 'Import gagal. Terdapat baris tidak valid.',
    //             'invalid_rows' => $invalidRows
    //         ], 422);
    //     }

    //     // Semua baris valid, lanjut simpan ke DB
    //     DB::beginTransaction();

    //     try {
    //         foreach ($rows as $data) {
    //             // Generate default password dari employee_id
    //             $password = $data['employee_id'] ?? str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

    //             // Buat User login
    //             $user = User::create([
    //                 'full_name' => $data['first_name'] . ' ' . $data['last_name'],
    //                 'password' => Hash::make($password),
    //                 'role' => 'employee',
    //                 'company_id' => $hrUser->company_id,
    //                 'is_profile_complete' => false,
    //             ]);

    //             // Format nomor telepon
    //             if (!empty($data['phone'])) {
    //                 $phone = preg_replace('/[^0-9]/', '', $data['phone']);
    //                 if (!Str::startsWith($phone, '62')) {
    //                     $phone = '62' . $phone;
    //                 }
    //                 $data['phone'] = '+' . $phone;
    //             }

    //             $employeeData = [
    //                 'user_id' => $user->id,
    //                 'employee_id' => $data['employee_id'],
    //                 'nik' => $data['nik'] ?? null,
    //                 'first_name' => $data['first_name'],
    //                 'last_name' => $data['last_name'],
    //                 'email' => $data['email'],
    //                 'phone' => $data['phone'] ?? null,
    //                 'position_id' => $data['position_id'] ?? null,
    //                 'address' => $data['address'] ?? null,
    //                 'birth_place' => $data['birth_place'] ?? null,
    //                 'birth_date' => $data['birth_date'] ?? null,
    //                 'education' => $data['education'] ?? null,
    //                 'religion' => $data['religion'] ?? null,
    //                 'marital_status' => $data['marital_status'] ?? null,
    //                 'citizenship' => $data['citizenship'] ?? null,
    //                 'gender' => $data['gender'] ?? null,
    //                 'blood_type' => $data['blood_type'] ?? null,
    //                 'salary' => $data['salary'] ?? null,
    //                 'contract_type' => $data['contract_type'] ?? null,
    //                 'bank_code' => $data['bank_code'] ?? null,
    //                 'account_number' => $data['account_number'] ?? null,
    //                 'join_date' => $data['join_date'] ?? now(),
    //                 'resign_date' => $data['resign_date'] ?? null,
    //                 'employee_photo' => $data['employee_photo'] ?? null,
    //                 'employee_status' => $data['employee_status'] ?? 'Active',
    //             ];
    //             // Format tanggal kosong menjadi null
    //             $employeeData['resign_date'] = !empty($employeeData['resign_date']) ? $employeeData['resign_date'] : null;
    //             $employeeData['birth_date'] = !empty($employeeData['birth_date']) ? $employeeData['birth_date'] : null;
    //             $employeeData['join_date'] = !empty($employeeData['join_date']) ? $employeeData['join_date'] : now();

    //             Employee::create($employeeData);
    //         }

    //         DB::commit();

    //         return response()->json(['message' => 'Import berhasil!'], 201);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['message' => 'Gagal menyimpan data.', 'error' => $e->getMessage()], 500);
    //     }
    // }

}
