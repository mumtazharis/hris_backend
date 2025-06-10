<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Google_Client;
use Exception;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[\W_]/|confirmed',
        ]);
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
            'is_profile_complete' => false,
            'auth_provider' => 'local',
        ]);

        return response()->json(['token' => $user->createToken('API Token')->plainTextToken, 'is_profile_complete' => $user->is_profile_complete]);
    }

    public function completeRegister(Request $request)
    {
        
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'phone' => 'required|numeric|digits_between:10,15|unique:users,phone',
            'company_name' => 'required|string|max:50',
            Rule::unique('users', 'phone')->ignore($user->id),
        ]);

        
 
        DB::beginTransaction();

        try {
            do {
                $randomCompanyId = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            } while (Company::where('company_id', $randomCompanyId)->exists());    

            $company = Company::create([
                'name' => $request->company_name,
                'company_id' => $randomCompanyId,
            ]);

            $user->update([
                'company_id' => $company->id,
                'full_name' => $request->first_name . ' ' . $request->last_name,
                'phone' => $request->phone,
                'is_profile_complete' => true,
            ]);
    
            // Commit jika semua berhasil
            DB::commit();
    
            return response()->json(['message' => 'Data diri berhasil dilengkapi.']);
    
        } catch (\Exception $e) {
            // Rollback jika ada error
            DB::rollBack();
            return response()->json(['error' => 'Terjadi kesalahan saat melengkapi data.',    'message' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request){
        // Ambil username dan password dari request
        $username = $request->input('username'); 
        $password = $request->input('password');
    
        // Periksa apakah username mengandung "@" (untuk email)
        if (strpos($username, '@') !== false) {
            // Jika username mengandung "@" berarti itu email
            $user = User::where('email', $username)->first();
        } else {
            // Jika username tidak mengandung "@" berarti itu nomor telepon
            $user = User::where('phone', $username)->first();
        }
    
        // Jika user tidak ditemukan atau password salah
        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json(['errors' => ['message' => 'Invalid E-mail or Password']], 401);
        }

        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $currentMonthBill = $user->bills()
            ->whereMonth('deadline', $currentMonth)
            ->whereYear('deadline', $currentYear)
            ->first();

        $userPhotoUrl = null;
        if (!empty($user->user_photo) && $user->user_photo !== '') {
            $userPhotoUrl = Storage::disk('s3')->temporaryUrl(
                $user->user_photo,
                Carbon::now()->addMinutes(10) // berlaku 10 menit
            );
        }
        // Jika login berhasil, buat token dan kirimkan sebagai response
        return response()->json([
            'token' => $user->createToken('API Token')->plainTextToken,
            'full_name' => $user->full_name,
            'user_photo' => $userPhotoUrl,
            'role' => $user->role,
            'is_profile_complete' => $user->is_profile_complete,
            'plan_name' => optional(optional($user->company)->billingPlan)->plan_name,
            'bill_period' => $currentMonthBill?->period,
            'bill_status' => $currentMonthBill?->status,
            'bill_deadline' => ($currentMonthBill && $currentMonthBill->status !== 'paid') ? $currentMonthBill->deadline : null,
        ]);
    }

    public function loginEmployee(Request $request){
        // Cari employee berdasarkan employee_id
        $employee = Employee::where('employee_id', $request->employee_id)->first();

        if (!$employee || !$employee->user) {
            return response()->json([
                'errors' => ['message' => 'Invalid Employee ID, Company, or Password']
            ], 401);
        }

        $user = $employee->user;

        // Cek apakah company_id cocok
        if ($user->company_id != $request->company_id) {
            return response()->json([
                'errors' => ['message' => 'Invalid Employee ID, Company, or Password']
            ], 401);
        }

        // Cek password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'errors' => ['message' => 'Invalid Employee ID, Company, or Password']
            ], 401);
        }

        // Jika berhasil login
        return response()->json([
            'token' => $user->createToken('API Token')->plainTextToken,
        ]);
    }
    

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function signupWithGoogle(Request $request)
    {
        $idToken = $request->input('id_token');  
        $client = new Google_Client(['client_id' => config('services.google.client_id')]);
           
        try {
            $payload = $client->verifyIdToken($idToken);
            
            if ($payload) {
                // ID Token valid, ambil informasi pengguna
                $userId = $payload['sub'];  // ID pengguna Google
                $email = $payload['email'];  // Email pengguna

                // Cek jika pengguna sudah terdaftar berdasarkan google_id
                $user = User::where('google_id', $userId)->first();

                if (!$user) {
                    $emailUser = User::where('email', $email)->first();
                    if (!$emailUser){
                        // Jika pengguna belum terdaftar, buat akun baru tanpa password

                        $user = User::create([
                            'email' => $email,
                            'google_id' => $userId,
                            'password' => null,  // Biarkan password null
                            'role' => 'admin',
                            'auth_provider' => 'google',
                        ]);
                    } else {
                        // Jika email sudah ada, update dengan google_id
                        $emailUser->google_id = $userId;
                        $emailUser->save();
                        $user = $emailUser;
                    }
            
                }
                return response()->json([   
                    'token' => $user->createToken('API Token')->plainTextToken,
                    'is_profile_complete' => $user->is_profile_complete,
                ]);
            } else {
                return response()->json(['errors' => ['message' => 'Invalid ID Token']], 400);
            }
        } catch (Exception $e) {
            return response()->json(['errors' => ['message' => 'Verification failed: ' . $e->getMessage()]], 400);
        }
    }

    public function loginWithGoogle(Request $request)
    {
        $idToken = $request->input('id_token');  // Ambil ID Token dari form-data atau JSON

        $client = new Google_Client(['client_id' => config('services.google.client_id')]);

        try {
            $payload = $client->verifyIdToken($idToken);

            if ($payload) {
                // ID Token valid, ambil informasi pengguna
                $userId = $payload['sub'];   // ID pengguna Google
                $email = $payload['email'];  // Email pengguna

                // Cari user berdasarkan google_id
                $user = User::where('google_id', $userId)->first();

                if (!$user) {
                    // Jika tidak ketemu berdasarkan google_id, coba cek berdasarkan email
                    $user = User::where('email', $email)->first();

                    if ($user) {
                        // Kalau email cocok tapi belum punya google_id, update google_id
                        $user->google_id = $userId;
                        $user->save();
                    } else {
                        // Kalau tidak ada user, berarti belum terdaftar
                        return response()->json(['errors' => ['message' => 'User not registered. Please sign up first.']], 404);
                    }
                }

                // Berikan token
                return response()->json([
                    'token' => $user->createToken('API Token')->plainTextToken,
                    'full_name' => $user->full_name,
                    'user_photo' => $user->user_photo,
                    'role' => $user->role,
                    'is_profile_complete' => $user->is_profile_complete,
                ]);
            } else {
                return response()->json(['errors' => ['message' => 'Invalid ID Token']], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => ['message' => 'Verification failed: ' . $e->getMessage()]], 400);
        }
    }

}