<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UsageSession;

class SessionHistoryController extends Controller
{
    public function index()
    {
        $sessions = UsageSession::with(['booking.station', 'booking.cabinet', 'booking.charger'])
            ->where('user_id', request()->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'sessions' => $sessions
        ]);
    }
}
