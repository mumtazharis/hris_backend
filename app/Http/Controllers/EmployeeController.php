<?php

namespace App\Http\Controllers;

use App\Models\DeletedEmployeeLog;
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
use Illuminate\Validation\Rule;
use function Laravel\Prompts\password;
use function Symfony\Component\Clock\now;
use Illuminate\Support\Carbon;

class EmployeeController extends Controller
{

    public function index()
    {
        // $employees = Employee::select('id', 'name', 'gender', 'phone', 'position', '')->get();
        
        // return response()->json($employees);
    $hrUser = Auth::user();
    $companyId = $hrUser->company_id;

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
        FROM employees e
        JOIN users u ON e.user_id = u.id
        WHERE e.company_id = ?
        ", [$companyId]);


        $data = DB::select("
            select 
            e.employee_id as id, 
            e.first_name || ' ' || e.last_name as \"name\",
            e.gender,
            e.phone,
            d.name as \"department\",
            p.name as \"position\",
            e.contract_type as \"contract_type\",
            e.employee_status as \"status\"
            from employees e 
            left join positions p on e.position_id = p.id
            left join departments d on p.department_id = d.id
            left join check_clock_settings ccs on e.ck_setting_id = ccs.id
            join users u on e.user_id = u.id
            where e.company_id = ?
            order by e.id
        ", [$companyId]);

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
            // 'email' => 'required|string|email|max:255|unique:employees,email',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('employees')->where(function ($query) use ($hrUser) {
                    return $query->where('employee_status', 'Active')
                                ->where('company_id', $hrUser->company_id);
                }),
            ],

            // Validasi untuk data EMPLOYEE (sesuai skema tabel employees)
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            // 'phone' => 'nullable|string|max:17|unique:employees',
            'phone' => [
                'required',
                'string',
                'max:17',
                Rule::unique('employees')->where(function ($query) use ($hrUser) {
                    return $query->where('employee_status', 'Active')
                                ->where('company_id', $hrUser->company_id);
                }),
            ],
            // 'nik' => 'nullable|string|size:16|unique:employees',
            'nik' => [
                'required',
                'string',
                'size:16',
                Rule::unique('employees')->where(function ($query) use ($hrUser) {
                    return $query->where('employee_status', 'Active')
                                ->where('company_id', $hrUser->company_id);
                }),
            ],

            'gender' => 'required|in:Male,Female',
            'education' => 'required|in:SD,SMP,SMA,D3,D4,S1,S2,S3',
            'birth_place' => 'required|string|max:100',
            'birth_date' => 'required|date',
            'blood_type' => 'required|in:A,B,AB,O,Unknown',
            'citizenship' => 'required|string|max:100',
            'marital_status' => 'required|in:Single,Married,Divorced,Widowed',
            'religion' => 'required|string|max:100',
            'position_id' => 'required|exists:positions,id',
            'address' => 'required|string|max:255',
            'bank_code' => 'nullable|exists:banks,code',
            'account_number' => 'nullable',
            'join_date' => 'required|date',
            'contract_type' => 'required|in:Permanent,Internship,Contract',
            'contract_end' => [
                'required_if:contract_type,Internship,Contract',
                'nullable',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail) use ($request) {
                    $type = $request->input('contract_type');
                    $value = $value === '' ? null : $value;
                    if (in_array($type, ['Internship', 'Contract']) && is_null($value)) {
                        $fail('Tanggal akhir kontrak wajib diisi jika tipe kontrak Internship atau Contract.');
                    }

                    if ($type === 'Permanent' && !is_null($value)) {
                        $fail('Tanggal akhir kontrak harus dikosongkan jika tipe kontrak adalah Permanent.');
                    }
                },
            ],

            'salary' => 'nullable|numeric|min:0',
            'employee_photo' => 'nullable|image|max:5120',
        ]);

        DB::beginTransaction();

        try {
            // generate employee_id 
            $currentYearTwoDigits = date('y');
            do {
                $uniqueRandomCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $generatedEmployeeId = "{$currentYearTwoDigits}{$uniqueRandomCode}";
            } while (Employee::where('employee_id', $generatedEmployeeId)->exists());
            
            // 4. Buat User baru dengan role 'employee'
            $user = User::create([
                'full_name' => $request['first_name'] . ' ' . $request['last_name'],
                'password' => Hash::make($generatedEmployeeId), 
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
       
                $path = $request->file('employee_photo')->store('employee_photo', 's3');
                // $fileName = basename($path);
                $employeeData['employee_photo'] = $path;

            } else {
                unset($employeeData['employee_photo']);
            }

            $employeeData['employee_status'] = 'Active';
            $employeeData['company_id'] = $hrUser->company_id;
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
        $hrUser = Auth::user();

        $employee = Employee::with([
            'position.department',
            'bank'
        ])->where('employee_id', $employee_id)->where('company_id', $hrUser->company_id)->first();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        // Buat array employee tanpa relasi lengkap
        $employeeData = $employee->toArray();

        // Hapus relasi position dan bank yang lengkap supaya tidak ikut dalam response
        unset($employeeData['position']);
        unset($employeeData['bank']);

        
        $employeePhotoUrl = null;
        if (!empty($employee->employee_photo)) {
            $employeePhotoUrl = Storage::disk('s3')->temporaryUrl(
                $employee->employee_photo,
                Carbon::now()->addMinutes(10) // berlaku 10 menit
            );
        }
        return response()->json([
            'employee' => $employeeData,
            'department_id' => $employee->position->department->id ?? null,
            'position_name' => $employee->position->name ?? null,
            'department_name' => $employee->position->department->name ?? null,
            'bank_name' => $employee->bank->name ?? null,
            'employee_photo_url' => $employeePhotoUrl,
        ]);
    }




    public function update(Request $request, string $employee_id)
    {
        // dd($request->all(), $employee_id);
        // 1. Temukan data Employee yang akan diupdate
        $hrUser = Auth::user();
        $employee = Employee::where('employee_id', $employee_id)->where('company_id', $hrUser->company_id)->first();

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
            'nik' => [
                'sometimes',
                'required',
                'string',
                'size:16',
                Rule::unique('employees')->ignore($employee_id, 'employee_id')->where(function ($query) use ($hrUser) {
                    return $query->where('employee_status', 'Active')
                                ->where('company_id', $hrUser->company_id);
                }),
            ],
            'gender' => 'sometimes|required|in:Male,Female',
            'education' => 'sometimes|required|in:SD,SMP,SMA,D3,D4,S1,S2,S3',
            'birth_place' => 'sometimes|required|string|max:100',
            'birth_date' => 'sometimes|required|date',
            'citizenship' => 'sometimes|required|string|max:100',
            'marital_status' => 'sometimes|required|in:Single,Married,Divorced,Widowed',
            'religion' => 'sometimes|required|string|max:100',
            'blood_type' => 'sometimes|required|in:A,B,AB,O,Unknown',
            'address' => 'sometimes|required|string|max:255',

            // Contact Information (untuk User dan Employee)
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('employees')->ignore($employee_id, 'employee_id')->where(function ($query) use ($hrUser) {
                    return $query->where('employee_status', 'Active')
                                ->where('company_id', $hrUser->company_id);
                }),
            ],
            // 'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => [
                'sometimes',
                'required',
                'string',
                'max:17',
                Rule::unique('employees')->ignore($employee_id, 'employee_id')->where(function ($query) use ($hrUser) {
                    return $query->where('employee_status', 'Active')
                                ->where('company_id', $hrUser->company_id);
                }),
            ],

            'position_id' => 'sometimes|required|exists:positions,id|required_with:department_id',

            'salary' => 'sometimes|nullable|string',
            'bank_code' => 'sometimes|nullable|exists:banks,code',
            'account_number' => 'sometimes|nullable|numeric',
            'contract_type' => 'sometimes|required|in:Permanent,Internship,Contract',
            'contract_end' => [
                'required_if:contract_type,Internship,Contract',
                'nullable',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail) use ($request) {
                    $type = $request->input('contract_type');
                    $value = $value === '' ? null : $value;
                    if (in_array($type, ['Internship', 'Contract']) && is_null($value)) {
                        $fail('Tanggal akhir kontrak wajib diisi jika tipe kontrak Internship atau Contract.');
                    }

                    if ($type === 'Permanent' && !is_null($value)) {
                        $fail('Tanggal akhir kontrak harus dikosongkan jika tipe kontrak adalah Permanent.');
                    }
                },
            ],

            'join_date' => 'sometimes|required|date',
            'exit_date' => 'sometimes|required_if:contract_type,Internship,Contract|date',
            'employee_status' => [
                'sometimes',
                'required',
                'string',
                function ($attribute, $value, $fail) use ($employee) {
                    if ($value === 'Active' && $employee->employee_status !== 'Active') {
                        $fail('Status tidak bisa diubah menjadi Active karena sebelumnya sudah bukan Active. Silakan lakukan rejoin.');
                    }
                },
            ],
            'employee_photo' => 'sometimes|nullable|image|max:5120',
        ]);

        DB::beginTransaction();

        try {
            // 4. Update data User jika ada perubahan pada nama, email, atau password
            $userDataToUpdate = [];
            if (isset($validatedData['first_name']) || isset($validatedData['last_name'])) {
                $userDataToUpdate['full_name'] = ($validatedData['first_name'] ?? $user->first_name) . ' ' . ($validatedData['last_name'] ?? $user->last_name);
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

        
            $employeeDataToUpdate = collect($validatedData)->except(['password'])->all();

            if ($request->hasFile('employee_photo')) {
                // Hapus foto lama jika ada
                if ($employee->employee_photo && Storage::disk('s3')->exists($employee->employee_photo)) {
                    Storage::disk('s3')->delete($employee->employee_photo);
                }

                // Upload foto baru
                $path = $request->file('employee_photo')->store('employee_photo', 's3');
                $employeeDataToUpdate['employee_photo'] = $path;
            } else {
                unset($employeeDataToUpdate['employee_photo']);
            }

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
    public function permanentDelete(string $employee_id)
    {
        $hrUser = Auth::user();
  
        $employee = Employee::where('employee_id', $employee_id)->where('company_id', $hrUser->company_id)->first();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $employee_user = User::find($employee->user_id);
        if ($employee_user){
            $admin_user = Auth::user();
            if ($admin_user){
                $deleted_user_data = [
                    'admin_id' => $admin_user->id,
                    'deleted_employee_name' => $employee_user->full_name,

                ];
                DeletedEmployeeLog::create($deleted_user_data);
                $employee_user->delete();
            }
        }
        
        
        if ($employee->employee_photo) {
            Storage::delete('public/employee_photos/' . $employee->employee_photo);
        }
        return response()->json(['message' => 'Employee deleted successfully'], 200);
    }

    public function exportCsv(Request $request)
    {
        $hrUser = Auth::user();
  
        $fileName = 'employee.csv';
        $query = Employee::query()->where('company_id', $hrUser->company_id);

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
                'company_id',
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
                'contract_end',
                'join_date',
                'exit_date',
                'employee_photo',
                'employee_status',
                ]
            );

            // Tulis data tiap baris
            foreach ($employees as $employee) {
                fputcsv($file, [
                $employee->id,
                $employee->company_id,
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
                $employee->contract_end,
                $employee->join_date,
                $employee->exit_date,
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
            foreach (['birth_date','contract_end', 'join_date', 'exit_date'] as $dateField) {
                if (isset($data[$dateField]) && trim($data[$dateField]) === '') {
                    $data[$dateField] = null;
                }
            }

            $hrUser = Auth::user();
            $validator = Validator::make($data, [
                'company_id' => [
                    'required',
                    Rule::in([$hrUser->company_id]) // hanya boleh company_id milik user login
                ],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('employees')->where(function ($query) use ($hrUser) {
                    return $query->where('employee_status', 'Active')
                                ->where('company_id', $hrUser->company_id);
                    }),
                ],
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone' => [
                    'required',
                    'string',
                    'max:17',
                    Rule::unique('employees')->where(function ($query) use ($hrUser) {
                    return $query->where('employee_status', 'Active')
                                ->where('company_id', $hrUser->company_id);
                    }),
                ],
                'nik' => [
                    'required',
                    'string',
                    'size:16',
                    Rule::unique('employees')->where(function ($query) use ($hrUser) {
                    return $query->where('employee_status', 'Active')
                                ->where('company_id', $hrUser->company_id);
                    }),
                ],

                'position_id' => 'required|exists:positions,id',
                'birth_date' => 'required|date',
                'contract_end' => [
                    'required_if:contract_type,Internship,Contract',
                    'date',
                    'after_or_equal:today',
                    function ($attribute, $value, $fail) use ($request) {
                        $type = $request->input('contract_type');

                        if (in_array($type, ['Internship', 'Contract']) && is_null($value)) {
                            $fail('Tanggal akhir kontrak wajib diisi jika tipe kontrak Internship atau Contract.');
                        }

                        if ($type === 'Permanent' && !is_null($value)) {
                            $fail('Tanggal akhir kontrak harus dikosongkan jika tipe kontrak adalah Permanent.');
                        }
                    },
                ],

                'join_date' => 'required|date',
                'exit_date' => 'nullable|date',
                'education' => 'required|in:SD,SMP,SMA,D3,D4,S1,S2,S3',
                'gender' => 'required|in:Male,Female',
                'blood_type' => 'required|in:A,B,AB,O,Unknown',
                'marital_status' => 'required|in:Single,Married,Divorced,Widowed',
                'contract_type' => 'required|in:Permanent,Internship,Contract',
                'bank_code' => 'nullable|exists:banks,code',
                'employee_status' => 'required|in:Active,Retire,Resign,Fired'
            ]);

            $position = Position::with('department')->find($data['position_id']);
            if ($position) {
                $data['position_name'] = $position->name;
                $data['department_name'] = $position->department->name ?? null;
            }
            if ($validator->fails()) {
                // $data['errors'] = $validator->errors()->toArray();
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
        $hrUser = Auth::user();
        $request->validate([
            'employees' => 'required|array',

            'employees.*.company_id' => [
                    'required',
                    Rule::in([$hrUser->company_id]) // hanya boleh company_id milik user login
            ],
            
            'employees.*.email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('employees')->where(function ($query) use ($hrUser) {
                    return $query->where('employee_status', 'Active')
                                ->where('company_id', $hrUser->company_id);
                }),
            ],
            'employees.*.first_name' => 'required|string|max:255',
            'employees.*.last_name' => 'required|string|max:255',
            'employees.*.phone' => [
                'required',
                'string',
                'max:17',
                Rule::unique('employees')->where(function ($query) use ($hrUser) {
                    return $query->where('employee_status', 'Active')
                                ->where('company_id', $hrUser->company_id);
                }),
            ],
            'employees.*.nik' => [
                'required',
                'string',
                'size:16',
                Rule::unique('employees')->where(function ($query) use ($hrUser) {
                    return $query->where('employee_status', 'Active')
                                ->where('company_id', $hrUser->company_id);
                }),
            ],

            'employees.*.position_id' => 'required|exists:positions,id',
            'employees.*.birth_date' => 'required|date',
            'employees.*.contract_end' => [
                'required_if:contract_type,Internship,Contract',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail) use ($request) {
                    $type = $request->input('contract_type');

                    if (in_array($type, ['Internship', 'Contract']) && is_null($value)) {
                        $fail('Tanggal akhir kontrak wajib diisi jika tipe kontrak Internship atau Contract.');
                    }

                    if ($type === 'Permanent' && !is_null($value)) {
                        $fail('Tanggal akhir kontrak harus dikosongkan jika tipe kontrak adalah Permanent.');
                    }
                },
            ],
            'employees.*.join_date' => 'required|date',
            'employees.*.exit_date' => 'nullable|date',
            'employees.*.education' => 'required|in:SD,SMP,SMA,D3,D4,S1,S2,S3',
            'employees.*.gender' => 'required|in:Male,Female',
            'employees.*.blood_type' => 'required|in:A,B,AB,O,Unknown',
            'employees.*.marital_status' => 'required|in:Single,Married,Divorced,Widowed',
            'employees.*.contract_type' => 'required|in:Permanent,Internship,Contract',
            'employees.*.bank_code' => 'nullable|exists:banks,code',
        ]);


        $hrUser = Auth::user();

        if (!$hrUser || !$hrUser->company_id) {
            return response()->json(['message' => 'HR user not authenticated or company_id not found.'], 403);
        }

        DB::beginTransaction();

        try {
            foreach ($request->employees as $data) {
                 $currentYearTwoDigits = date('y');

                do {
                    $uniqueRandomCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                    $generatedEmployeeId = "{$currentYearTwoDigits}{$uniqueRandomCode}";
                } while (Employee::where('employee_id', $generatedEmployeeId)->exists());

                $user = User::create([
                    'full_name' => $data['first_name'] . ' ' . $data['last_name'],
                    'password' => Hash::make($generatedEmployeeId),
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
                    'company_id' => $data['company_id'],
                    'employee_id' => $generatedEmployeeId,
                    'nik' => $data['nik'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'position_id' => $data['position_id'],
                    'address' => $data['address'],
                    'birth_place' => $data['birth_place'],
                    'birth_date' => $data['birth_date'],
                    'education' => $data['education'],
                    'religion' => $data['religion'],
                    'marital_status' => $data['marital_status'],
                    'citizenship' => $data['citizenship'],
                    'gender' => $data['gender'],
                    'blood_type' => $data['blood_type'],
                    'salary' => $data['salary'] ?? null,
                    'contract_type' => $data['contract_type'],
                    'bank_code' => $data['bank_code'] ?? null,
                    'account_number' => $data['account_number'] ?? null,
                    'contract_end' => $data['contract_end'] ?? null,
                    'join_date' => $data['join_date'],
                    'exit_date' => $data['exit_date'] ?? null,
                    'employee_photo' => $data['employee_photo'] ?? null,
                    'employee_status' => $data['employee_status'],
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
        $hrUser = Auth::user();
        $employee = Employee::where('employee_id', $employee_id)->where('company_id', $hrUser->company_id)->firstOrFail();

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
    //             'exit_date' => 'nullable|date|nullable',
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
    //                 'exit_date' => $data['exit_date'] ?? null,
    //                 'employee_photo' => $data['employee_photo'] ?? null,
    //                 'employee_status' => $data['employee_status'] ?? 'Active',
    //             ];
    //             // Format tanggal kosong menjadi null
    //             $employeeData['exit_date'] = !empty($employeeData['exit_date']) ? $employeeData['exit_date'] : null;
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
