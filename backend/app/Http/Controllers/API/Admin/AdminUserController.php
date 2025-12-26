<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AdminEditUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index()
    {
        return response()->json([
            'users' => User::orderByDesc('created_at')->get()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password' => [
                'required',
                'min:12',
                'regex:/[A-Z]/',      // at least one uppercase
                'regex:/[a-z]/',      // at least one lowercase  
                'regex:/[0-9]/',      // at least one number
                'regex:/[@$!%*#?&]/', // at least one special char,
            ],
            'role_type'  => 'required|in:admin,faculty,staff,student',
            'job_number' => 'required',
            'department' => 'required'
        ]);


        $user = User::create($validated);

        return response()->json([
            'message' => 'User_created_successfully',
            'user'    => $user
        ], 201);
    }

    public function update(AdminEditUserRequest $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update([
            'name' => $request->name,
            'email' => $request->email
        ]);
        return response()->json([
            'message' => 'User_updated',
            'user'    => $user
        ]);
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return response()->json([
            'message' => 'User_deleted'
        ]);
    }
}
