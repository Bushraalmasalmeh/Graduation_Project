<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Update the authenticated admin's profile (name and phone number).
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $admin = $request->user();

        // Check if phone number is already used by another user
        $exists = DB::table('user_phones')
            ->where('phone_number', $request->phone_number)
            ->where('user_id', '!=', $admin->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number is already in use'
            ], 422);
        }

        // Update admin name
        $admin->update(['name' => $request->name]);

        // Update or insert phone number in user_phones table
        $admin->primaryPhone()->updateOrCreate(
            ['user_id' => $admin->id, 'type' => 'primary'],
            ['phone_number' => $request->phone_number]
        );


        return response()->json([
            'success' => true,
            'message' => 'Admin profile updated successfully',
            'admin'   => array_merge(
                $admin->toArray(),
                ['phone_number' => $request->phone_number]
            )
        ]);
    }

    /**
     * Change the authenticated admin's password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $admin = $request->user();

        if (!Hash::check($request->current_password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        $admin->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }
}
