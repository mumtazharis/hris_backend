<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; 

use App\Models\User;      
use App\Models\Department; 
use App\Models\Position;   
use App\Models\Company;    

class CompanyController extends Controller
{
    /**
     * Mengambil data profil HR/Admin yang sedang login.
     * 
     */
    public function showProfileData()
    {
        // Mendapatkan user yang sedang login
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $profileData = [
            'id' => $user->id,
            'firstName' => $user->first_name, // Menggunakan accessor dari model User
            'lastName' => $user->last_name,   // Menggunakan accessor dari model User
            'phoneNumber' => $user->phone,     
            'email' => $user->email,
            'companyName' => $user->company->name ?? 'N/A', 
            'avatarUrl' => $user->avatar_url, 
        ];

        return response()->json($profileData);
    }

    /**
     * Memperbarui data profil HR/Admin yang sedang login.
     */
    public function updateProfileData(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'phoneNumber' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)], // Pastikan unik selain diri sendiri
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)], // Pastikan unik selain diri sendiri
            'avatarUrl' => 'nullable|url', 
        ]);

        // Gabungkan first_name dan last_name menjadi full_name
        $full_name = trim($request->input('firstName') . ' ' . $request->input('lastName'));

        // Update data user
        $user->full_name = $full_name;
        $user->email = $request->input('email');
        $user->phone = $request->input('phoneNumber'); 
        $user->avatar_url = $request->input('avatarUrl'); 
        $user->save();

        // Mengembalikan data user yang sudah diupdate, dengan relasi company
        return response()->json(['message' => 'Profile updated successfully!', 'user' => $user->fresh()->load('company')], 200);
    }

    /**
     * Mengambil semua Departemen dan Posisi untuk perusahaan user yang login.
     */
    public function getCompanyStructure()
    {
        $user = Auth::user();
        if (!$user || !$user->company_id) {
            return response()->json(['message' => 'Unauthorized or company not assigned'], 403);
        }

        $companyId = $user->company_id;

        // Ambil semua departemen untuk company_id user yang login
        // Eager load posisi-posisi di setiap departemen
        $departments = Department::where('company_id', $companyId)
                                 ->with('positions') 
                                 ->get();

        // Format data agar sesuai dengan interface Department di page.tsx (bisa juga dikirim langsung)
        $formattedDepartments = $departments->map(function ($dept) {
            return [
                'id' => $dept->id,
                'name' => $dept->name,
                'positions' => $dept->positions->map(function ($pos) {
                    return [
                        'id' => $pos->id,
                        'name' => $pos->name,
                        // detail posisi lain 
                    ];
                })->toArray(),
            ];
        });

        return response()->json($formattedDepartments);
    }

    public function updateCompanyStructure(Request $request){
        $user = Auth::user();

        if (!$user || !$user->company_id) {
            return response()->json(['message' => 'Unauthorized or company not assigned'], 403);
        }

        $request->validate([
            'company_name' => 'required|string|max:50',
        ]);
        // Dapatkan instance Company berdasarkan company_id dari user yang login
        // Asumsi primary key di tabel 'companies' adalah 'company_id'
        $company = Company::where('company_id', $user->company_id)->first();

        // Pastikan perusahaan ditemukan (seharusnya selalu ada jika user memiliki company_id)
        if (!$company) {
            return response()->json(['message' => 'Company not found.'], 404);
        }

        $company->name = $request->input('company_name');
        $company->save();

        return response()->json(['message' => 'Company name updated successfully!', 'company' => $company], 200);
    }

    /**
     * Membuat Departemen baru untuk perusahaan user yang login.
     */
    public function createDepartment(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->company_id) {
            return response()->json(['message' => 'Unauthorized or company not assigned'], 403);
        }
        $companyId = $user->company_id;

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                
                Rule::unique('departments')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                }),
            ],
        ]);

        $department = Department::create([
            'company_id' => $companyId,
            'name' => $request->name,
        ]);

        return response()->json(['message' => 'Department created successfully!', 'department' => $department], 201);
    }

    /**
     * Memperbarui nama Departemen.
     */
    public function updateDepartment(Request $request, Department $department) 
    {
        $user = Auth::user();
        if (!$user || !$user->company_id) {
            return response()->json(['message' => 'Unauthorized or company not assigned'], 403);
        }
        $companyId = $user->company_id;

        // Pastikan departemen yang diupdate milik perusahaan user yang login
        if ($department->company_id !== $companyId) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // Pastikan nama departemen unik di dalam company_id yang sama, kecuali untuk departemen ini sendiri
                Rule::unique('departments')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                })->ignore($department->id),
            ],
        ]);

        $department->update(['name' => $request->name]);

        return response()->json(['message' => 'Department updated successfully!', 'department' => $department], 200);
    }

    /**
     * Menghapus Departemen.
     */
    public function deleteDepartment(Department $department) /
    {
        $user = Auth::user();
        if (!$user || !$user->company_id) {
            return response()->json(['message' => 'Unauthorized or company not assigned'], 403);
        }
        $companyId = $user->company_id;

        // Pastikan departemen yang dihapus milik perusahaan user yang login
        if ($department->company_id !== $companyId) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        } 
    
        // Cek apakah ada karyawan yang terkait dengan departemen ini MELALUI POSISI-POSISI di departemen tersebut
        $hasEmployeesInDepartmentPositions = $department->positions() // Ambil semua posisi di departemen ini
                                                        ->whereHas('employees') // Cek apakah ada posisi yang punya karyawan
                                                        ->exists(); // Jika ada satu saja, maka true
    
        if ($hasEmployeesInDepartmentPositions) {
            return response()->json([
                'message' => 'Cannot delete department: Employees are still assigned to positions within this department.'
            ], 400);
        }
    
        $department->delete();
    
        return response()->json(['message' => 'Department deleted successfully!'], 200);
    }

    /**
     * Membuat Posisi baru untuk Departemen tertentu dalam perusahaan user yang login.
     */
    public function createPosition(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->company_id) {
            return response()->json(['message' => 'Unauthorized or company not assigned'], 403);
        }
        $companyId = $user->company_id;

        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
        ]);

        // Pastikan department_id yang dikirim benar-benar milik perusahaan user yang login
        $department = Department::where('id', $request->department_id)
                                 ->where('company_id', $companyId)
                                 ->first();

        if (!$department) {
            return response()->json(['message' => 'Department not found or does not belong to your company.'], 404);
        }

        $request->validate([
            'name' => Rule::unique('positions')->where(function ($query) use ($department) {
                return $query->where('department_id', $department->id);
            })
        ]);

        $position = Position::create([
            'company_id' => $companyId, 
            'department_id' => $department->id,
            'name' => $request->name,
        ]);

        return response()->json(['message' => 'Position created successfully!', 'position' => $position], 201);
    }

    /**
     * Memperbarui nama Posisi.
     */
    public function updatePosition(Request $request, Position $position) 
    {
        $user = Auth::user();
        if (!$user || !$user->company_id) {
            return response()->json(['message' => 'Unauthorized or company not assigned'], 403);
        }
        $companyId = $user->company_id;

        // Pastikan posisi yang diupdate milik perusahaan user yang login
        // dan departemennya juga milik perusahaan yang sama
        if ($position->company_id !== $companyId || $position->department->company_id !== $companyId) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // Pastikan nama posisi unik dalam departemen yang sama, kecuali untuk posisi ini sendiri
                Rule::unique('positions')->where(function ($query) use ($position) {
                    return $query->where('department_id', $position->department_id);
                })->ignore($position->id),
            ],
            // 'department_id' => [ // Opsional: jika ingin memindahkan posisi ke departemen lain
            //     'required', 
            //     'exists:departments,id',
            // ],
        ]);

        // Jika department_id diubah, pastikan department baru juga milik perusahaan yang sama
        if ($request->has('department_id') && $request->department_id !== $position->department_id) {
            $newDepartment = Department::where('id', $request->department_id)
                                        ->where('company_id', $companyId)
                                        ->first();
            if (!$newDepartment) {
                return response()->json(['message' => 'New department not found or does not belong to your company.'], 404);
            }
        }

        $position->update([
            'name' => $request->name,
            // 'department_id' => $request->department_id, // Update juga jika diubah
        ]);

        return response()->json(['message' => 'Position updated successfully!', 'position' => $position], 200);
    }

    /**
     * Menghapus Posisi.
     */
    public function deletePosition(Position $position) 
    {
        $user = Auth::user();
        if (!$user || !$user->company_id) {
            return response()->json(['message' => 'Unauthorized or company not assigned'], 403);
        }
        $companyId = $user->company_id;

        // Pastikan posisi yang dihapus milik perusahaan user yang login
        if ($position->company_id !== $companyId) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        // Cek apakah ada karyawan yang masih terkait dengan posisi ini (di tabel employees)
        // Asumsi ada relasi 'employees' di Model Position
        if ($position->employees()->exists()) {
             return response()->json(['message' => 'Cannot delete position: Employees are still assigned to it.'], 400);
        }

        $position->delete();

        return response()->json(['message' => 'Position deleted successfully!'], 200);
    }
}