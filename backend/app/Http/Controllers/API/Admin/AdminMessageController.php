<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminMessageController extends Controller
{
    public function index(): JsonResponse
    {
        $messages = ContactMessage::with('user')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data'   => $messages
        ]);
    }

    public function reply(Request $request, $id): JsonResponse
    {
        $request->validate(['admin_reply' => 'required|string']);

        $message = ContactMessage::findOrFail($id);
        $message->update([
            'admin_reply' => $request->admin_reply,
            'status'      => 'resolved'
        ]);

        return response()->json(['message' => 'Reply_sent_to_user_Ssuccessfully']);
    }
    public function resolve($id)
    {
        $message = ContactMessage::findOrFail($id);
        $message->update(['status' => 'resolved']);

        return response()->json(['status' => 'success', 'message' => 'Message marked as resolved']);
    }
}
