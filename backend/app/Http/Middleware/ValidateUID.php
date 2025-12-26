<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Charger;

class ValidateUID
{
    public function handle($request, Closure $next)
    {
        $uid = $request->input('uid');

        if (!$uid) {
            return response()->json(['message' => 'uid_required'], 400);
        }

        $charger = Charger::where('uid', $uid)->first();

        if (!$charger) {
            return response()->json(['message' => 'invalid_charger_uid'], 404);
        }

        $request->attributes->set('charger', $charger);

        return $next($request);
    }
}
