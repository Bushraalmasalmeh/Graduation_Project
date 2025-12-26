<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        // Combine status checks for performance
        if ($user->status === 'disabled') {
            return response()->json(['message' => 'account_disabled'], 403);
        }

        if ($user->status === 'blocked') {
            return response()->json(['message' => 'account_blocked_temporarily'], 403);
        }

        // Logic from CheckUserWarnings integrated for a cleaner pipeline
        if ($user->warnings >= 3) {
            return response()->json(['message' => 'maximum_warnings_reached'], 403);
        }

        return $next($request);
    }
}
