<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(){
        $user = Auth::user();
         if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        $userPhotoUrl = null;
        if (!empty($user->user_photo) && $user->user_photo !== '') {
            $userPhotoUrl = Storage::disk('s3')->temporaryUrl(
                $user->user_photo,
                Carbon::now()->addMinutes(10) // berlaku 10 menit
            );
        }
        return response()->json([
            'photo_url' => $userPhotoUrl,
            'full_name' => $user->full_name,
            'phone' => $user->phone,
            'email' => $user->email,
            'company_name' => $user->company?->name,
            'company_id' => $user->company_id
        ]);
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $userId = $user->id;

        $request->validate([
            'user_photo' => 'sometimes|nullable|image|max:5120',
            'fullName' => 'sometimes|required|string',
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => [
                'sometimes',
                'required',
                'string',
                'min:10',
                'max:17',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
        ]);

        if ($request->hasFile('user_photo')) {
            if ($user->user_photo && Storage::disk('s3')->exists($user->user_photo)) {
                Storage::disk('s3')->delete($user->user_photo);
            }

            $path = $request->file('user_photo')->store('user_photo', 's3');
            $user->user_photo = $path;
        }

        if ($request->filled('fullName')) {
            $user->full_name = $request->input('fullName');
        }

        if ($request->filled('email')) {
            $user->email = $request->input('email');
        }

        if ($request->filled('phone')) {
            $user->phone = $request->input('phone');
        }

        $user->save();
        $userPhotoUrl = null;
        if (!empty($user->user_photo) && $user->user_photo !== '') {
            $userPhotoUrl = Storage::disk('s3')->temporaryUrl(
                $user->user_photo,
                Carbon::now()->addMinutes(10)
            );
        }
        return response()->json(['message' => 'Profile updated successfully', 'user_photo'=> $userPhotoUrl]);
    }



}
