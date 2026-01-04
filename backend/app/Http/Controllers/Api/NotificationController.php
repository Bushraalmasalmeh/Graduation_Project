<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = DB::table('notifications')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['status' => 'success', 'data' => $notifications]);
    }

    public function markAsRead(Request $request, int $id)
    {
        $user = $request->user();

        $affected = DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->update(['is_read' => true, 'updated_at' => now()]);

        if (!$affected) {
            return response()->json(['status' => 'error', 'message' => 'Notification not found'], 404);
        }

        return response()->json(['status' => 'success', 'message' => 'Notification marked as read']);
    }

    public function test(Request $request)
    {
        $user = $request->user();
        $this->notificationService->notifyUser($user->id, 'Test Notification', 'This is a test', 'system');

        return response()->json(['status' => 'success', 'message' => 'Test notification sent']);
    }
}
