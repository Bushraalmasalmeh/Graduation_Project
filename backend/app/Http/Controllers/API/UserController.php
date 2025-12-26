<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use App\Models\Car;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Get user profile with car details
     */
    public function profile()
    {
        $user = request()->user()->load('car');

        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully',
            'data' => [
                'user' => $user
            ]
        ]);
    }

    /**
     * Update user profile information
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user
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

        // ✅ هذه السطر كان ناقص!
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
        $user = request()->user();

        $request->validate([
            'car_model'    => 'required|string|max:50',
            'plate_number' => 'required|string|max:20|unique:cars,plate_number,' . optional($user->car)->id,
            'car_image'    => 'nullable|image|max:2048',
        ]);

        $car = Car::firstOrNew(['user_id' => $user->id]);
        $car->car_model = $request->car_model;
        $car->plate_number = $request->plate_number;

        if ($request->hasFile('car_image')) {
            // Delete old car image if exists
            if ($car->car_image && Storage::disk('public')->exists($car->car_image)) {
                Storage::disk('public')->delete($car->car_image);
            }
            $car->car_image = $request->file('car_image')->store('cars', 'public');
        }

        $car->save();

        return response()->json([
            'success' => true,
            'message' => 'Car information updated successfully',
            'data' => [
                'car' => $car
            ]
        ]);
    }
}
