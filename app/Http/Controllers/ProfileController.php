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
        ]);
    }

    public function update(Request $request){
        $userId = Auth::id();
        $request->validate([
            'fullName' => 'required|string',
            'email' => [
                            'required',
                            'email',
                            'max:255',
                            Rule::unique('users', 'email')->ignore($userId),
                        ],
            'phoneNumber' => [
                            'required',
                            'string',
                            'min:10',
                            'max:17',
                            Rule::unique('users', 'phone')->ignore($userId),
                        ],
        ]);
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->full_name = $request->fullName;
        $user->email = $request->email;
        $user->phone = $request->phoneNumber;
        $user->save();

    }


}
