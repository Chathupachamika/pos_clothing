<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        try {
            $fields = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users,email',
                'password' => 'required|string|confirmed|min:8'
            ]);

            $user = User::create([
                'name' => $fields['name'],
                'email' => $fields['email'],
                'password' => Hash::make($fields['password'])
            ]);

            $token = $user->createToken($request->name)->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
                'is-admin' => $user->isAdmin(),
                'message' => 'Registration successful'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $fields = $request->validate([
                'email' => 'required|email|exists:users,email',
                'password' => 'required|min:8'
            ]);
            $user = User::where('email', $fields['email'])->first();
            if (!$user || !Hash::check($fields['password'], $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ], 401);
            }
            

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'user' => $user,
                'token' => $token,
                'is-admin' => $user->isAdmin(),
                'message' => 'Login successful'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return [
            'message' => 'You are LogOut'
        ];
    }

    public function sendResetEmailLink(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email|exists:users,email']);

            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($otp),
                    'created_at' => now()
                ]
            );

            Mail::to($request->email)->send(new ResetPasswordMail($otp));

            return response()->json([
                'message' => 'Password reset OTP sent!',
                'email' => $request->email
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send reset email'], 500);
        }
    }

    public function verifyOTP(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6'
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->otp, $record->token)) {
            return response()->json(['error' => 'Invalid OTP'], 400);
        }

        // Check if OTP is expired (optional, 10 minutes expiry)
        if (now()->diffInMinutes($record->created_at) > 10) {
            return response()->json(['error' => 'OTP has expired'], 400);
        }

        return response()->json(['message' => 'OTP verified successfully']);
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'token' => 'required|string',
                'password' => 'required|string|min:8|confirmed'
            ]);

            $record = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$record) {
                return response()->json(['error' => 'Reset request not found'], 400);
            }

            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            return response()->json([
                'message' => 'Password has been reset successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
