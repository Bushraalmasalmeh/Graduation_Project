<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;
use App\Models\User;

class AdminNotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function index()
    {
        $notifications = DB::table('notifications')->orderByDesc('created_at')->get();
        return response()->json(['status' => 'success', 'data' => $notifications]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'title'   => 'required|string|max:255',
            'message' => 'nullable|string',
            'type'    => 'nullable|string|max:50',
        ]);

        foreach ($data['user_ids'] as $userId) {
            $this->notificationService->notifyUser(
                (int) $userId,
                $data['title'],
                $data['message'] ?? null,
                $data['type'] ?? 'system'
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification sent to selected users'
        ]);
    }


    public function broadcast(Request $request)
    {
        $data = $request->validate([
            'title'   => 'required|string|max:255',
            'message' => 'nullable|string',
            'type'    => 'nullable|string|max:50',
            'role'    => 'nullable|string|in:user,admin,all',
        ]);

        $role = $data['role'] ?? 'all';
        $userQuery = User::query();

        if ($role === 'user') {
            $userQuery->where('role', 'user');
        } elseif ($role === 'admin') {
            $userQuery->where('role', 'admin');
        }

        $userIds = $userQuery->pluck('id')->all();

        $this->notificationService->notifyMany(
            $userIds,
            $data['title'],
            $data['message'] ?? null,
            $data['type'] ?? 'system'
        );

        return response()->json(['status' => 'success', 'message' => 'Broadcast sent']);
    }
}
