<?php

namespace App\Http\Controllers;

use App\Mail\ResetPassword;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ResetPasswordController extends Controller
{
    public function reqResetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // $user = $request->email;

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['errors' => ['message' => 'Email not found']], 404);
        }

        if ($user->auth_provider !== 'local') {
            return response()->json(['errors' => ['message' => 'This account uses a third-party login — no password to reset!']], 400);
        }
        // Generate a unique reset token
        do {
            $resetToken = bin2hex(random_bytes(32));
        } while (User::where('reset_token', $resetToken)->exists());

        // Set expiration time (e.g., 1 hour from now)
        $resetTokenExpiresAt = now()->addHour();

        // Save token and expiration to user
        $user->reset_token = $resetToken;
        $user->reset_token_expire = $resetTokenExpiresAt;
        $user->save();
        
        $url = 'http://localhost:3000/';
        
        $resetUrl = $url . '/sign-in/set-new-password?token=' . $resetToken;

        Mail::to($user->email)->send(new ResetPassword($user->email, $resetUrl));    
        // Mail::to($user)->send(new ResetPassword($user, $resetUrl));    

        return response()->json(['message' => 'Link was sent to your E-mail'], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[\W_]/|confirmed',
        ]);

        $user = User::where('reset_token', $request->token)
            ->where('reset_token_expire', '>', now())
            ->first();

        if (!$user) {
            return response()->json(['errors' => ['message' => 'Invalid or expired token', 'is_expired' => true]], 400);
        }

        // Update the user's password
        $user->password = Hash::make($request->password);
        $user->reset_token = null;
        $user->reset_token_expire = null;
        $user->save();

        return response()->json(['message' => 'Password reset successfully', 'is_expired' => false], 200);
    }

    public function checkToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = User::where('reset_token', $request->token)
            ->where('reset_token_expire', '>', now())
            ->first();

        if (!$user) {
            return response()->json(['errors' => ['message' => 'Invalid or expired token', 'is_expired' => true]], 400);
        }

        return response()->json(['message' => 'Valid token', 'is_expired' => false], 200);
    }
}
