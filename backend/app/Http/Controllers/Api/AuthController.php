<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Car;
use App\Models\UserPhone;
use App\Mail\ResetCodeMail;

class AuthController extends Controller
{
    // ========== USER AUTH ==========
    public function userRegister(Request $request)
    {
        $request->validate([
            'name'         => 'required|string',
            'email'        => 'required|email|unique:users',
            'password'     => 'required|min:6|confirmed',
            'job_number'   => 'required|unique:users',
            'department'   => 'required',
            'role_type'    => 'required|in:faculty,staff,both',
            'car_model'    => 'required|string|max:50',
            'plate_number' => 'required|string|max:20|unique:cars',
            'phone'        => 'required|string|max:20', // ← تأكد من وجوده
        ]);

        $user = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => bcrypt($request->password),
            'job_number' => $request->job_number,
            'department' => $request->department,
            'role_type'  => $request->role_type,
            'status'     => 'active',
        ]);

        Car::create([
            'user_id'      => $user->id,
            'car_model'    => $request->car_model,
            'plate_number' => $request->plate_number,
            'car_image'    => null,
        ]);

        // حفظ رقم الهاتف في جدول user_phones
        $user->phones()->create([
            'phone' => $request->phone,
            'type'  => 'primary',
        ]);

        $token = $user->createToken('user_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user'    => $user->load('primaryPhone'),
            'token'   => $token
        ], 201);
    }

    public function userLogin(LoginRequest $request)
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('user_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user'    => $user->load('primaryPhone'),
            'token'   => $token
        ], 200);
    }

    // ========== ADMIN AUTH ==========
    public function adminRegister(Request $request)
    {
        $request->validate([
            'name'       => 'required|string',
            'email'      => 'required|email|unique:users',
            'password'   => 'required|min:6|confirmed',
            'job_number' => 'required|unique:users',
            'department' => 'required|string',
        ]);

        $admin = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => bcrypt($request->password),
            'job_number' => $request->job_number,
            'department' => $request->department,
            'role_type'  => 'admin',
            'status'     => 'active',
        ]);

        $token = $admin->createToken('admin_token')->plainTextToken;

        return response()->json([
            'message' => 'Admin registered successfully',
            'admin'   => $admin,
            'token'   => $token
        ], 201);
    }

    public function adminLogin(LoginRequest $request)
    {
        $data = $request->validated();
        $admin = User::where('email', $data['email'])
            ->where('role_type', 'admin')
            ->first();

        if (!$admin || !Hash::check($data['password'], $admin->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $admin->createToken('admin_token')->plainTextToken;

        return response()->json([
            'message' => 'Admin login successful',
            'admin'   => $admin->load('primaryPhone'),
            'token'   => $token
        ], 200);
    }

    // ========== COMMON AUTH UTILITIES ==========
    public function me(Request $request)
    {
        $user = $request->user()->load('primaryPhone');

        return response()->json([
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'job_number' => $user->job_number,
            'department' => $user->department,
            'role_type'  => $user->role_type,
            'status'     => $user->status,
            'phone'      => optional($user->primaryPhone)->phone,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    public function logout()
    {
        request()->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    // ========== PASSWORD RESET ==========
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $existing = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('expires_at', '>=', now())
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'A reset code was already sent. Please wait before requesting a new one.'
            ], 429);
        }

        $code = rand(100000, 999999);

        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        DB::table('password_reset_tokens')->insert([
            'email'      => $request->email,
            'token'      => $code,
            'created_at' => now(),
            'expires_at' => now()->addMinute(),
            'attempts'   => 0,
        ]);

        Mail::to($request->email)->send(new ResetCodeMail($code));

        return response()->json(['message' => 'Reset code sent to your email.']);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required'
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->code)
            ->where('expires_at', '>=', now())
            ->first();

        if (!$record) {
            return response()->json(['error' => 'Invalid or expired code'], 400);
        }

        return response()->json(['message' => 'Code verified.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'code'     => 'required',
            'password' => 'required|confirmed|min:6',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->code)
            ->where('expires_at', '>=', now())
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }

        $user = User::where('email', $request->email)->first();
        $user->update(['password' => bcrypt($request->password)]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password reset successful.']);
    }
}
