<?php

namespace App\Http\Middleware;

use App\Models\UsageSession;
use Closure;
use Illuminate\Http\Request;

class ValidateStartSession
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Check if the user is active (Status check)
        if ($request->user()->status !== 'active') {
            return response()->json(['message' => 'User account is not active'], 403);
        }

        // 2. Check for warnings
        if ($request->user()->warnings >= 3) {
            return response()->json(['message' => 'Too many warnings. Contact Admin.'], 403);
        }

        $activeSession = UsageSession::where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->exists();

        if ($activeSession) {
            return response()->json([
                'message' => 'active_session_exists'
            ], 409);
        }
        return $next($request);
    }
}
