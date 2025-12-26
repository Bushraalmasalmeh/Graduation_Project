<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user || !in_array($user->role_type, $roles)) {
            return response()->json(['message' => 'access_denied'], 403);
        }

        return $next($request);
    }
}
