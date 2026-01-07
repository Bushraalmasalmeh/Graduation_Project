<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Models\Car;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Get user profile with car details
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load('car', 'primaryPhone');
        $todayStart = Carbon::today();
        $todayEnd = Carbon::now();
        $usedMinutesToday = Booking::where(
            'user_id',
            $user->id
        )
            ->whereBetween('start_time', [$todayStart, $todayEnd])
            ->where('status', '!=', 'cancelled')->sum(DB::raw('TIMESTAMPDIFF(MINUTE, start_time, end_time)'));
        $dailyLimitMinutes = ($user->daily_limit_hours ?? 0) * 60;
        $remainingLimit = max(0, $dailyLimitMinutes - $usedMinutesToday);
        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully',
            'data' => ['user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'job_number' => $user->job_number,
                'department' => $user->department,
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : asset('storage/avatars/user.png'),
                'device_token' => $user->device_token,
                'status' => $user->status,
                'role_type' => $user->role_type,
                'warnings' => $user->warnings,
                'accept_terms' => !is_null($user->accepted_terms_at),
                'accepted_terms_at' => optional($user->accepted_terms_at)->toIso8601String(),
                'daily_limit_hours' => $user->daily_limit_hours ?? 0,
                'used_today_minutes' => $usedMinutesToday,
                'remaining_limit_minutes' => $remainingLimit,
                'created_at' => $user->created_at->toIso8601String(),
                'updated_at' => $user->updated_at->toIso8601String(),
                'phone' => optional($user->primaryPhone)->phone_number,
                'car' => [
                    'car_model' => optional($user->car)->car_model,
                    'plate_number' => optional($user->car)->plate_number,
                    'car_image' => optional($user->car)->car_image,
                    'car_image_url' => optional($user->car) && $user->car->car_image ? asset('storage/' . $user->car->car_image) : asset('images/car_models/car.jpg'),
                ],
            ], 'token' => $request->bearerToken(),]
        ]);
    }



    /**
     * Update user profile information
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        $user->update($request->validated());

        if ($request->filled('phone')) {
            $user->primaryPhone()->updateOrCreate(
                ['type' => 'primary'],
                ['phone_number' => $request->phone]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user->load('primaryPhone')
            ]
        ]);
    }


    /**
     * Update user avatar image
     */ public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        $user = $request->user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->storeAs(
            'avatars',
            $user->id . '_' . time() . '.jpg',
            'public'
        );

        $user->update(['avatar' => $path]);

        return response()->json([
            'status'     => 'success',
            'message'    => 'Avatar updated successfully',
            'avatar_url' => asset('storage/' . $path),
            'user'       => $user
        ], 200);
    }

    /**
     * Update user car information
     */
    public function updateCar(Request $request)
    {
        $request->validate([
            'job_number' => 'required|string|exists:users,job_number',
            'car_model' => 'required|string|max:50',
            'plate_number' => 'required|string|max:20|unique:cars,plate_number,' . optional($request->user()->car)->id,
            'car_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
        $authUser = $request->user();
        $user = User::where('job_number', $request->job_number)->first();
        if (!$user || $user->id !== $authUser->id) {
            return response()->json(['success' => false, 'message' => 'invalid_job_number'], 403);
        }
        $car = Car::firstOrNew(['user_id' => $user->id]);
        if ($car->exists && $car->updated_at && $car->updated_at->diffInDays(now()) < 30) {
            return response()->json(['success' => false, 'message' => 'car_update_locked_for_30_days'], 403);
        }
        $car->car_model = $request->car_model;
        $car->plate_number = $request->plate_number;
        if ($request->hasFile('car_image')) {
            if ($car->car_image && Storage::disk('public')->exists($car->car_image)) {
                Storage::disk('public')->delete($car->car_image);
            }
            $car->car_image = $request->file('car_image')->store('cars', 'public');
        }
        $car->save();
        return response()->json(['success' => true, 'message' => 'Car information updated successfully', 'data' => ['car' => $car]]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();
        if (Hash::check($request->new_password, $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'New password must be different'], 400);
        }

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'Old password incorrect'], 400);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['status' => 'success', 'message' => 'Password changed successfully']);
    }
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['status' => 'success', 'message' => 'Logged out from all devices']);
    }
}
