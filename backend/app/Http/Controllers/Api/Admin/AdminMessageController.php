<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminMessageController extends Controller
{
    public function index()
    {
        $messages = ContactMessage::with('user') // نجيب العلاقة كاملة
            ->latest()
            ->get()
            ->map(function ($msg) {
                return [
                    'id'         => $msg->id,
                    'user'       => [
                        'name'  => optional($msg->user)->name ?? 'Guest/Deleted',
                        'email' => optional($msg->user)->email ?? 'N/A',
                    ],
                    'subject'    => $msg->subject ?? 'No Subject',
                    'message'    => $msg->message,
                    'status'     => $msg->status,
                    'created_at' => $msg->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json(['messages' => $messages]);
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'reply' => 'required|string',
        ]);

        $message = ContactMessage::findOrFail($id);
        $message->admin_reply = $request->reply;
        $message->status = 'replied';
        $message->save();

        return response()->json([
            'success' => true,
            'message' => 'Reply sent successfully',
            'status'  => $message->status,
        ]);
    }

    public function resolve($id)
    {
        $message = ContactMessage::findOrFail($id);
        $message->update(['status' => 'replied']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Message marked as resolved'
        ]);
    }
}
