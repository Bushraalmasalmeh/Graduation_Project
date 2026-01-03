<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized. Please login.'], 401);
        }

        // special case: if user role_type = both, treat it as valid if roles contain faculty or staff
        if ($user->role_type === 'both' && (in_array('faculty', $roles) || in_array('staff', $roles))) {
            return $next($request);
        }

        if (!in_array($user->role_type, $roles)) {
            return response()->json(['message' => 'access_denied'], 403);
        }

        return $next($request);
    }
}
