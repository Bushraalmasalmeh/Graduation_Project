<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Car;


class AuthController extends Controller
{

    public function register(Request $request)
    {
        // ===========================
        // VALIDATION
        // ===========================
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'job_number' => 'required|unique:users',
            'department' => 'required',

            'car_model' => 'required|string',
            'plate_number' => 'required|string|unique:cars',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // ===========================
        // CREATE USER
        // ===========================
        $user = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => bcrypt($request->password),
            'job_number' => $request->job_number,
            'department' => $request->department,
            'role_type'  => 'staff',
            'status'     => 'active',
        ]);

        // ===========================
        // CREATE CAR
        // ===========================
        $car = Car::create([
            'user_id'      => $user->id,
            'car_model'    => $request->car_model,
            'plate_number' => $request->plate_number,
            'car_image'    => null,
        ]);

        // ===========================
        // CREATE TOKEN
        // ===========================
        $token = $user->createToken('electra_token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'User_registered successfully',
            'user'    => $user,
            'car'     => $car,
            'token'   => $token
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (!$user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid_credentials'
            ], 401);
        }

        // Update device token if provided
        if (!empty($data['device_token'])) {
            $user->device_token = $data['device_token'];
            $user->save();
        }

        // Delete old tokens
        // Only delete expired tokens
        $user->tokens()->where('expires_at', '<', now())->delete();
        // OR limit concurrent sessions
        if ($user->tokens()->count() > 5) {
            $user->tokens()->oldest()->first()->delete();
        }
        // Create new token
        $token = $user->createToken('electra_token')->plainTextToken;

        return response()->json([
            'message' => 'Login_successful',
            'user'    => $user,
            'token'   => $token
        ], 200);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout()
    {
        request()->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged_out_successfully'
        ]);
    }
}
