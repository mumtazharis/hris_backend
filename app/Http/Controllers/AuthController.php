<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Google_Client;
use Exception;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|numeric|digits_between:10,15|unique:users,phone',
            'password' => 'nullable|sometimes|required_without:google_id|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[\W_]/|confirmed',
            'google_id' => 'nullable|sometimes|required_without:password',
        ]);
        $user = User::create([
            // 'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['token' => $user->createToken('API Token')->plainTextToken]);
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
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    
        // Jika login berhasil, buat token dan kirimkan sebagai response
        return response()->json(['token' => $user->createToken('API Token')->plainTextToken]);
    }
    

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function loginWithGoogle(Request $request)
    {
        $idToken = $request->input('id_token');  // Ambil ID Token dari form-data atau JSON

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
                        ]);
                    } else {
                        // Jika email sudah ada, update dengan google_id
                        $emailUser->google_id = $userId;
                        $emailUser->save();
                        $user = $emailUser;
                    }
            
                }
                return response()->json([   
                    'token' => $user->createToken('API TOKEN')->plainTextToken,
                ]);
            } else {
                return response()->json(['error' => 'Invalid ID token'], 400);
            }
        } catch (Exception $e) {
            return response()->json(['error' => 'Verification failed: ' . $e->getMessage()], 400);
        }
    }
}