<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\Concerns\Has;

class AdminMessageController extends Controller
{
    public function index()
    {
        $messages = ContactMessage::with('user:id,name,email')
            ->latest()
            ->get()
            ->map(function ($msg) {
                return [
                    'id'         => $msg->id,
                    'user'       => [
                        'name'  => $msg->user->name,
                        'email' => $msg->user->email,
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
        $message->status = 'replied'; // ✅ استخدم نفس القيم الموجودة في الـ migration
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

        return response()->json(['status' => 'success', 'message' => 'Message marked as resolved']);
    }
}
