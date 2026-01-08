<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // ============================
    // USER SENDS SUPPORT MESSAGE
    // ============================
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:5',
        ]);


        $user = $request->user()->load('primaryPhone');

        $msg = ContactMessage::create([
            'user_id' => $user->id,
            'name'    => $user->name,
            'email'   => $user->email,
            'phone'   => optional($user->primaryPhone)->phone_number,
            'message' => $request->message,
            'status'  => 'open'
        ]);

        $this->notificationService->notifyAdmins(
            'Contact Message Received',
            'From ' . $msg->phone . ': ' . $msg->message,
            'support'
        );

        return response()->json([
            'message' => 'support_message_sent',
            'data'    => $msg
        ], 200);
    }


    public function myMessages(): JsonResponse
    {
        $user = request()->user();
        $messages = ContactMessage::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $messages
        ]);
    }
}
