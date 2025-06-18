<?php

namespace App\Http\Controllers\employee;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Models\Employee;

use Illuminate\Support\Facades\Hash;


class EmployeeProfileController extends Controller
{
    
    public function profile()
    {
        $user = Auth::user(); // User yang sedang login

        // Ambil data employee yang terkait
        $employee = Employee::with([
            'bank',
            'position.department'
        ])
            ->where('user_id', $user->id)
            ->first();

        return response()->json($employee);
    }


public function changePassword(Request $request)
{
    
    // 1. Validasi input
   $request->validate([
    'current_password' => 'required',
    'new_password' => [
        'required',
        'min:8',
        'confirmed',
        'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
    ],
    'new_password_confirmation' => 'required'
], [
    'new_password.regex' => 'Password harus mengandung huruf kapital, angka, dan simbol.',
    'new_password.min' => 'Password minimal 8 karakter.',
    'new_password.confirmed' => 'Konfirmasi password tidak cocok.',
    'new_password_confirmation.required' => 'Konfirmasi password wajib diisi.',
    'current_password.required' => 'Password lama wajib diisi.',
]);

    $user = Auth::user();

    // 2. Cek apakah password lama cocok
    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json([
            'status' => false,
            'message' => 'Current password is incorrect'
        ], 400);
    }

    // 3. Cek apakah password baru sama dengan yang lama
    if ($request->current_password === $request->new_password) {
        return response()->json([
            'status' => false,
            'message' => 'New password cannot be the same as the current password'
        ], 400);
    }

    // 4. Update password
    $user->password = Hash::make($request->new_password);
    If ($user->save()){

        return response()->json([
            'status' => true,
            'message' => 'Password successfully updated'
        ]);
    } else {
        return response()->json([
            'status' => false,
            'message' => 'Failed to update password'
        ], 500);
    }
 
}

   

    // public function update(Request $request)
    // {
    //     /** @var \App\Models\User $user */
    //     $user = Auth::user();
    //     $userId = $user->id;

    //     $request->validate([
    //         'user_photo' => 'sometimes|nullable|image|max:5120',
    //         'fullName' => 'sometimes|required|string',
    //         'email' => [
    //             'sometimes',
    //             'required',
    //             'email',
    //             'max:255',
    //             Rule::unique('users', 'email')->ignore($userId),
    //         ],
    //         'phone' => [
    //             'sometimes',
    //             'required',
    //             'string',
    //             'min:10',
    //             'max:17',
    //             Rule::unique('users', 'phone')->ignore($userId),
    //         ],
    //     ]);

    //     if ($request->hasFile('user_photo')) {
    //         if ($user->user_photo && Storage::disk('s3')->exists($user->user_photo)) {
    //             Storage::disk('s3')->delete($user->user_photo);
    //         }

    //         $path = $request->file('user_photo')->store('user_photo', 's3');
    //         $user->user_photo = $path;
    //     }

    //     if ($request->filled('fullName')) {
    //         $user->full_name = $request->input('fullName');
    //     }

    //     if ($request->filled('email')) {
    //         $user->email = $request->input('email');
    //     }

    //     if ($request->filled('phone')) {
    //         $user->phone = $request->input('phone');
    //     }

    //     $user->save();
    //     $userPhotoUrl = null;
    //     if (!empty($user->user_photo) && $user->user_photo !== '') {
    //         $userPhotoUrl = Storage::disk('s3')->temporaryUrl(
    //             $user->user_photo,
    //             Carbon::now()->addMinutes(10)
    //         );
    //     }
    //     return response()->json(['message' => 'Profile updated successfully', 'user_photo' => $userPhotoUrl]);
    // }
}
